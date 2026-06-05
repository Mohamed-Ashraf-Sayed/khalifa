<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'draft' => 'مسودة',
        'sent' => 'مُرسل',
        'accepted' => 'مقبول',
        'rejected' => 'مرفوض',
        'expired' => 'منتهي',
    ];

    protected $fillable = [
        'quotation_number', 'client_id', 'project_id', 'issue_date', 'valid_until',
        'subtotal', 'tax_rate', 'tax_amount', 'total_amount', 'status', 'notes',
        'converted_invoice_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * إعادة احتساب الإجماليات من البنود (مصدر موثوق):
     * subtotal = مجموع البنود، الضريبة = subtotal × tax_rate%، الإجمالي = subtotal + الضريبة.
     */
    public function recomputeTotals(): void
    {
        $subtotal = (string) $this->items()->sum('total_price');
        $taxAmount = bcdiv(bcmul($subtotal, (string) $this->tax_rate, 4), '100', 2);

        $this->subtotal = $subtotal;
        $this->tax_amount = $taxAmount;
        $this->total_amount = bcadd($subtotal, $taxAmount, 2);
        $this->save();
    }
}
