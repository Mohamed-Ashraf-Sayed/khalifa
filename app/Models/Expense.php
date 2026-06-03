<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expense extends Model
{
    public const PAYMENT_STATUSES = [
        'unpaid' => 'غير مدفوع',
        'partial' => 'مدفوع جزئياً',
        'paid' => 'مدفوع',
    ];

    public const CATEGORIES = [
        'materials' => 'مواد',
        'labor' => 'عمالة',
        'equipment' => 'معدات',
        'transportation' => 'نقل',
        'utilities' => 'مرافق',
        'administrative' => 'إدارية',
        'other' => 'أخرى',
    ];

    public const PAYMENT_METHODS = [
        'cash' => 'نقدي',
        'bank_transfer' => 'تحويل بنكي',
        'check' => 'شيك',
    ];

    protected $fillable = [
        'project_id', 'category', 'description', 'amount', 'expense_date',
        'payment_method', 'bank_account_id', 'notes', 'created_by',
        'paid_amount', 'payment_status', 'due_date', 'is_credit',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'expense_date' => 'date',
            'due_date' => 'date',
            'is_credit' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ExpensePayment::class);
    }

    /** المتبقّي على المصروف. */
    public function remaining(): string
    {
        return bcsub((string) $this->amount, (string) $this->paid_amount, 2);
    }

    /**
     * يُحدّث المدفوع وحالة الدفع تلقائياً.
     * المصروف النقدي/البنكي المباشر: مدفوع بالكامل.
     * المصروف الآجل: المدفوع = مجموع الأقساط، والحالة مشتقّة منه.
     */
    public function refreshPaymentStatus(): void
    {
        if (! $this->is_credit && $this->bank_account_id) {
            $this->paid_amount = $this->amount;
            $this->payment_status = 'paid';
        } else {
            $paid = (string) $this->payments()->sum('amount');
            $this->paid_amount = $paid;
            $this->payment_status = (bccomp((string) $this->amount, '0', 2) > 0 && bccomp($paid, (string) $this->amount, 2) >= 0)
                ? 'paid'
                : (bccomp($paid, '0', 2) > 0 ? 'partial' : 'unpaid');
        }

        $this->save();
    }
}
