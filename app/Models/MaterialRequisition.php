<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialRequisition extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'pending' => 'بانتظار الاعتماد',
        'approved' => 'معتمد',
        'issued' => 'مصروف',
        'rejected' => 'مرفوض',
    ];

    protected $fillable = [
        'requisition_number', 'project_id', 'request_date', 'status',
        'approved_by', 'approved_at', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MaterialRequisitionItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
