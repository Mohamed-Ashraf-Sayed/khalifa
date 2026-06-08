<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Services\BankLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            'attachment' => ['nullable', 'file', 'max:8192', 'mimes:pdf,jpg,jpeg,png,webp,docx,xlsx'],
        ]);
        $data['category'] = $data['category'] ?? 'general';
        $data['created_by'] = $request->user()->id;

        // رفع المرفق (إن وُجد) على القرص الخاص — غير قابل للوصول المباشر
        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('bank-transactions', 'local');
        } else {
            unset($data['attachment']);
        }

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

    public function downloadAttachment(BankTransaction $bank_transaction): StreamedResponse
    {
        abort_unless($bank_transaction->attachment, 404);
        abort_unless(Storage::disk('local')->exists($bank_transaction->attachment), 404);

        $name = 'transaction-'.$bank_transaction->id.'.'.pathinfo($bank_transaction->attachment, PATHINFO_EXTENSION);

        return Storage::disk('local')->download($bank_transaction->attachment, $name);
    }

    public function destroy(BankTransaction $bank_transaction): RedirectResponse
    {
        // حذف ملف المرفق المرتبط (إن وُجد) قبل حذف الحركة — لا يمسّ منطق الرصيد
        if ($bank_transaction->attachment && Storage::disk('local')->exists($bank_transaction->attachment)) {
            Storage::disk('local')->delete($bank_transaction->attachment);
        }

        $this->ledger->deleteTransaction($bank_transaction);

        return back()->with('success', 'تم حذف الحركة وتحديث الرصيد.');
    }
}
