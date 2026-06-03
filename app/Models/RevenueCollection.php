<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueCollection extends Model
{
    protected $fillable = [
        'revenue_id', 'collection_date', 'amount', 'payment_method',
        'bank_account_id', 'check_number', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'collection_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function revenue(): BelongsTo
    {
        return $this->belongsTo(Revenue::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
