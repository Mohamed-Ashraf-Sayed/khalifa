<?php

namespace Tests\Unit;

use App\Models\BankAccount;
use App\Services\BankLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankLedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $opening = '1000'): BankAccount
    {
        return BankAccount::create([
            'name' => 'حساب اختبار', 'bank_name' => 'بنك', 'currency' => 'EGP',
            'opening_balance' => $opening, 'current_balance' => $opening, 'is_active' => true,
        ]);
    }

    public function test_deposit_then_withdrawal_derives_correct_balance(): void
    {
        $acc = $this->account('1000');
        $svc = app(BankLedgerService::class);

        $svc->post($acc, ['type' => 'deposit', 'amount' => '500', 'transaction_date' => '2026-01-01', 'description' => 'إيداع']);
        $svc->post($acc, ['type' => 'withdrawal', 'amount' => '200', 'transaction_date' => '2026-01-02', 'description' => 'سحب']);

        // 1000 + 500 - 200 = 1300
        $this->assertSame('1300.00', $acc->fresh()->deriveBalance());
    }

    public function test_balance_is_independent_of_transaction_date_order(): void
    {
        $acc = $this->account('0');
        $svc = app(BankLedgerService::class);

        // سحب مؤرّخ قبل الإيداع — المشتق لازم يجمع بغضّ النظر عن الترتيب
        $svc->post($acc, ['type' => 'withdrawal', 'amount' => '300', 'transaction_date' => '2026-01-05', 'description' => 'سحب']);
        $svc->post($acc, ['type' => 'deposit', 'amount' => '1000', 'transaction_date' => '2026-01-01', 'description' => 'إيداع']);

        $this->assertSame('700.00', $acc->fresh()->deriveBalance());
    }

    public function test_post_updates_stored_current_balance_to_match_derived(): void
    {
        $acc = $this->account('100');
        $svc = app(BankLedgerService::class);

        $svc->post($acc, ['type' => 'deposit', 'amount' => '50', 'transaction_date' => '2026-02-01', 'description' => 'إيداع']);

        $fresh = $acc->fresh();
        $this->assertSame($fresh->deriveBalance(), number_format((float) $fresh->current_balance, 2, '.', ''));
    }
}
