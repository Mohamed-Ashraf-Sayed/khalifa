<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Contractor;
use App\Models\ContractorExtract;
use App\Models\ContractorPayment;
use App\Services\BankLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ContractorPaymentController extends Controller implements HasMiddleware
{
    public function __construct(private readonly BankLedgerService $ledger) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:contractors.view', only: ['index', 'show']),
            new Middleware('can:contractors.create', only: ['create', 'store']),
            new Middleware('can:contractors.edit', only: ['edit', 'update']),
            new Middleware('can:contractors.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $contractorId = (string) $request->input('contractor_id', '');
        $paymentMethod = (string) $request->input('payment_method', '');
        $dateFrom = (string) $request->input('date_from', '');
        $dateTo = (string) $request->input('date_to', '');

        $filtered = fn ($q) => $q
            ->when($contractorId !== '', fn ($q) => $q->where('contractor_id', $contractorId))
            ->when($paymentMethod !== '', fn ($q) => $q->where('payment_method', $paymentMethod))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('payment_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('payment_date', '<=', $dateTo));

        $payments = ContractorPayment::query()
            ->with(['contractor', 'bankAccount', 'extract'])
            ->tap($filtered)
            ->latest('payment_date')
            ->paginate(15)
            ->withQueryString();

        $total = (float) ContractorPayment::query()->tap($filtered)->sum('amount');

        return view('contractor_payments.index', [
            'payments' => $payments,
            'total' => $total,
            'contractors' => Contractor::orderBy('name')->get(),
            'contractorId' => $contractorId,
            'paymentMethod' => $paymentMethod,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function show(ContractorPayment $contractor_payment): View
    {
        $contractor_payment->load(['contractor', 'extract', 'bankAccount', 'creator']);

        return view('contractor_payments.show', ['payment' => $contractor_payment]);
    }

    public function create(): View
    {
        return view('contractor_payments.form', $this->formData(new ContractorPayment(['payment_date' => now()->toDateString(), 'payment_method' => 'cash'])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;

        DB::transaction(function () use ($data) {
            $payment = ContractorPayment::create($data);
            $this->syncBankTransaction($payment);
            $payment->extract?->refreshPaymentStatus();
        });

        return redirect()->route('contractor_payments.index')->with('success', 'تمت إضافة الدفعة.');
    }

    public function edit(ContractorPayment $contractorPayment): View
    {
        return view('contractor_payments.form', $this->formData($contractorPayment));
    }

    public function update(Request $request, ContractorPayment $contractorPayment): RedirectResponse
    {
        $data = $this->validateData($request);
        $originalExtractId = $contractorPayment->extract_id;

        DB::transaction(function () use ($contractorPayment, $data, $originalExtractId) {
            $contractorPayment->update($data);
            $this->syncBankTransaction($contractorPayment);
            $contractorPayment->extract?->refreshPaymentStatus();
            // لو اتغيّر المستخلص، حدّث القديم كمان
            if ($originalExtractId && $originalExtractId !== $contractorPayment->extract_id) {
                ContractorExtract::find($originalExtractId)?->refreshPaymentStatus();
            }
        });

        return redirect()->route('contractor_payments.index')->with('success', 'تم تحديث الدفعة.');
    }

    public function destroy(ContractorPayment $contractorPayment): RedirectResponse
    {
        DB::transaction(function () use ($contractorPayment) {
            $extract = $contractorPayment->extract;
            $this->removeLinkedBankTransaction($contractorPayment);
            $contractorPayment->delete();
            $extract?->refreshPaymentStatus();
        });

        return back()->with('success', 'تم حذف الدفعة.');
    }

    /**
     * يضمن تطابق الحركة البنكية المرتبطة مع حالة الدفعة الحالية:
     * يحذف أي حركة سابقة، ثم يسجّل سحباً جديداً لو الدفعة من حساب بنكي.
     */
    private function syncBankTransaction(ContractorPayment $payment): void
    {
        $this->removeLinkedBankTransaction($payment);

        if ($payment->bank_account_id) {
            $account = BankAccount::findOrFail($payment->bank_account_id);
            $this->ledger->post($account, [
                'type' => 'withdrawal',
                'amount' => $payment->amount,
                'transaction_date' => $payment->payment_date,
                'description' => 'دفعة مقاول',
                'reference_number' => $payment->reference_number,
                'related_type' => 'contractor_payment',
                'related_id' => $payment->id,
                'created_by' => $payment->created_by,
            ]);
        }
    }

    private function removeLinkedBankTransaction(ContractorPayment $payment): void
    {
        BankTransaction::where('related_type', 'contractor_payment')
            ->where('related_id', $payment->id)
            ->get()
            ->each(fn (BankTransaction $t) => $this->ledger->deleteTransaction($t));
    }

    private function formData(ContractorPayment $contractorPayment): array
    {
        return [
            'contractorPayment' => $contractorPayment,
            'contractors' => Contractor::orderBy('name')->get(),
            'accounts' => BankAccount::where('is_active', true)->orderBy('name')->get(),
            'extracts' => ContractorExtract::latest()->get(),
        ];
    }

    private function validateData(Request $request): array
    {
        $allowed = array_merge(
            array_keys(ContractorPayment::PAYMENT_METHODS),
            \App\Models\CustomPaymentMethod::where('is_active', true)->pluck('code')->all(),
        );

        return $request->validate([
            'contractor_id' => ['required', 'exists:contractors,id'],
            'extract_id' => ['nullable', 'exists:contractor_extracts,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:'.implode(',', $allowed)],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
