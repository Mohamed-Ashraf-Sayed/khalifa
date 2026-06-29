<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollItemTest extends TestCase
{
    use RefreshDatabase;

    /** ينشئ بند مسيّر مرتبط بمسيّر وموظف بالقيم المطلوبة. */
    private function item(array $overrides = []): PayrollItem
    {
        $run = PayrollRun::create([
            'run_number' => 'PR-'.uniqid(),
            'period_year' => 2026,
            'period_month' => 6,
            'status' => 'draft',
            'created_by' => User::factory()->create()->id,
        ]);

        $employee = Employee::create([
            'employee_code' => 'EMP-'.uniqid(),
            'name' => 'موظف اختبار',
            'job_title' => 'محاسب',
            'salary' => '5000.00',
            'hire_date' => '2026-01-01',
            'is_active' => true,
        ]);

        return PayrollItem::create(array_merge([
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'basic_salary' => '0',
            'allowances' => '0',
            'bonus' => '0',
            'deductions' => '0',
            'advance_deduction' => '0',
        ], $overrides));
    }

    public function test_computes_net_as_basic_plus_allowances_plus_bonus_minus_deductions_minus_advance(): void
    {
        $item = $this->item([
            'basic_salary' => '5000.00',
            'allowances' => '1500.50',
            'bonus' => '750.25',
            'deductions' => '300.75',
            'advance_deduction' => '450.00',
        ]);

        // (5000.00 + 1500.50 + 750.25) - 300.75 - 450.00 = 6500.00
        $this->assertSame('6500.00', $item->computeNet());
    }

    public function test_computes_net_with_bcmath_two_decimal_precision(): void
    {
        $item = $this->item([
            'basic_salary' => '0.10',
            'allowances' => '0.20',
            'bonus' => '0.01',
            'deductions' => '0.05',
            'advance_deduction' => '0.03',
        ]);

        // (0.10 + 0.20 + 0.01) - 0.05 - 0.03 = 0.23 — لا أخطاء عائمة
        $this->assertSame('0.23', $item->computeNet());
    }

    public function test_net_is_zero_when_deductions_exactly_equal_gross(): void
    {
        $item = $this->item([
            'basic_salary' => '2000.00',
            'allowances' => '500.00',
            'bonus' => '0',
            'deductions' => '2000.00',
            'advance_deduction' => '500.00',
        ]);

        // (2000.00 + 500.00 + 0) - 2000.00 - 500.00 = 0.00
        $this->assertSame('0.00', $item->computeNet());
    }

    public function test_net_goes_negative_when_deductions_exceed_gross(): void
    {
        $item = $this->item([
            'basic_salary' => '1000.00',
            'allowances' => '0',
            'bonus' => '0',
            'deductions' => '800.00',
            'advance_deduction' => '500.00',
        ]);

        // (1000.00) - 800.00 - 500.00 = -300.00 — لا يوجد قصّ للصفر في computeNet
        $this->assertSame('-300.00', $item->computeNet());
    }

    public function test_net_with_only_basic_salary(): void
    {
        $item = $this->item(['basic_salary' => '3200.00']);

        $this->assertSame('3200.00', $item->computeNet());
    }
}
