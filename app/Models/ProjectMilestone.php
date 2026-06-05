<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMilestone extends Model
{
    protected $fillable = [
        'project_id', 'name', 'planned_start', 'planned_end',
        'actual_start', 'actual_end', 'progress_percent', 'status', 'sort', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'planned_start' => 'date',
            'planned_end' => 'date',
            'actual_start' => 'date',
            'actual_end' => 'date',
            'progress_percent' => 'integer',
        ];
    }

    public const STATUSES = [
        'pending' => 'لم تبدأ',
        'in_progress' => 'جارية',
        'done' => 'منتهية',
        'delayed' => 'متأخرة',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** المرحلة متأخرة إذا لم تنتهِ وتجاوز تاريخ نهايتها المخطط. */
    public function isDelayed(): bool
    {
        return $this->status !== 'done' && $this->planned_end && $this->planned_end->isPast();
    }
}
