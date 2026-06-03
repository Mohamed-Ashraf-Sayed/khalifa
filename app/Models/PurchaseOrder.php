<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrder extends Model
{
    public const STATUSES = [
        'draft' => 'مسودة',
        'pending' => 'قيد الانتظار',
        'approved' => 'معتمد',
        'received' => 'مستلم',
        'cancelled' => 'ملغي',
    ];

    protected $fillable = [
        'order_number', 'supplier_id', 'project_id', 'order_date', 'expected_delivery',
        'status', 'total_amount', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'order_date' => 'date',
            'expected_delivery' => 'date',
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
}
