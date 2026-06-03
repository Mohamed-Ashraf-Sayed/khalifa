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

    /**
     * رصيد المورّد المستحقّ = إجمالي أوامر الشراء المستلَمة − إجمالي المدفوعات.
     * مشتقّ من المصدر مباشرةً (مفيش عمود مخزّن يتعرّض للـdrift).
     */
    public function balanceDue(): string
    {
        $received = (string) $this->purchaseOrders()->where('status', 'received')->sum('total_amount');
        $paid = (string) $this->payments()->sum('amount');

        return bcsub($received, $paid, 2);
    }
}
