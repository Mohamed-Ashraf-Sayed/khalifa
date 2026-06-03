<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class SupplierTransactionController extends Controller implements HasMiddleware
{
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
        $category = (string) $request->input('category', '');

        $transactions = SupplierTransaction::query()
            ->with(['supplier', 'project'])
            ->when($supplierId !== '', fn ($q) => $q->where('supplier_id', $supplierId))
            ->when($category !== '', fn ($q) => $q->where('category', $category))
            ->latest('transaction_date')
            ->paginate(15)
            ->withQueryString();

        return view('supplier_transactions.index', [
            'transactions' => $transactions,
            'suppliers' => Supplier::orderBy('name')->get(),
            'supplierId' => $supplierId,
            'category' => $category,
            'totalNet' => (float) SupplierTransaction::sum('net_amount'),
            'totalPaid' => (float) SupplierTransaction::sum('paid_amount'),
        ]);
    }

    public function show(SupplierTransaction $supplier_transaction): View
    {
        $supplier_transaction->load(['supplier', 'project', 'creator']);

        return view('supplier_transactions.show', ['transaction' => $supplier_transaction]);
    }

    public function create(): View
    {
        return view('supplier_transactions.form', $this->formData(new SupplierTransaction([
            'transaction_date' => now()->toDateString(),
            'quantity' => 1,
            'payment_method' => 'cash',
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;

        SupplierTransaction::create($data);

        return redirect()->route('supplier_transactions.index')->with('success', 'تمت إضافة عملية الشراء.');
    }

    public function edit(SupplierTransaction $supplierTransaction): View
    {
        return view('supplier_transactions.form', $this->formData($supplierTransaction));
    }

    public function update(Request $request, SupplierTransaction $supplierTransaction): RedirectResponse
    {
        $supplierTransaction->update($this->validateData($request));

        return redirect()->route('supplier_transactions.index')->with('success', 'تم تحديث عملية الشراء.');
    }

    public function destroy(SupplierTransaction $supplierTransaction): RedirectResponse
    {
        $supplierTransaction->delete();

        return back()->with('success', 'تم حذف عملية الشراء.');
    }

    private function formData(SupplierTransaction $supplierTransaction): array
    {
        return [
            'transaction' => $supplierTransaction,
            'suppliers' => Supplier::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
        ];
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'transaction_date' => ['required', 'date'],
            'item_description' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'in:'.implode(',', array_keys(SupplierTransaction::CATEGORIES))],
            'unit' => ['nullable', 'string', 'max:30'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:'.implode(',', array_keys(SupplierTransaction::PAYMENT_METHODS))],
            'check_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        // الحساب على السيرفر: الإجمالي = كمية × سعر، الصافي = الإجمالي − نسبة الخصم.
        $total = bcmul((string) $data['quantity'], (string) $data['unit_price'], 2);
        $discount = bcdiv(bcmul($total, (string) ($data['discount_percentage'] ?? 0), 4), '100', 2);

        $data['total_amount'] = $total;
        $data['net_amount'] = bcsub($total, $discount, 2);
        $data['paid_amount'] = $data['paid_amount'] ?? 0;
        $data['discount_percentage'] = $data['discount_percentage'] ?? 0;

        return $data;
    }
}
