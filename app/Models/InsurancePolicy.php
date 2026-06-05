<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class InsurancePolicy extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'policy_number', 'type', 'provider', 'coverage_amount', 'premium',
        'start_date', 'expiry_date', 'status', 'project_id', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'coverage_amount' => 'decimal:2',
            'premium' => 'decimal:2',
            'start_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public const TYPES = [
        'contractor_all_risk' => 'كل أخطار المقاولين',
        'liability' => 'مسؤولية مدنية',
        'workers' => 'إصابات عمل',
        'equipment' => 'معدات',
        'other' => 'أخرى',
    ];

    public const STATUSES = [
        'active' => 'سارية',
        'expired' => 'منتهية',
        'cancelled' => 'ملغاة',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** هل الوثيقة سارية وقاربت على الانتهاء خلال عدد أيام محدد؟ */
    public function isExpiringSoon(int $days = 30): bool
    {
        if ($this->status !== 'active' || ! $this->expiry_date) {
            return false;
        }

        $today = Carbon::today();

        return $this->expiry_date->gte($today)
            && $this->expiry_date->lte($today->copy()->addDays($days));
    }
}
