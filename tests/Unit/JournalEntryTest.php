<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalEntryTest extends TestCase
{
    use RefreshDatabase;

    private function accounts(): array
    {
        $cash = Account::create(['code' => '1101', 'name' => 'نقدية', 'type' => 'asset', 'is_group' => false, 'normal_balance' => 'debit', 'opening_balance' => 0, 'is_active' => true]);
        $rev = Account::create(['code' => '4101', 'name' => 'إيراد', 'type' => 'revenue', 'is_group' => false, 'normal_balance' => 'credit', 'opening_balance' => 0, 'is_active' => true]);

        return [$cash, $rev];
    }

    private function entry(string $number = 'JE-0001'): JournalEntry
    {
        return JournalEntry::create([
            'entry_number' => $number,
            'entry_date' => '2026-01-01',
            'description' => 'قيد اختبار',
            'status' => 'draft',
        ]);
    }

    private function line(JournalEntry $entry, int $accountId, string $debit, string $credit): JournalEntryLine
    {
        return JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $accountId,
            'debit' => $debit,
            'credit' => $credit,
        ]);
    }

    public function test_recompute_totals_sums_lines(): void
    {
        [$cash, $rev] = $this->accounts();
        $entry = $this->entry();

        $this->line($entry, $cash->id, '100.00', '0');
        $this->line($entry, $cash->id, '50.50', '0');
        $this->line($entry, $rev->id, '0', '150.50');

        $entry->recomputeTotals();

        $fresh = $entry->fresh();
        $this->assertSame('150.50', (string) $fresh->total_debit);
        $this->assertSame('150.50', (string) $fresh->total_credit);
    }

    public function test_recompute_totals_with_no_lines_is_zero(): void
    {
        $entry = $this->entry();

        $entry->recomputeTotals();

        $fresh = $entry->fresh();
        $this->assertSame('0.00', (string) $fresh->total_debit);
        $this->assertSame('0.00', (string) $fresh->total_credit);
    }

    public function test_is_balanced_true_when_debit_equals_credit_above_zero(): void
    {
        [$cash, $rev] = $this->accounts();
        $entry = $this->entry();

        $this->line($entry, $cash->id, '200.00', '0');
        $this->line($entry, $rev->id, '0', '200.00');

        $entry->recomputeTotals();

        $this->assertTrue($entry->isBalanced());
    }

    public function test_is_balanced_false_when_debit_not_equal_credit(): void
    {
        [$cash, $rev] = $this->accounts();
        $entry = $this->entry();

        $this->line($entry, $cash->id, '200.00', '0');
        $this->line($entry, $rev->id, '0', '150.00');

        $entry->recomputeTotals();

        $this->assertFalse($entry->isBalanced());
    }

    public function test_is_balanced_false_when_both_zero(): void
    {
        $entry = $this->entry();

        $entry->recomputeTotals();

        $this->assertSame('0.00', (string) $entry->total_debit);
        $this->assertSame('0.00', (string) $entry->total_credit);
        $this->assertFalse($entry->isBalanced());
    }
}
