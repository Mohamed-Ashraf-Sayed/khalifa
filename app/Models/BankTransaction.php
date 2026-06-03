<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransaction extends Model
{
    public const TYPES = [
        'deposit' => 'إيداع',
        'withdrawal' => 'سحب',
    ];

    public const CATEGORIES = [
        'general' => 'عام',
        'fee' => 'رسوم',
        'salary' => 'رواتب',
        'tax' => 'ضرائب',
        'transfer' => 'تحويل',
        'other' => 'أخرى',
    ];

    protected $fillable = [
        'bank_account_id', 'type', 'amount', 'transaction_date',
        'description', 'reference_number', 'related_type', 'related_id', 'created_by',
        'category', 'beneficiary', 'check_number', 'value_date', 'is_reconciled', 'attachment',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
            'value_date' => 'date',
            'is_reconciled' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
