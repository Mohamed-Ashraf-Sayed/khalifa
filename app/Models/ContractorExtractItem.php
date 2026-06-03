<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractorExtractItem extends Model
{
    protected $fillable = [
        'contractor_extract_id', 'description', 'unit',
        'quantity', 'prev_quantity', 'unit_price', 'total_price', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'prev_quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public function extract(): BelongsTo
    {
        return $this->belongsTo(ContractorExtract::class, 'contractor_extract_id');
    }
}
