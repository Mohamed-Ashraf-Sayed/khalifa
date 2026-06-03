<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    protected $fillable = [
        'name', 'client_id', 'project_type', 'location', 'description',
        'contract_value', 'start_date', 'end_date', 'actual_end_date',
        'status', 'manager_id', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'contract_value' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'actual_end_date' => 'date',
        ];
    }

    public const STATUSES = [
        'pending' => 'قيد الانتظار',
        'in_progress' => 'جاري التنفيذ',
        'completed' => 'مكتمل',
        'on_hold' => 'متوقف',
        'cancelled' => 'ملغي',
    ];

    public const TYPES = [
        'building' => 'مبنى',
        'road' => 'طريق',
        'bridge' => 'كوبري',
        'residential' => 'سكني',
        'commercial' => 'تجاري',
        'other' => 'أخرى',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
