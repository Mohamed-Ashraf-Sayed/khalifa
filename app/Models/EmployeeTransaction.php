<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeTransaction extends Model
{
    protected $fillable = [
        'employee_id', 'type', 'amount', 'transaction_date',
        'project_id', 'description', 'notes', 'created_by',
        'reference_type', 'reference_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    public const TYPES = [
        'salary' => 'راتب',
        'advance' => 'سلفة',
        'advance_return' => 'سداد سلفة',
        'custody' => 'عهدة',
        'custody_return' => 'رد عهدة',
        'custody_expense' => 'صرف من العهدة',
        'bonus' => 'مكافأة',
        'deduction' => 'خصم',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
