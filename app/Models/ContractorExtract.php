<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractorExtract extends Model
{
    public const STATUSES = [
        'pending' => 'قيد الاعتماد',
        'approved' => 'معتمد',
        'partial' => 'مدفوع جزئياً',
        'paid' => 'مدفوع',
        'cancelled' => 'ملغي',
    ];

    protected $fillable = [
        'extract_number', 'contractor_id', 'project_id', 'extract_date', 'description',
        'total_amount', 'deductions', 'net_amount', 'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'deductions' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'extract_date' => 'date',
        ];
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
