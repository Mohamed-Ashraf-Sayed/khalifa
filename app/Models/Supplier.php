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
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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

        return bcsub($owed, $paid, 2);
    }
}
