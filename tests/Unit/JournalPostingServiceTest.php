<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Services\JournalPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalPostingServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * يزرع حسابات الشجرة التي يحتاجها buildExpense لمصروف مواد نقدي:
     * مدين تكلفة المواد (5102) / دائن النقدية (1101).
     */
    private function seedExpenseAccounts(): void
    {
        Account::create(['code' => Account::CODES['material_cost'], 'name' => 'تكلفة مواد', 'type' => 'expense', 'is_group' => false, 'normal_balance' => 'debit', 'opening_balance' => 0, 'is_active' => true]);
        Account::create(['code' => Account::CODES['cash'], 'name' => 'النقدية', 'type' => 'asset', 'is_group' => false, 'normal_balance' => 'debit', 'opening_balance' => 0, 'is_active' => true]);
    }

    private function cashExpense(): Expense
    {
        return Expense::create([
            'category' => 'materials',
            'description' => 'شراء أسمنت',
            'amount' => '500.00',
            'expense_date' => '2026-01-10',
            'payment_method' => 'cash',
            'is_credit' => false,
            'paid_amount' => '500.00',
            'payment_status' => 'paid',
        ]);
    }

    public function test_post_document_is_idempotent_only_one_entry_for_same_document(): void
    {
        $this->seedExpenseAccounts();
        $expense = $this->cashExpense();
        $svc = app(JournalPostingService::class);

        $first = $svc->postDocument('expense', $expense);
        $second = $svc->postDocument('expense', $expense);

        $this->assertInstanceOf(JournalEntry::class, $first);
        $this->assertNull($second, 'الترحيل الثاني لنفس المستند لازم يرجّع null (idempotent).');
        $this->assertSame(1, JournalEntry::where('reference_type', 'expense')->where('reference_id', $expense->id)->count());
        $this->assertSame(1, JournalEntry::count());
    }

    public function test_posted_entry_is_balanced(): void
    {
        $this->seedExpenseAccounts();
        $expense = $this->cashExpense();

        $entry = app(JournalPostingService::class)->postDocument('expense', $expense);

        $this->assertNotNull($entry);
        $this->assertTrue($entry->isBalanced());
        $this->assertSame('posted', $entry->status);
        $this->assertSame('500.00', (string) $entry->total_debit);
        $this->assertSame('500.00', (string) $entry->total_credit);
        $this->assertSame('auto', $entry->source);
    }

    public function test_post_document_returns_null_when_document_has_no_date(): void
    {
        $this->seedExpenseAccounts();
        $expense = $this->cashExpense();
        // تاريخ مفقود في الذاكرة → buildExpense يرجّع null للتاريخ → لا يُنشأ قيد
        $expense->expense_date = null;

        $entry = app(JournalPostingService::class)->postDocument('expense', $expense);

        $this->assertNull($entry);
        $this->assertSame(0, JournalEntry::count());
    }

    public function test_post_document_returns_null_when_fewer_than_two_lines(): void
    {
        $this->seedExpenseAccounts();
        $expense = $this->cashExpense();
        // نوع غير مدعوم → فرع default يرجّع [null, '', []] → أقل من بندين

        $entry = app(JournalPostingService::class)->postDocument('unsupported_type', $expense);

        $this->assertNull($entry);
        $this->assertSame(0, JournalEntry::count());
    }
}
