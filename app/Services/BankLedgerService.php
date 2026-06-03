<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * الخدمة المسؤولة عن كل حركات الأرصدة البنكية.
 *
 * المبدأ المُصلِّح لباجز النظام القديم:
 *  - مفيش عمود "balance_after" مخزّن لكل حركة (ده اللي كان بيتكسر مع ترتيب التواريخ).
 *  - الرصيد الحالي دايماً = الرصيد الافتتاحي + الإيداعات − السحوبات (مشتقّ من المصدر).
 *  - كل تعديل بيتمّ داخل DB transaction مع قفل صف الحساب (lockForUpdate) لمنع تضارب التزامن.
 */
class BankLedgerService
{
    /**
     * تسجيل حركة (إيداع/سحب) وتحديث رصيد الحساب ذرّياً.
     */
    public function post(BankAccount $account, array $data): BankTransaction
    {
        return DB::transaction(function () use ($account, $data) {
            // قفل صف الحساب طوال العملية
            $account = BankAccount::lockForUpdate()->findOrFail($account->id);

            $txn = $account->transactions()->create([
                'type' => $data['type'],
                'amount' => $data['amount'],
                'transaction_date' => $data['transaction_date'],
                'description' => $data['description'],
                'reference_number' => $data['reference_number'] ?? null,
                'related_type' => $data['related_type'] ?? null,
                'related_id' => $data['related_id'] ?? null,
                'created_by' => $data['created_by'] ?? null,
                // مفاتيح اختيارية — لا تؤثر على المستدعين القدامى ولا على اشتقاق الرصيد
                'category' => $data['category'] ?? null,
                'beneficiary' => $data['beneficiary'] ?? null,
                'check_number' => $data['check_number'] ?? null,
                'value_date' => $data['value_date'] ?? null,
                'is_reconciled' => $data['is_reconciled'] ?? false,
                'attachment' => $data['attachment'] ?? null,
            ]);

            $this->refreshBalance($account);

            return $txn;
        });
    }

    /**
     * حذف حركة وإعادة احتساب الرصيد ذرّياً.
     */
    public function deleteTransaction(BankTransaction $txn): void
    {
        DB::transaction(function () use ($txn) {
            $account = BankAccount::lockForUpdate()->findOrFail($txn->bank_account_id);
            $txn->delete();
            $this->refreshBalance($account);
        });
    }

    /**
     * إعادة اشتقاق الرصيد الحالي من المصدر الموثوق وحفظه.
     * يُستخدم بعد أي تعديل، ويصلح أي انحراف سابق إن وُجد.
     */
    public function refreshBalance(BankAccount $account): void
    {
        $account->current_balance = $account->deriveBalance();
        $account->save();
    }

    /**
     * كشف حساب: الحركات مرتّبة ترتيباً حتمياً (التاريخ ثم id) مع رصيد جارٍ
     * محسوب لحظة العرض — مش مخزّن. النتيجة صحيحة مهما كان ترتيب إدخال التواريخ.
     *
     * @return Collection<int, array{txn: BankTransaction, running: string}>
     */
    public function statement(BankAccount $account): Collection
    {
        $running = (string) $account->opening_balance;

        return $account->transactions()
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get()
            ->map(function (BankTransaction $txn) use (&$running) {
                $running = $txn->type === 'deposit'
                    ? bcadd($running, (string) $txn->amount, 2)
                    : bcsub($running, (string) $txn->amount, 2);

                return ['txn' => $txn, 'running' => $running];
            });
    }
}
