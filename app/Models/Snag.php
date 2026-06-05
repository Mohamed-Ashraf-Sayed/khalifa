<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Snag extends Model
{
    public const PRIORITIES = [
        'low' => 'منخفضة',
        'medium' => 'متوسطة',
        'high' => 'عالية',
    ];

    public const STATUSES = [
        'open' => 'مفتوح',
        'in_progress' => 'قيد المعالجة',
        'closed' => 'مغلق',
    ];

    protected $fillable = [
        'project_id', 'title', 'description', 'location', 'priority', 'status',
        'assigned_employee_id', 'responsible', 'due_date', 'closed_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** الملاحظة متأخرة عن تاريخ الاستحقاق ولم تُغلق بعد. */
    public function isOverdue(): bool
    {
        return $this->status !== 'closed' && $this->due_date && $this->due_date->isPast();
    }
}
