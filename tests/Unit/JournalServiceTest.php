<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Services\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalServiceTest extends TestCase
{
    use RefreshDatabase;

    private function accounts(): array
    {
        $cash = Account::create(['code' => '1101', 'name' => 'نقدية', 'type' => 'asset', 'is_group' => false, 'normal_balance' => 'debit', 'opening_balance' => 0, 'is_active' => true]);
        $rev = Account::create(['code' => '4101', 'name' => 'إيراد', 'type' => 'revenue', 'is_group' => false, 'normal_balance' => 'credit', 'opening_balance' => 0, 'is_active' => true]);

        return [$cash, $rev];
    }

    public function test_creates_balanced_entry(): void
    {
        [$cash, $rev] = $this->accounts();
        $entry = app(JournalService::class)->createEntry(
            ['entry_date' => '2026-01-01', 'description' => 'قيد متوازن'],
            [
                ['account_id' => $cash->id, 'debit' => '100', 'credit' => 0],
                ['account_id' => $rev->id, 'debit' => 0, 'credit' => '100'],
            ],
        );

        $this->assertTrue($entry->isBalanced());
        $this->assertSame('100.00', (string) $entry->total_debit);
        $this->assertSame('100.00', (string) $entry->total_credit);
    }

    public function test_rejects_unbalanced_entry(): void
    {
        [$cash, $rev] = $this->accounts();
        $this->expectException(\InvalidArgumentException::class);
        app(JournalService::class)->createEntry(
            ['entry_date' => '2026-01-01', 'description' => 'غير متوازن'],
            [
                ['account_id' => $cash->id, 'debit' => '100', 'credit' => 0],
                ['account_id' => $rev->id, 'debit' => 0, 'credit' => '50'],
            ],
        );
    }

    public function test_rejects_entry_with_less_than_two_lines(): void
    {
        [$cash] = $this->accounts();
        $this->expectException(\InvalidArgumentException::class);
        app(JournalService::class)->createEntry(
            ['entry_date' => '2026-01-01', 'description' => 'بند واحد'],
            [['account_id' => $cash->id, 'debit' => '100', 'credit' => 0]],
        );
    }

    public function test_rejects_line_with_both_debit_and_credit(): void
    {
        [$cash, $rev] = $this->accounts();
        $this->expectException(\InvalidArgumentException::class);
        app(JournalService::class)->createEntry(
            ['entry_date' => '2026-01-01', 'description' => 'بند بمدين ودائن'],
            [
                ['account_id' => $cash->id, 'debit' => '100', 'credit' => '100'],
                ['account_id' => $rev->id, 'debit' => 0, 'credit' => '100'],
            ],
        );
    }

    public function test_post_marks_entry_posted(): void
    {
        [$cash, $rev] = $this->accounts();
        $svc = app(JournalService::class);
        $entry = $svc->createEntry(
            ['entry_date' => '2026-01-01', 'description' => 'للترحيل'],
            [
                ['account_id' => $cash->id, 'debit' => '100', 'credit' => 0],
                ['account_id' => $rev->id, 'debit' => 0, 'credit' => '100'],
            ],
        );
        $this->assertSame('draft', $entry->status);
        $svc->post($entry);
        $this->assertSame('posted', $entry->fresh()->status);
    }
}
