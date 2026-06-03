<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\BankLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupplierPaymentController extends Controller implements HasMiddleware
{
    public function __construct(private readonly BankLedgerService $ledger) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:suppliers.view', only: ['index', 'show']),
            new Middleware('can:suppliers.create', only: ['create', 'store']),
            new Middleware('can:suppliers.edit', only: ['edit', 'update']),
            new Middleware('can:suppliers.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $supplierId = (string) $request->input('supplier_id', '');
        $paymentMethod = (string) $request->input('payment_method', '');
        $dateFrom = (string) $request->input('date_from', '');
        $dateTo = (string) $request->input('date_to', '');

        $filtered = fn ($q) => $q
            ->when($supplierId !== '', fn ($q) => $q->where('supplier_id', $supplierId))
            ->when($paymentMethod !== '', fn ($q) => $q->where('payment_method', $paymentMethod))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('payment_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('payment_date', '<=', $dateTo));

        $payments = SupplierPayment::query()
            ->with(['supplier', 'bankAccount'])
            ->tap($filtered)
            ->latest('payment_date')
            ->paginate(15)
            ->withQueryString();

        $total = (float) SupplierPayment::query()->tap($filtered)->sum('amount');

        return view('supplier_payments.index', [
            'payments' => $payments,
            'total' => $total,
            'suppliers' => Supplier::orderBy('name')->get(),
            'supplierId' => $supplierId,
            'paymentMethod' => $paymentMethod,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function show(SupplierPayment $supplier_payment): View
    {
        $supplier_payment->load(['supplier', 'bankAccount', 'creator']);

        return view('supplier_payments.show', ['payment' => $supplier_payment]);
    }

    public function create(): View
    {
        return view('supplier_payments.form', $this->formData(new SupplierPayment(['payment_date' => now()->toDateString(), 'payment_method' => 'cash'])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data = $this->applyDeductions($data);
        $data['created_by'] = $request->user()->id;

        DB::transaction(function () use ($data) {
            $payment = SupplierPayment::create($data);
            $this->syncBankTransaction($payment);
        });

        return redirect()->route('supplier_payments.index')->with('success', 'تمت إضافة الدفعة.');
    }

    public function edit(SupplierPayment $supplierPayment): View
    {
        return view('supplier_payments.form', $this->formData($supplierPayment));
    }

    public function update(Request $request, SupplierPayment $supplierPayment): RedirectResponse
    {
        $data = $this->validateData($request);
        $data = $this->applyDeductions($data);

        DB::transaction(function () use ($supplierPayment, $data) {
            $supplierPayment->update($data);
            $this->syncBankTransaction($supplierPayment);
        });

        return redirect()->route('supplier_payments.index')->with('success', 'تم تحديث الدفعة.');
    }

    public function destroy(SupplierPayment $supplierPayment): RedirectResponse
    {
        DB::transaction(function () use ($supplierPayment) {
            $this->removeLinkedBankTransaction($supplierPayment);
            $supplierPayment->delete();
        });

        return back()->with('success', 'تم حذف الدفعة.');
    }

    /**
     * يضمن تطابق الحركة البنكية المرتبطة مع حالة الدفعة الحالية:
     * يحذف أي حركة سابقة، ثم يسجّل سحباً جديداً لو الدفعة من حساب بنكي.
     */
    private function syncBankTransaction(SupplierPayment $payment): void
    {
        $this->removeLinkedBankTransaction($payment);

        // السحب البنكي = صافي المدفوع نقداً = الإجمالي − الاستقطاعات.
        $net = bcsub((string) $payment->amount, (string) $payment->total_deductions, 2);

        if ($payment->bank_account_id && bccomp($net, '0', 2) > 0) {
            $account = BankAccount::findOrFail($payment->bank_account_id);
            $this->ledger->post($account, [
                'type' => 'withdrawal',
                'amount' => $net,
                'transaction_date' => $payment->payment_date,
                'description' => 'دفعة مورد: '.optional($payment->supplier)->name,
                'reference_number' => $payment->reference_number,
                'related_type' => 'supplier_payment',
                'related_id' => $payment->id,
                'created_by' => $payment->created_by,
            ]);
        }
    }

    private function removeLinkedBankTransaction(SupplierPayment $payment): void
    {
        BankTransaction::where('related_type', 'supplier_payment')
            ->where('related_id', $payment->id)
            ->get()
            ->each(fn (BankTransaction $t) => $this->ledger->deleteTransaction($t));
    }

    private function formData(SupplierPayment $supplierPayment): array
    {
        return [
            'supplierPayment' => $supplierPayment,
            'suppliers' => Supplier::orderBy('name')->get(),
            'accounts' => BankAccount::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    private function validateData(Request $request): array
    {
        $allowed = array_merge(
            array_keys(SupplierPayment::PAYMENT_METHODS),
            \App\Models\CustomPaymentMethod::where('is_active', true)->pluck('code')->all(),
        );

        $rules = [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:'.implode(',', $allowed)],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];

        foreach (SupplierPayment::DEDUCTION_FIELDS as $field) {
            $rules[$field] = ['nullable', 'numeric', 'min:0'];
        }

        return $request->validate($rules);
    }

    /**
     * يضبط مكوّنات الاستقطاع المفقودة على 0 ويحسب الإجمالي بـ bcmath.
     * total_deductions لا يُقبل من الإدخال — يُحسب هنا.
     */
    private function applyDeductions(array $data): array
    {
        $total = '0';

        foreach (SupplierPayment::DEDUCTION_FIELDS as $field) {
            $data[$field] = $data[$field] ?? 0;
            $total = bcadd($total, (string) $data[$field], 2);
        }

        $data['total_deductions'] = $total;

        return $data;
    }
}
