<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerTransaction extends Model
{
    protected $fillable = [
        'partner_id', 'type', 'amount', 'transaction_date',
        'description', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    public const TYPES = [
        'deposit' => 'إيداع رأس مال',
        'withdrawal' => 'سحب',
        'profit' => 'أرباح',
        'settlement' => 'تسوية',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
