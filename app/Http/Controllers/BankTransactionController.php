<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Services\BankLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class BankTransactionController extends Controller implements HasMiddleware
{
    public function __construct(private readonly BankLedgerService $ledger) {}

    public static function middleware(): array
    {
        // إضافة/حذف حركة = تعديل على الحساب
        return [new Middleware('can:bank_accounts.edit')];
    }

    public function store(Request $request, BankAccount $bank_account): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:deposit,withdrawal'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ]);
        $data['created_by'] = $request->user()->id;

        $this->ledger->post($bank_account, $data);

        return back()->with('success', 'تم تسجيل الحركة وتحديث الرصيد.');
    }

    public function destroy(BankTransaction $bank_transaction): RedirectResponse
    {
        $this->ledger->deleteTransaction($bank_transaction);

        return back()->with('success', 'تم حذف الحركة وتحديث الرصيد.');
    }
}
