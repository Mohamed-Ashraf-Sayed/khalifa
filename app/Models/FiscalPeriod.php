<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalPeriod extends Model
{
    public const STATUSES = [
        'open' => 'مفتوحة',
        'closed' => 'مقفلة',
    ];

    protected $fillable = [
        'fiscal_year_id', 'name', 'period_number',
        'start_date', 'end_date', 'status', 'closed_by', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * هل التاريخ يقع داخل فترة مقفلة؟ (يمنع الترحيل المحاسبي على فترة مغلقة).
     * يعتمد على وجود فترة مقفلة تحتوي التاريخ — لو مفيش فترات معرّفة أصلاً، لا قفل.
     */
    public static function isLocked(string $date): bool
    {
        return self::where('status', 'closed')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();
    }
}
