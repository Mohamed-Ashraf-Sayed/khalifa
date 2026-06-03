<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contractor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contractor_code', 'name', 'company_name', 'phone', 'phone2', 'email',
        'specialty', 'national_id', 'tax_number', 'notes', 'is_active', 'created_by',
        'opening_balance',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'opening_balance' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function extracts(): HasMany
    {
        return $this->hasMany(ContractorExtract::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ContractorPayment::class);
    }

    /**
     * رصيد المقاول المستحقّ = صافي المستخلصات المعتمدة − إجمالي الدفعات.
     * مشتقّ من المصدر مباشرةً.
     */
    public function balanceDue(): string
    {
        $earned = (string) $this->extracts()
            ->whereIn('status', ['approved', 'partial', 'paid'])
            ->sum('net_amount');
        $paid = (string) $this->payments()->sum('amount');

        return bcadd((string) $this->opening_balance, bcsub($earned, $paid, 2), 2);
    }

    /** إجمالي المبالغ المحتجزة غير المُحرَّرة على المستخلصات المعتمدة/الجزئية/المدفوعة. */
    public function retentionHeld(): string
    {
        return (string) $this->extracts()
            ->whereIn('status', ['approved', 'partial', 'paid'])
            ->where('retention_released', false)
            ->sum('retention_amount');
    }
}
