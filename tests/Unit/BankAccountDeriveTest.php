<?php

namespace Tests\Unit;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankAccountDeriveTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $opening = '0'): BankAccount
    {
        return BankAccount::create([
            'name' => 'حساب اشتقاق', 'bank_name' => 'بنك', 'currency' => 'EGP',
            'opening_balance' => $opening, 'current_balance' => $opening, 'is_active' => true,
        ]);
    }

    private function tx(BankAccount $acc, string $type, string $amount): void
    {
        BankTransaction::create([
            'bank_account_id' => $acc->id,
            'type' => $type,
            'amount' => $amount,
            'transaction_date' => '2026-01-01',
            'description' => 'حركة اختبار',
        ]);
    }

    /** بدون أي حركات: الرصيد المشتقّ = الافتتاحي منسّقاً بخانتين عشريتين. */
    public function test_no_transactions_returns_opening_balance_formatted(): void
    {
        $acc = $this->account('1500');

        $this->assertSame('1500.00', $acc->fresh()->deriveBalance());
    }

    /** افتتاحي صفر وبدون حركات يرجّع 0.00 وليس 0. */
    public function test_zero_opening_and_no_transactions_returns_zero_formatted(): void
    {
        $acc = $this->account('0');

        $this->assertSame('0.00', $acc->fresh()->deriveBalance());
    }

    /** السحوبات أكبر من الإيداعات تنتج رصيداً سالباً كنص. */
    public function test_withdrawals_exceeding_deposits_returns_negative_balance(): void
    {
        $acc = $this->account('100');
        $this->tx($acc, 'deposit', '50');      // +50
        $this->tx($acc, 'withdrawal', '400');  // -400

        // 100 + 50 - 400 = -250.00
        $this->assertSame('-250.00', $acc->fresh()->deriveBalance());
    }

    /** سحب واحد من رصيد افتتاحي صفر يعطي سالباً صريحاً. */
    public function test_single_withdrawal_from_zero_opening_is_negative(): void
    {
        $acc = $this->account('0');
        $this->tx($acc, 'withdrawal', '75.50');

        $this->assertSame('-75.50', $acc->fresh()->deriveBalance());
    }

    /** دقّة bcmath بخانتين: 0.10 + 0.20 = 0.30 بالضبط (مش 0.30000000000000004). */
    public function test_bcmath_two_decimal_precision_with_fractional_amounts(): void
    {
        $acc = $this->account('0.10');
        $this->tx($acc, 'deposit', '0.20');

        $derived = $acc->fresh()->deriveBalance();

        $this->assertSame('0.30', $derived);
        // تأكيد إن النتيجة نصّ مضبوط بخانتين وليست تقريب float
        $this->assertNotSame((string) (0.1 + 0.2), $derived);
    }

    /** تراكم كسور متعددة يفضل دقيقاً بخانتين عشريتين. */
    public function test_multiple_fractional_deposits_stay_precise(): void
    {
        $acc = $this->account('0');
        $this->tx($acc, 'deposit', '0.10');
        $this->tx($acc, 'deposit', '0.10');
        $this->tx($acc, 'deposit', '0.10');

        // 0.10 * 3 = 0.30
        $this->assertSame('0.30', $acc->fresh()->deriveBalance());
    }
}
