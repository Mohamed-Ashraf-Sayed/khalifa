<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPayment extends Model
{
    public const PAYMENT_METHODS = [
        'cash' => 'نقدي',
        'bank_transfer' => 'تحويل بنكي',
        'check' => 'شيك',
    ];

    /** مكوّنات الاستقطاع الـ10 (بدون total_deductions). */
    public const DEDUCTION_FIELDS = [
        'vat',
        'insurance_5_percent',
        'social_insurance',
        'commercial_profit_supply',
        'commercial_profit_works',
        'engineering_professions',
        'arts_specialists',
        'applied_professions',
        'bank_transfer_fee',
        'other_deductions',
    ];

    protected $fillable = [
        'supplier_id', 'amount', 'payment_date', 'payment_method',
        'bank_account_id', 'reference_number', 'notes', 'created_by',
        'vat', 'insurance_5_percent', 'social_insurance', 'commercial_profit_supply',
        'commercial_profit_works', 'engineering_professions', 'arts_specialists',
        'applied_professions', 'bank_transfer_fee', 'other_deductions', 'total_deductions',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'vat' => 'decimal:2',
            'insurance_5_percent' => 'decimal:2',
            'social_insurance' => 'decimal:2',
            'commercial_profit_supply' => 'decimal:2',
            'commercial_profit_works' => 'decimal:2',
            'engineering_professions' => 'decimal:2',
            'arts_specialists' => 'decimal:2',
            'applied_professions' => 'decimal:2',
            'bank_transfer_fee' => 'decimal:2',
            'other_deductions' => 'decimal:2',
            'total_deductions' => 'decimal:2',
        ];
    }

    /** صافي المدفوع نقداً = الإجمالي المستحق − إجمالي الاستقطاعات. */
    public function netCash(): string
    {
        return bcsub((string) $this->amount, (string) $this->total_deductions, 2);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
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
