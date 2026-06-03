<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cheque extends Model
{
    protected $fillable = [
        'cheque_number', 'bank_account_id', 'direction', 'party_name',
        'amount', 'issue_date', 'due_date', 'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'issue_date' => 'date',
            'due_date' => 'date',
        ];
    }

    public const DIRECTIONS = [
        'incoming' => 'وارد',
        'outgoing' => 'صادر',
    ];

    public const STATUSES = [
        'pending' => 'قيد التحصيل',
        'deposited' => 'مودع',
        'cleared' => 'محصّل',
        'bounced' => 'مرتد',
        'cancelled' => 'ملغي',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
