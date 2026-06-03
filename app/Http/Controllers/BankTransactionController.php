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
            'category' => ['nullable', 'in:'.implode(',', array_keys(BankTransaction::CATEGORIES))],
            'beneficiary' => ['nullable', 'string', 'max:150'],
            'check_number' => ['nullable', 'string', 'max:50'],
            'value_date' => ['nullable', 'date'],
        ]);
        $data['category'] = $data['category'] ?? 'general';
        $data['created_by'] = $request->user()->id;

        $this->ledger->post($bank_account, $data);

        return back()->with('success', 'تم تسجيل الحركة وتحديث الرصيد.');
    }

    public function reconcile(BankTransaction $bank_transaction): RedirectResponse
    {
        // تبديل حالة المطابقة فقط — لا يمسّ الرصيد إطلاقاً
        $bank_transaction->is_reconciled = ! $bank_transaction->is_reconciled;
        $bank_transaction->save();

        return back()->with('success', $bank_transaction->is_reconciled ? 'تمت المطابقة.' : 'تم إلغاء المطابقة.');
    }

    public function destroy(BankTransaction $bank_transaction): RedirectResponse
    {
        $this->ledger->deleteTransaction($bank_transaction);

        return back()->with('success', 'تم حذف الحركة وتحديث الرصيد.');
    }
}
