<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Material extends Model
{
    protected $fillable = [
        'project_id', 'supplier_id', 'name', 'category', 'unit',
        'unit_price', 'current_stock', 'min_stock', 'notes', 'created_by',
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
}
