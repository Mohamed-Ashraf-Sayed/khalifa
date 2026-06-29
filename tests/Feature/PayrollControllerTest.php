<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Employee;
use App\Models\EmployeeTransaction;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PayrollControllerTest extends TestCase
{
    use RefreshDatabase;

    /** مستخدم مُصادَق عليه ومعه صلاحية employees.edit (المطلوبة لـ approve/pay). */
    private function actor(): User
    {
        Permission::firstOrCreate(['name' => 'employees.edit', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->givePermissionTo('employees.edit');

        return $user;
    }

    private function bankAccount(string $opening = '100000'): BankAccount
    {
        return BankAccount::create([
            'name' => 'حساب الرواتب', 'bank_name' => 'بنك مصر', 'currency' => 'EGP',
            'opening_balance' => $opening, 'current_balance' => $opening, 'is_active' => true,
        ]);
    }

    private function employee(string $salary = '5000'): Employee
    {
        return Employee::create([
            'employee_code' => 'EMP-'.fake()->unique()->numberBetween(1000, 9999),
            'name' => 'محمد علي',
            'job_title' => 'مهندس',
            'salary' => $salary,
            'hire_date' => '2025-01-01',
            'is_active' => true,
        ]);
    }

    /**
     * يبني مسيّر رواتب ببند واحد للموظف المعطى. الحالة draft افتراضياً.
     */
    private function runWithItem(User $creator, Employee $employee, string $status = 'draft', string $advanceDeduction = '0'): PayrollRun
    {
        $run = PayrollRun::create([
            'run_number' => 'PR-2026-06-'.fake()->unique()->numberBetween(1, 999),
            'period_year' => 2026,
            'period_month' => 6,
            'status' => $status,
            'total_net' => 0,
            'created_by' => $creator->id,
        ]);

        $item = new PayrollItem([
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'basic_salary' => $employee->salary,
            'allowances' => 0,
            'bonus' => 0,
            'deductions' => 0,
            'advance_deduction' => $advanceDeduction,
        ]);
        $item->net_salary = $item->computeNet();
        $item->save();

        $run->load('items');
        $run->recomputeTotal();

        return $run->fresh();
    }

    public function test_pay_is_rejected_when_run_is_still_draft(): void
    {
        $user = $this->actor();
        $employee = $this->employee('5000');
        $bank = $this->bankAccount('100000');
        $run = $this->runWithItem($user, $employee, 'draft');

        $response = $this->actingAs($user)
            ->post(route('payroll.pay', $run), ['bank_account_id' => $bank->id]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // الحالة ما اتغيرتش والبند مادُفعش وما اتسجلتش حركة بنكية
        $this->assertSame('draft', $run->fresh()->status);
        $this->assertFalse((bool) $run->items->first()->fresh()->paid);
        $this->assertDatabaseCount('bank_transactions', 0);
        $this->assertSame('100000.00', $bank->fresh()->deriveBalance());
    }

    public function test_pay_requires_a_bank_account_id(): void
    {
        $user = $this->actor();
        $employee = $this->employee('5000');
        $run = $this->runWithItem($user, $employee, 'approved');

        $response = $this->actingAs($user)
            ->post(route('payroll.pay', $run), []);

        $response->assertSessionHasErrors('bank_account_id');

        // فشل التحقق => مفيش صرف حصل
        $this->assertSame('approved', $run->fresh()->status);
        $this->assertDatabaseCount('bank_transactions', 0);
    }

    public function test_approve_then_pay_posts_withdrawals_and_marks_paid(): void
    {
        $user = $this->actor();
        $employee = $this->employee('5000');
        $bank = $this->bankAccount('100000');
        $run = $this->runWithItem($user, $employee, 'draft');

        // 1) اعتماد المسيّر
        $approve = $this->actingAs($user)->post(route('payroll.approve', $run));
        $approve->assertRedirect();
        $approve->assertSessionHas('success');

        $run->refresh();
        $this->assertSame('approved', $run->status);
        $this->assertSame($user->id, $run->approved_by);
        $this->assertNotNull($run->approved_at);

        // 2) صرف المسيّر
        $pay = $this->actingAs($user)->post(route('payroll.pay', $run), ['bank_account_id' => $bank->id]);
        $pay->assertRedirect();
        $pay->assertSessionHas('success');

        $run->refresh();
        $this->assertSame('paid', $run->status);
        $this->assertNotNull($run->paid_at);
        $this->assertSame($bank->id, $run->bank_account_id);

        // البند اتعلّم مدفوع
        $item = $run->items()->first();
        $this->assertTrue((bool) $item->paid);

        // حركة سحب بنكية بقيمة صافي الراتب مربوطة بالبند
        $this->assertDatabaseHas('bank_transactions', [
            'bank_account_id' => $bank->id,
            'type' => 'withdrawal',
            'amount' => '5000.00',
            'related_type' => 'payroll_item',
            'related_id' => $item->id,
            'created_by' => $user->id,
        ]);

        // الرصيد المشتقّ نقص بمقدار صافي الراتب
        $this->assertSame('95000.00', $bank->fresh()->deriveBalance());

        // حركة راتب على الموظف
        $this->assertDatabaseHas('employee_transactions', [
            'employee_id' => $employee->id,
            'type' => 'salary',
            'amount' => '5000.00',
        ]);
    }

    public function test_pay_with_advance_deduction_records_advance_return(): void
    {
        $user = $this->actor();
        $employee = $this->employee('5000');
        $bank = $this->bankAccount('100000');
        // خصم سلفة 1000 => صافي = 4000
        $run = $this->runWithItem($user, $employee, 'approved', '1000');

        $pay = $this->actingAs($user)->post(route('payroll.pay', $run), ['bank_account_id' => $bank->id]);
        $pay->assertSessionHas('success');

        $this->assertSame('paid', $run->fresh()->status);

        // السحب البنكي بقيمة الصافي (4000) مش الأساسي
        $this->assertDatabaseHas('bank_transactions', [
            'bank_account_id' => $bank->id,
            'type' => 'withdrawal',
            'amount' => '4000.00',
        ]);

        // اتسجلت حركة سداد سلفة بقيمة الخصم
        $this->assertDatabaseHas('employee_transactions', [
            'employee_id' => $employee->id,
            'type' => 'advance_return',
            'amount' => '1000.00',
        ]);

        // والراتب نفسه اتسجل بالصافي
        $this->assertDatabaseHas('employee_transactions', [
            'employee_id' => $employee->id,
            'type' => 'salary',
            'amount' => '4000.00',
        ]);
    }

    public function test_pay_is_forbidden_without_permission(): void
    {
        $user = User::factory()->create(); // مفيش صلاحية employees.edit ولا دور admin
        $employee = $this->employee('5000');
        $bank = $this->bankAccount('100000');
        $run = $this->runWithItem($user, $employee, 'approved');

        $response = $this->actingAs($user)
            ->post(route('payroll.pay', $run), ['bank_account_id' => $bank->id]);

        $response->assertForbidden();

        // مفيش صرف اتم
        $this->assertSame('approved', $run->fresh()->status);
        $this->assertDatabaseCount('bank_transactions', 0);
    }
}
