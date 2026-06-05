<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rfi extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'open' => 'مفتوح',
        'answered' => 'تمت الإجابة',
        'closed' => 'مغلق',
    ];

    protected $fillable = [
        'rfi_number', 'project_id', 'subject', 'question', 'answer',
        'status', 'raised_to', 'due_date', 'answered_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'answered_at' => 'datetime',
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

    /** طلب الاستفسار مفتوح وتجاوز موعد الرد المستهدف. */
    public function isOverdue(): bool
    {
        return $this->status === 'open' && $this->due_date && $this->due_date->isPast();
    }
}
