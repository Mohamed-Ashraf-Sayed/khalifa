<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountBalanceTest extends TestCase
{
    use RefreshDatabase;

    private int $entrySeq = 0;

    /** إنشاء قيد مُرحَّل (posted) ببنوده مباشرةً. */
    private function postedEntry(array $lines, string $date = '2026-01-01'): JournalEntry
    {
        $entry = JournalEntry::create([
            'entry_number' => 'JE-'.(++$this->entrySeq),
            'entry_date' => $date,
            'description' => 'قيد مُرحَّل للاختبار',
            'status' => 'posted',
            'total_debit' => 0,
            'total_credit' => 0,
        ]);

        foreach ($lines as $line) {
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $line['account_id'],
                'debit' => $line['debit'] ?? '0',
                'credit' => $line['credit'] ?? '0',
            ]);
        }

        $entry->load('lines')->recomputeTotals();

        return $entry;
    }

    public function test_debit_normal_account_balance_is_opening_plus_debit_minus_credit(): void
    {
        $cash = Account::create([
            'code' => '1101', 'name' => 'نقدية', 'type' => 'asset', 'is_group' => false,
            'normal_balance' => 'debit', 'opening_balance' => '100.00', 'is_active' => true,
        ]);
        $rev = Account::create([
            'code' => '4101', 'name' => 'إيراد', 'type' => 'revenue', 'is_group' => false,
            'normal_balance' => 'credit', 'opening_balance' => '0.00', 'is_active' => true,
        ]);

        // قيد مُرحَّل: مدين 500 على النقدية / دائن 500 على الإيراد
        $this->postedEntry([
            ['account_id' => $cash->id, 'debit' => '500'],
            ['account_id' => $rev->id, 'credit' => '500'],
        ]);
        // قيد مُرحَّل ثانٍ: دائن 200 على النقدية (يقلّل رصيدها المدين)
        $this->postedEntry([
            ['account_id' => $rev->id, 'debit' => '200'],
            ['account_id' => $cash->id, 'credit' => '200'],
        ]);

        // مدين: 100 + (500 - 200) = 400.00
        $this->assertSame('400.00', $cash->fresh()->balance());
    }

    public function test_credit_normal_account_balance_is_opening_plus_credit_minus_debit(): void
    {
        $cash = Account::create([
            'code' => '1101', 'name' => 'نقدية', 'type' => 'asset', 'is_group' => false,
            'normal_balance' => 'debit', 'opening_balance' => '0.00', 'is_active' => true,
        ]);
        $rev = Account::create([
            'code' => '4101', 'name' => 'إيراد', 'type' => 'revenue', 'is_group' => false,
            'normal_balance' => 'credit', 'opening_balance' => '50.00', 'is_active' => true,
        ]);

        $this->postedEntry([
            ['account_id' => $cash->id, 'debit' => '500'],
            ['account_id' => $rev->id, 'credit' => '500'],
        ]);
        $this->postedEntry([
            ['account_id' => $rev->id, 'debit' => '200'],
            ['account_id' => $cash->id, 'credit' => '200'],
        ]);

        // دائن: 50 + (500 - 200) = 350.00
        $this->assertSame('350.00', $rev->fresh()->balance());
    }

    public function test_contra_asset_account_uses_normal_balance_credit_not_type(): void
    {
        // حساب مقابل (contra): نوعه asset لكن طبيعته دائنة (مثل مجمّع الإهلاك)
        $accumDep = Account::create([
            'code' => '1202', 'name' => 'مجمّع الإهلاك', 'type' => 'asset', 'is_group' => false,
            'normal_balance' => 'credit', 'opening_balance' => '0.00', 'is_active' => true,
        ]);
        $depExpense = Account::create([
            'code' => '5205', 'name' => 'مصروف الإهلاك', 'type' => 'expense', 'is_group' => false,
            'normal_balance' => 'debit', 'opening_balance' => '0.00', 'is_active' => true,
        ]);

        // قيد إهلاك مُرحَّل: مدين 300 على المصروف / دائن 300 على مجمّع الإهلاك
        $this->postedEntry([
            ['account_id' => $depExpense->id, 'debit' => '300'],
            ['account_id' => $accumDep->id, 'credit' => '300'],
        ]);

        // لو كان البرنامج يحسب حسب type=asset (مدين) لكانت النتيجة سالبة (-300.00).
        // الصحيح أنه يحسب حسب normal_balance=credit ⇒ موجب 300.00
        $this->assertSame('300.00', $accumDep->fresh()->balance());
        $this->assertNotSame('-300.00', $accumDep->fresh()->balance());
    }

    public function test_draft_entries_are_excluded_from_balance(): void
    {
        $cash = Account::create([
            'code' => '1101', 'name' => 'نقدية', 'type' => 'asset', 'is_group' => false,
            'normal_balance' => 'debit', 'opening_balance' => '0.00', 'is_active' => true,
        ]);
        $rev = Account::create([
            'code' => '4101', 'name' => 'إيراد', 'type' => 'revenue', 'is_group' => false,
            'normal_balance' => 'credit', 'opening_balance' => '0.00', 'is_active' => true,
        ]);

        // قيد مُرحَّل: +1000 على النقدية
        $this->postedEntry([
            ['account_id' => $cash->id, 'debit' => '1000'],
            ['account_id' => $rev->id, 'credit' => '1000'],
        ]);

        // قيد مسودة (draft) يجب ألا يدخل في الرصيد المُرحَّل
        $draft = JournalEntry::create([
            'entry_number' => 'JE-DRAFT', 'entry_date' => '2026-01-02',
            'description' => 'مسودة', 'status' => 'draft', 'total_debit' => 0, 'total_credit' => 0,
        ]);
        JournalEntryLine::create(['journal_entry_id' => $draft->id, 'account_id' => $cash->id, 'debit' => '777', 'credit' => '0']);
        JournalEntryLine::create(['journal_entry_id' => $draft->id, 'account_id' => $rev->id, 'debit' => '0', 'credit' => '777']);

        // المرحّل فقط: 1000.00
        $this->assertSame('1000.00', $cash->fresh()->balance());
    }

    public function test_balance_respects_as_of_date(): void
    {
        $cash = Account::create([
            'code' => '1101', 'name' => 'نقدية', 'type' => 'asset', 'is_group' => false,
            'normal_balance' => 'debit', 'opening_balance' => '0.00', 'is_active' => true,
        ]);
        $rev = Account::create([
            'code' => '4101', 'name' => 'إيراد', 'type' => 'revenue', 'is_group' => false,
            'normal_balance' => 'credit', 'opening_balance' => '0.00', 'is_active' => true,
        ]);

        $this->postedEntry([
            ['account_id' => $cash->id, 'debit' => '400'],
            ['account_id' => $rev->id, 'credit' => '400'],
        ], '2026-01-10');
        $this->postedEntry([
            ['account_id' => $cash->id, 'debit' => '600'],
            ['account_id' => $rev->id, 'credit' => '600'],
        ], '2026-02-10');

        // حتى 2026-01-31 يُحتسب أول قيد فقط
        $this->assertSame('400.00', $cash->fresh()->balance('2026-01-31'));
        // بدون asOf يُحتسب الكل
        $this->assertSame('1000.00', $cash->fresh()->balance());
    }
}
