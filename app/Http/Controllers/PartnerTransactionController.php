<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\PartnerTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class PartnerTransactionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:partners.view', only: ['index', 'show']),
            new Middleware('can:partners.create', only: ['create', 'store']),
            new Middleware('can:partners.edit', only: ['edit', 'update']),
            new Middleware('can:partners.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $type = (string) $request->input('type', '');

        $transactions = PartnerTransaction::query()
            ->with('partner')
            ->when($search !== '', fn ($q) => $q->where('description', 'like', "%{$search}%"))
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->latest('transaction_date')
            ->paginate(15)
            ->withQueryString();

        return view('partner_transactions.index', compact('transactions', 'search', 'type'));
    }

    public function show(PartnerTransaction $partner_transaction): View
    {
        $partner_transaction->load(['partner', 'creator']);

        return view('partner_transactions.show', ['transaction' => $partner_transaction]);
    }

    public function create(): View
    {
        return view('partner_transactions.form', [
            'transaction' => new PartnerTransaction(['type' => 'deposit']),
            'partners' => Partner::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        PartnerTransaction::create($data);

        return redirect()->route('partner_transactions.index')->with('success', 'تمت إضافة الحركة بنجاح.');
    }

    public function edit(PartnerTransaction $partnerTransaction): View
    {
        return view('partner_transactions.form', [
            'transaction' => $partnerTransaction,
            'partners' => Partner::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, PartnerTransaction $partnerTransaction): RedirectResponse
    {
        $partnerTransaction->update($this->validateData($request));

        return redirect()->route('partner_transactions.index')->with('success', 'تم تحديث الحركة.');
    }

    public function destroy(PartnerTransaction $partnerTransaction): RedirectResponse
    {
        $partnerTransaction->delete();

        return back()->with('success', 'تم حذف الحركة.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'partner_id' => ['required', 'exists:partners,id'],
            'type' => ['required', 'in:'.implode(',', array_keys(PartnerTransaction::TYPES))],
            'amount' => ['required', 'numeric', 'gt:0'],
            'transaction_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
