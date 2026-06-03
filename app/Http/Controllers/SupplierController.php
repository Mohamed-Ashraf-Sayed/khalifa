<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class SupplierController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:suppliers.view', only: ['index', 'show', 'statement']),
            new Middleware('can:suppliers.create', only: ['create', 'store']),
            new Middleware('can:suppliers.edit', only: ['edit', 'update']),
            new Middleware('can:suppliers.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));

        $suppliers = Supplier::query()
            ->when($search !== '', fn ($q) => $q->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
            ))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers', 'search'));
    }

    public function show(Supplier $supplier): View
    {
        $supplier->load([
            'creator',
            'purchaseOrders' => fn ($q) => $q->latest(),
            'payments' => fn ($q) => $q->latest(),
        ]);

        return view('suppliers.show', compact('supplier'));
    }

    public function create(): View
    {
        return view('suppliers.form', ['supplier' => new Supplier()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        Supplier::create($data);

        return redirect()->route('suppliers.index')->with('success', 'تمت إضافة المورد بنجاح.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validateData($request));

        return redirect()->route('suppliers.index')->with('success', 'تم تحديث بيانات المورد.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        return back()->with('success', 'تم حذف المورد.');
    }

    /**
     * كشف حساب المورّد: حركات مرتّبة زمنياً مع رصيد جارٍ محسوب بـ bcmath.
     * مدين (+): توريدات المورّد + أوامر الشراء المستلَمة (ما علينا).
     * دائن (−): مدفوعات المورّد (سداد).
     */
    public function statement(Supplier $supplier): View
    {
        $rows = collect();

        foreach ($supplier->transactions()->get() as $txn) {
            $rows->push([
                'date' => $txn->transaction_date,
                'id' => $txn->id,
                'label' => 'توريد: '.($txn->item_description ?: '—'),
                'debit' => (string) $txn->net_amount,
                'credit' => '0',
            ]);
        }

        foreach ($supplier->purchaseOrders()->whereIn('status', ['partial', 'received'])->get() as $po) {
            $rows->push([
                'date' => $po->order_date,
                'id' => $po->id,
                'label' => 'أمر شراء '.$po->order_number,
                'debit' => (string) $po->net_amount,
                'credit' => '0',
            ]);
        }

        foreach ($supplier->payments()->get() as $payment) {
            $rows->push([
                'date' => $payment->payment_date,
                'id' => $payment->id,
                'label' => 'دفعة',
                'debit' => '0',
                'credit' => (string) $payment->amount,
            ]);
        }

        $rows = $rows
            ->sortBy([
                fn ($a, $b) => optional($a['date'])->timestamp <=> optional($b['date'])->timestamp,
                fn ($a, $b) => $a['id'] <=> $b['id'],
            ])
            ->values();

        $running = '0';
        $totalDebit = '0';
        $totalCredit = '0';
        $rows = $rows->map(function ($row) use (&$running, &$totalDebit, &$totalCredit) {
            $running = bcsub(bcadd($running, $row['debit'], 2), $row['credit'], 2);
            $totalDebit = bcadd($totalDebit, $row['debit'], 2);
            $totalCredit = bcadd($totalCredit, $row['credit'], 2);
            $row['running'] = $running;

            return $row;
        });

        return view('suppliers.statement', [
            'supplier' => $supplier,
            'rows' => $rows,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'balance' => $supplier->balanceDue(),
        ]);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:external,internal'],
            'phone' => ['required', 'string', 'max:30'],
            'phone2' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'commercial_register' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
