<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentLog extends Model
{
    protected $fillable = [
        'asset_id', 'log_type', 'log_date', 'operating_hours',
        'cost', 'description', 'next_service_date', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'log_date' => 'date',
            'next_service_date' => 'date',
            'operating_hours' => 'decimal:2',
            'cost' => 'decimal:2',
        ];
    }

    public const LOG_TYPES = [
        'usage' => 'تشغيل',
        'maintenance' => 'صيانة',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
