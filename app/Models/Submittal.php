<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Submittal extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'material' => 'عينة مواد',
        'shop_drawing' => 'لوحة تنفيذية',
        'method_statement' => 'طريقة تنفيذ',
        'sample' => 'عيّنة',
        'other' => 'أخرى',
    ];

    public const STATUSES = [
        'submitted' => 'مقدّم',
        'under_review' => 'قيد المراجعة',
        'approved' => 'معتمد',
        'approved_as_noted' => 'معتمد بملاحظات',
        'rejected' => 'مرفوض',
    ];

    protected $fillable = [
        'submittal_number', 'project_id', 'title', 'type', 'spec_section',
        'description', 'status', 'submitted_to', 'due_date', 'reviewed_at',
        'review_notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'reviewed_at' => 'datetime',
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

    /** الاعتماد ما زال مفتوحاً وتجاوز موعد المراجعة المستهدف. */
    public function isOverdue(): bool
    {
        return in_array($this->status, ['submitted', 'under_review'])
            && $this->due_date && $this->due_date->isPast();
    }
}
