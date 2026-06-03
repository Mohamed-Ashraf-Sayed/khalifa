<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerProfitSchedule extends Model
{
    protected $fillable = [
        'partner_deposit_id', 'due_date', 'amount', 'is_paid', 'paid_date', 'partner_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'paid_date' => 'date',
            'amount' => 'decimal:2',
            'is_paid' => 'boolean',
        ];
    }

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(PartnerDeposit::class, 'partner_deposit_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PartnerTransaction::class, 'partner_transaction_id');
    }
}
