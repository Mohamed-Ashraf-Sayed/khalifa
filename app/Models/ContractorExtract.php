<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractorExtract extends Model
{
    public const STATUSES = [
        'pending' => 'قيد الاعتماد',
        'approved' => 'معتمد',
        'partial' => 'مدفوع جزئياً',
        'paid' => 'مدفوع',
        'cancelled' => 'ملغي',
    ];

    protected $fillable = [
        'extract_number', 'contractor_id', 'project_id', 'extract_date', 'description',
        'total_amount', 'additions', 'discount_percent', 'execution_percent', 'deductions',
        'net_amount', 'paid_amount', 'status', 'notes', 'attachment', 'created_by', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'additions' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'execution_percent' => 'decimal:2',
            'deductions' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'extract_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContractorExtractItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * إعادة احتساب الإجماليات من البنود (مصدر موثوق):
     * الإجمالي = مجموع البنود، الصافي = (الإجمالي + الإضافات) − الخصومات.
     */
    public function recomputeTotals(): void
    {
        $total = (string) $this->items()->sum('total_price');
        $this->total_amount = $total;
        $this->net_amount = bcsub(bcadd($total, (string) $this->additions, 2), (string) $this->deductions, 2);
        $this->save();
    }

    /** المتبقّي على المستخلص. */
    public function remaining(): string
    {
        return bcsub((string) $this->net_amount, (string) $this->paid_amount, 2);
    }
}
