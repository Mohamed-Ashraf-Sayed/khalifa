<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Meeting extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'meeting_number', 'project_id', 'title', 'meeting_date', 'location',
        'attendees', 'agenda', 'decisions', 'action_items', 'next_meeting_date', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
            'next_meeting_date' => 'date',
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

    public static function nextNumber(): string
    {
        $year = now()->format('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;

        return sprintf('MIN-%s-%04d', $year, $count);
    }
}
