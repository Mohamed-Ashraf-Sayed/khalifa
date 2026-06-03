<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    public const TYPES = [
        'initial' => 'مقدّمة',
        'progress' => 'مرحلية',
        'final' => 'نهائية',
    ];

    public const STATUSES = [
        'draft' => 'مسودة',
        'sent' => 'مُرسلة',
        'partial' => 'مدفوعة جزئياً',
        'paid' => 'مدفوعة',
        'overdue' => 'متأخرة',
        'cancelled' => 'ملغاة',
    ];

    protected $fillable = [
        'invoice_number', 'client_id', 'project_id', 'invoice_type', 'issue_date', 'due_date',
        'subtotal', 'tax_rate', 'tax_amount', 'total_amount', 'paid_amount', 'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
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
        return $this->hasMany(InvoiceItem::class);
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
