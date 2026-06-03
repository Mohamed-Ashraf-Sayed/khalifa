<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'name', 'company_name', 'type', 'phone', 'phone2', 'email',
        'address', 'tax_number', 'commercial_register', 'notes', 'is_active', 'created_by',
        'opening_balance', 'credit_limit', 'payment_terms',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'opening_balance' => 'decimal:2',
            'credit_limit' => 'decimal:2',
        ];
    }

    public const TYPES = [
        'external' => 'خارجي',
        'internal' => 'داخلي',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SupplierTransaction::class);
    }

    /**
     * رصيد المورّد المستحقّ (مشتقّ من المصدر، مفيش عمود مخزّن يتعرّض للـdrift):
     *   المستحقّ  = أوامر الشراء المستلَمة + صافي توريدات المورّد
     *   المسدّد   = مدفوعات المورّد + المدفوع وقت الشراء على التوريدات
     *   الرصيد    = المستحقّ − المسدّد
     */
    public function balanceDue(): string
    {
        $poReceived = (string) $this->purchaseOrders()
            ->whereIn('status', ['partial', 'received'])
            ->sum('net_amount');
        $txnNet = (string) $this->transactions()->sum('net_amount');
        $owed = bcadd($poReceived, $txnNet, 2);

        $paymentsPaid = (string) $this->payments()->sum('amount');
        $txnPaid = (string) $this->transactions()->sum('paid_amount');
        $paid = bcadd($paymentsPaid, $txnPaid, 2);

        return bcadd((string) $this->opening_balance, bcsub($owed, $paid, 2), 2);
    }

    /** هل تجاوز المورّد الحدّ الائتماني؟ (حد > 0 والرصيد المستحقّ أكبر منه). */
    public function overCreditLimit(): bool
    {
        return bccomp((string) $this->credit_limit, '0', 2) > 0
            && bccomp($this->balanceDue(), (string) $this->credit_limit, 2) > 0;
    }
}
