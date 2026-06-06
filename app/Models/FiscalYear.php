<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FiscalYear extends Model
{
    public const STATUSES = [
        'open' => 'مفتوحة',
        'closed' => 'مقفلة',
    ];

    protected $fillable = [
        'name', 'start_date', 'end_date', 'status',
        'closing_entry_id', 'closed_by', 'closed_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function periods(): HasMany
    {
        return $this->hasMany(FiscalPeriod::class)->orderBy('period_number');
    }

    public function closingEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'closing_entry_id');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
