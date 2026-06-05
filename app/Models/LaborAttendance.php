<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaborAttendance extends Model
{
    protected $fillable = [
        'project_id', 'attendance_date', 'employee_id', 'laborer_name',
        'hours', 'present', 'wage', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'present' => 'boolean',
            'hours' => 'decimal:2',
            'wage' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** اسم العامل المعروض: الموظف المرتبط أو الاسم اليدوي. */
    public function displayName(): string
    {
        return $this->employee?->name ?? $this->laborer_name ?? '—';
    }
}
