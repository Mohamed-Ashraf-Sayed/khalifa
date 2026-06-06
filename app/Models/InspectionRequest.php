<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InspectionRequest extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'general' => 'عام',
        'excavation' => 'حفر',
        'concrete_pour' => 'صب خرسانة',
        'steel' => 'حديد تسليح',
        'finishing' => 'تشطيبات',
        'mep' => 'كهروميكانيكا',
        'survey' => 'رفع مساحي',
    ];

    public const STATUSES = [
        'pending' => 'بانتظار الفحص',
        'approved' => 'مقبول',
        'rejected' => 'مرفوض',
        'closed' => 'مغلق',
    ];

    protected $fillable = [
        'ir_number', 'project_id', 'title', 'type', 'location',
        'scheduled_date', 'status', 'result', 'inspector',
        'inspected_at', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'inspected_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** طلب الفحص بانتظار الفحص وتجاوز الموعد المجدول. */
    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->scheduled_date && $this->scheduled_date->isPast();
    }
}
