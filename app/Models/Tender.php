<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tender extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tender_number', 'title', 'client_id', 'estimated_value', 'bid_value',
        'submission_date', 'status', 'guarantee_id', 'project_id', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'estimated_value' => 'decimal:2',
            'bid_value' => 'decimal:2',
            'submission_date' => 'date',
        ];
    }

    public const STATUSES = [
        'draft' => 'مسودة',
        'submitted' => 'مقدّمة',
        'won' => 'فائزة',
        'lost' => 'خاسرة',
        'cancelled' => 'ملغاة',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function guarantee(): BelongsTo
    {
        return $this->belongsTo(LetterOfGuarantee::class, 'guarantee_id');
    }
}
