<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tax extends Model
{
    protected $fillable = [
        'name', 'tax_type', 'project_id', 'rate', 'base_amount',
        'amount', 'period', 'due_date', 'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'base_amount' => 'decimal:2',
            'amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public const TYPES = [
        'vat' => 'قيمة مضافة',
        'income' => 'دخل',
        'withholding' => 'خصم وتحصيل',
        'stamp' => 'دمغة',
        'other' => 'أخرى',
    ];

    public const STATUSES = [
        'pending' => 'مستحقة',
        'paid' => 'مدفوعة',
        'cancelled' => 'ملغاة',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
