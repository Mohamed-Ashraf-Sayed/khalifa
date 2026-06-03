<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id', 'supplier_id', 'name', 'category', 'unit',
        'unit_price', 'current_stock', 'min_stock', 'notes', 'created_by', 'warehouse_location',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'current_stock' => 'decimal:2',
            'min_stock' => 'decimal:2',
        ];
    }

    public const CATEGORIES = [
        'cement' => 'أسمنت',
        'steel' => 'حديد',
        'wood' => 'خشب',
        'equipment' => 'معدات',
        'tools' => 'أدوات',
        'other' => 'أخرى',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stockValue(): string
    {
        return bcmul((string) $this->current_stock, (string) $this->unit_price, 2);
    }

    public function scopeLowStock(Builder $q): Builder
    {
        return $q->whereColumn('current_stock', '<=', 'min_stock')->where('min_stock', '>', 0);
    }

    public function isLowStock(): bool
    {
        return bccomp((string) $this->min_stock, '0', 2) > 0
            && bccomp((string) $this->current_stock, (string) $this->min_stock, 2) <= 0;
    }
}
