<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    protected $fillable = [
        'name', 'bank_name', 'account_number', 'iban', 'branch',
        'currency', 'opening_balance', 'current_balance', 'is_active', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * الرصيد الموثوق المشتقّ من المصدر: الافتتاحي + الإيداعات − السحوبات.
     * مش بيعتمد على أي عمود balance_after مخزّن — وبالتالي مايتأثرش بترتيب التواريخ.
     */
    public function deriveBalance(): string
    {
        $deposits = (string) $this->transactions()->where('type', 'deposit')->sum('amount');
        $withdrawals = (string) $this->transactions()->where('type', 'withdrawal')->sum('amount');

        return bcadd(bcsub((string) $this->opening_balance, $withdrawals, 2), $deposits, 2);
    }
}
