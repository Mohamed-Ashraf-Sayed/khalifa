<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    public const STATUSES = [
        'draft' => 'مسودة',
        'pending' => 'قيد الانتظار',
        'approved' => 'معتمد',
        'partial' => 'مستلم جزئياً',
        'received' => 'مستلم',
        'cancelled' => 'ملغي',
    ];

    protected $fillable = [
        'order_number', 'supplier_id', 'project_id', 'order_date', 'expected_delivery', 'actual_delivery',
        'status', 'total_amount', 'discount', 'tax', 'net_amount', 'paid_amount', 'add_to_inventory',
        'notes', 'created_by', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'add_to_inventory' => 'boolean',
            'order_date' => 'date',
            'expected_delivery' => 'date',
            'actual_delivery' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
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
     * الإجمالي = مجموع البنود، الصافي = الإجمالي − الخصم + الضريبة.
     */
    public function recomputeTotals(): void
    {
        $total = (string) $this->items()->sum('total_price');
        $this->total_amount = $total;
        $this->net_amount = bcadd(bcsub($total, (string) $this->discount, 2), (string) $this->tax, 2);
        $this->save();
    }

    /** المتبقّي على أمر الشراء. */
    public function remaining(): string
    {
        return bcsub((string) $this->net_amount, (string) $this->paid_amount, 2);
    }
}
