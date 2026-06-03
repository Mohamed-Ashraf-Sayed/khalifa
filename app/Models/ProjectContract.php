<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectContract extends Model
{
    protected $fillable = [
        'project_id', 'contract_number', 'contract_type', 'title',
        'first_party', 'second_party', 'signing_date', 'start_date',
        'end_date', 'contract_value', 'status', 'description', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'signing_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'contract_value' => 'decimal:2',
        ];
    }

    public const TYPES = [
        'main' => 'عقد أصلي',
        'amendment' => 'تعديل',
        'addendum' => 'ملحق',
        'subcontract' => 'عقد باطن',
    ];

    public const STATUSES = [
        'draft' => 'مسودة',
        'active' => 'ساري',
        'completed' => 'منتهٍ',
        'cancelled' => 'ملغي',
        'suspended' => 'معلّق',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
