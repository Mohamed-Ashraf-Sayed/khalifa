<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Revenue extends Model
{
    public const PAYMENT_METHODS = [
        'cash' => 'نقدي',
        'bank_transfer' => 'تحويل بنكي',
        'check' => 'شيك',
    ];

    public const PAYMENT_STATUSES = [
        'pending' => 'غير محصّل',
        'partial' => 'محصّل جزئياً',
        'collected' => 'محصّل بالكامل',
    ];

    protected $fillable = [
        'project_id', 'description', 'amount', 'paid_amount', 'payment_status',
        'revenue_date', 'due_date', 'payment_method', 'bank_account_id',
        'check_number', 'deferred_check', 'is_confirmed', 'notes', 'created_by',
        'cost_center_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'revenue_date' => 'date',
            'due_date' => 'date',
            'deferred_check' => 'boolean',
            'is_confirmed' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function collections(): HasMany
    {
        return $this->hasMany(RevenueCollection::class);
    }

    /** المتبقّي تحصيله على الإيراد. */
    public function remaining(): string
    {
        return bcsub((string) $this->amount, (string) $this->paid_amount, 2);
    }

    /**
     * يُحدّث المحصّل وحالة التحصيل تلقائياً:
     * - الإيراد المُودَع مباشرة في حساب بنكي يُعتبر محصّلاً بالكامل.
     * - الإيراد الآجل: المحصّل = مجموع التحصيلات المرتبطة.
     */
    public function refreshCollectionStatus(): void
    {
        if ($this->bank_account_id) {
            $this->paid_amount = $this->amount;
        } else {
            $this->paid_amount = (string) $this->collections()->sum('amount');
        }

        $cmp = bccomp((string) $this->paid_amount, (string) $this->amount, 2);
        $this->payment_status = $cmp >= 0
            ? 'collected'
            : (bccomp((string) $this->paid_amount, '0', 2) > 0 ? 'partial' : 'pending');

        $this->save();
    }
}
