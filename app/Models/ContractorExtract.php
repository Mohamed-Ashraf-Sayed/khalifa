<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractorExtract extends Model
{
    use SoftDeletes;

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
        'retention_percent', 'retention_amount', 'retention_released',
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
            'retention_percent' => 'decimal:2',
            'retention_amount' => 'decimal:2',
            'retention_released' => 'boolean',
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
        $this->retention_amount = bcdiv(bcmul((string) $this->net_amount, (string) $this->retention_percent, 4), '100', 2);
        $this->save();
    }

    /** تحرير المبلغ المحتجز على المستخلص. */
    public function releaseRetention(): void
    {
        $this->retention_released = true;
        $this->save();
    }

    /** المتبقّي على المستخلص. */
    public function remaining(): string
    {
        return bcsub((string) $this->net_amount, (string) $this->paid_amount, 2);
    }

    /**
     * يُحدّث المدفوع وحالة المستخلص تلقائياً من دفعات المقاول المرتبطة.
     * paid = مجموع الدفعات على هذا المستخلص؛ الحالة: مدفوع/جزئي/معتمد.
     * لا يُنزّل مستخلصاً "ملغياً" أو "قيد الاعتماد" — يحافظ على المسار المنطقي.
     */
    public function refreshPaymentStatus(): void
    {
        $paid = (string) ContractorPayment::where('extract_id', $this->id)->sum('amount');
        $this->paid_amount = $paid;

        if (! in_array($this->status, ['cancelled', 'pending'], true)) {
            if (bccomp((string) $this->net_amount, '0', 2) > 0 && bccomp($paid, (string) $this->net_amount, 2) >= 0) {
                $this->status = 'paid';
            } elseif (bccomp($paid, '0', 2) > 0) {
                $this->status = 'partial';
            } else {
                $this->status = 'approved';
            }
        }

        $this->save();
    }
}
