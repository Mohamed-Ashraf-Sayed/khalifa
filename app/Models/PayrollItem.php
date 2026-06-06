<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_run_id', 'employee_id', 'basic_salary', 'allowances',
        'bonus', 'deductions', 'advance_deduction', 'net_salary', 'paid',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'allowances' => 'decimal:2',
            'bonus' => 'decimal:2',
            'deductions' => 'decimal:2',
            'advance_deduction' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'paid' => 'boolean',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** صافي الراتب = (الأساسي + البدلات + المكافأة) − الخصومات − خصم السلفة. */
    public function computeNet(): string
    {
        return bcsub(
            bcsub(
                bcadd(bcadd((string) $this->basic_salary, (string) $this->allowances, 2), (string) $this->bonus, 2),
                (string) $this->deductions,
                2
            ),
            (string) $this->advance_deduction,
            2
        );
    }
}
