<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'company_name', 'phone', 'phone2', 'email',
        'city', 'address', 'tax_number', 'commercial_register', 'notes', 'created_by',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * إجمالي فواتير العميل غير الملغاة (مدين على العميل).
     * مشتقّ من المصدر مباشرةً للعرض فقط — مطابق لمنطق دالة statement.
     */
    public function totalInvoiced(): string
    {
        return (string) $this->invoices()
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');
    }

    /**
     * إجمالي ما سدّده العميل من دفعات على فواتيره غير الملغاة (دائن).
     */
    public function totalPaid(): string
    {
        return (string) $this->invoices()
            ->where('status', '!=', 'cancelled')
            ->sum('paid_amount');
    }

    /**
     * الرصيد المستحقّ على العميل = إجمالي الفواتير − إجمالي المسدّد.
     * للعرض فقط — لا يغيّر أي ترحيل محاسبي.
     */
    public function balanceDue(): string
    {
        return bcsub($this->totalInvoiced(), $this->totalPaid(), 2);
    }
}
