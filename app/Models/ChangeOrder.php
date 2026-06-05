<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChangeOrder extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'addition' => 'أمر إضافي',
        'deduction' => 'أمر خصم',
    ];

    public const STATUSES = [
        'pending' => 'معلّق',
        'approved' => 'معتمد',
        'rejected' => 'مرفوض',
    ];

    protected $fillable = [
        'co_number', 'project_id', 'contract_id', 'title', 'description',
        'change_type', 'amount', 'status', 'request_date',
        'approved_by', 'approved_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'request_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(ProjectContract::class, 'contract_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** القيمة بإشارتها: أمر الخصم يُحسب سالباً. */
    public function signedAmount(): string
    {
        return $this->change_type === 'deduction'
            ? bcmul((string) $this->amount, '-1', 2)
            : (string) $this->amount;
    }
}
