<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{
    protected $fillable = [
        'asset_code', 'asset_name', 'category', 'purchase_date',
        'purchase_value', 'depreciation_rate', 'useful_life_years',
        'status', 'location', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_value' => 'decimal:2',
            'depreciation_rate' => 'decimal:2',
            'purchase_date' => 'date',
        ];
    }

    public const STATUSES = [
        'active' => 'نشط',
        'sold' => 'مُباع',
        'disposed' => 'مُستبعد',
        'fully_depreciated' => 'مُستهلك بالكامل',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
