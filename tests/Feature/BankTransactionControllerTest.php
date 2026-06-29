<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BankTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    /** مستخدم مصرّح له بتعديل الحسابات البنكية (صلاحية الـ store). */
    private function actingUser(): User
    {
        Permission::findOrCreate('bank_accounts.edit', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo('bank_accounts.edit');

        return $user;
    }

    /** حساب بنكي برصيد افتتاحي معروف. */
    private function account(string $opening = '1000'): BankAccount
    {
        return BankAccount::create([
            'name' => 'حساب اختبار', 'bank_name' => 'بنك', 'currency' => 'EGP',
            'opening_balance' => $opening, 'current_balance' => $opening, 'is_active' => true,
        ]);
    }

    public function test_deposit_increases_derived_balance(): void
    {
        $acc = $this->account('1000');

        $response = $this->actingAs($this->actingUser())->post(
            route('bank_transactions.store', $acc),
            [
                'type' => 'deposit',
                'amount' => '500',
                'transaction_date' => '2026-01-01',
                'description' => 'إيداع',
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $fresh = $acc->fresh();
        // 1000 + 500 = 1500
        $this->assertSame('1500.00', $fresh->deriveBalance());
        $this->assertSame('1500.00', number_format((float) $fresh->current_balance, 2, '.', ''));

        $this->assertDatabaseHas('bank_transactions', [
            'bank_account_id' => $acc->id,
            'type' => 'deposit',
            'amount' => '500.00',
        ]);
    }

    public function test_withdrawal_decreases_derived_balance(): void
    {
        $acc = $this->account('1000');

        $response = $this->actingAs($this->actingUser())->post(
            route('bank_transactions.store', $acc),
            [
                'type' => 'withdrawal',
                'amount' => '300',
                'transaction_date' => '2026-01-02',
                'description' => 'سحب',
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $fresh = $acc->fresh();
        // 1000 - 300 = 700
        $this->assertSame('700.00', $fresh->deriveBalance());
        $this->assertSame('700.00', number_format((float) $fresh->current_balance, 2, '.', ''));

        $this->assertDatabaseHas('bank_transactions', [
            'bank_account_id' => $acc->id,
            'type' => 'withdrawal',
            'amount' => '300.00',
        ]);
    }

    public function test_validation_rejects_non_positive_amount(): void
    {
        $acc = $this->account('1000');

        $response = $this->actingAs($this->actingUser())->post(
            route('bank_transactions.store', $acc),
            [
                'type' => 'deposit',
                'amount' => '0',
                'transaction_date' => '2026-01-01',
                'description' => 'صفر مرفوض',
            ],
        );

        $response->assertSessionHasErrors('amount');

        // مفيش حركة اتسجّلت والرصيد ثابت
        $this->assertDatabaseCount('bank_transactions', 0);
        $this->assertSame('1000.00', $acc->fresh()->deriveBalance());
    }

    public function test_validation_rejects_negative_amount(): void
    {
        $acc = $this->account('1000');

        $response = $this->actingAs($this->actingUser())->post(
            route('bank_transactions.store', $acc),
            [
                'type' => 'withdrawal',
                'amount' => '-50',
                'transaction_date' => '2026-01-01',
                'description' => 'سالب مرفوض',
            ],
        );

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('bank_transactions', 0);
    }

    public function test_validation_rejects_invalid_type(): void
    {
        $acc = $this->account('1000');

        $response = $this->actingAs($this->actingUser())->post(
            route('bank_transactions.store', $acc),
            [
                'type' => 'transfer', // غير مسموح — المسموح فقط deposit/withdrawal
                'amount' => '100',
                'transaction_date' => '2026-01-01',
                'description' => 'نوع غير صالح',
            ],
        );

        $response->assertSessionHasErrors('type');
        $this->assertDatabaseCount('bank_transactions', 0);
        $this->assertSame('1000.00', $acc->fresh()->deriveBalance());
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $acc = $this->account('1000');

        $response = $this->actingAs(User::factory()->create())->post(
            route('bank_transactions.store', $acc),
            [
                'type' => 'deposit',
                'amount' => '500',
                'transaction_date' => '2026-01-01',
                'description' => 'بدون صلاحية',
            ],
        );

        $response->assertForbidden();
        $this->assertDatabaseCount('bank_transactions', 0);
    }
}
