<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    protected $fillable = [
        'name', 'phone', 'email', 'national_id', 'address',
        'join_date', 'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
        ];
    }

    public const STATUSES = [
        'active' => 'نشط',
        'inactive' => 'غير نشط',
        'settled' => 'مُسوّى',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PartnerTransaction::class);
    }

    /** إجمالي رأس المال المودَع. */
    public function totalCapital(): string
    {
        return (string) $this->transactions()->where('type', 'deposit')->sum('amount');
    }

    /** الرصيد الحالي للشريك = الإيداعات − (السحوبات + الأرباح المصروفة + التسويات). */
    public function currentBalance(): string
    {
        $deposits = (string) $this->transactions()->where('type', 'deposit')->sum('amount');
        $out = (string) $this->transactions()->whereIn('type', ['withdrawal', 'profit', 'settlement'])->sum('amount');

        return bcsub($deposits, $out, 2);
    }
}
