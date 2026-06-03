<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    public const TYPES = [
        'in' => 'إضافة للمخزون',
        'out' => 'صرف من المخزون',
        'transfer' => 'تحويل بين المشاريع',
        'adjustment' => 'تسوية جرد',
    ];

    protected $fillable = [
        'material_id', 'type', 'quantity', 'movement_date', 'project_id', 'reason', 'notes', 'created_by',
        'unit_price', 'total_value', 'stock_before', 'stock_after', 'employee_id', 'to_project_id',
        'reference_type', 'reference_id', 'warehouse_location',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'movement_date' => 'date',
            'unit_price' => 'decimal:2',
            'total_value' => 'decimal:2',
            'stock_before' => 'decimal:2',
            'stock_after' => 'decimal:2',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function toProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'to_project_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
