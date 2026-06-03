<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierTransaction extends Model
{
    public const CATEGORIES = [
        'materials' => 'مواد',
        'equipment' => 'معدات',
        'services' => 'خدمات',
        'transport' => 'نقل',
        'other' => 'أخرى',
    ];

    public const PAYMENT_METHODS = [
        'cash' => 'نقدي',
        'bank' => 'تحويل بنكي',
        'check' => 'شيك',
        'credit' => 'آجل',
    ];

    protected $fillable = [
        'supplier_id', 'project_id', 'transaction_date', 'item_description', 'category',
        'unit', 'quantity', 'unit_price', 'total_amount', 'discount_percentage',
        'net_amount', 'paid_amount', 'payment_method', 'check_number', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'discount_percentage' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** المتبقّي على هذه المعاملة (للعرض). */
    public function remaining(): string
    {
        return bcsub((string) $this->net_amount, (string) $this->paid_amount, 2);
    }
}
