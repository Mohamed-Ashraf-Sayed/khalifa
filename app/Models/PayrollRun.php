<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollRun extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'draft' => 'مسودة',
        'approved' => 'معتمد',
        'paid' => 'مدفوع',
    ];

    public const MONTHS = [
        1 => 'يناير',
        2 => 'فبراير',
        3 => 'مارس',
        4 => 'أبريل',
        5 => 'مايو',
        6 => 'يونيو',
        7 => 'يوليو',
        8 => 'أغسطس',
        9 => 'سبتمبر',
        10 => 'أكتوبر',
        11 => 'نوفمبر',
        12 => 'ديسمبر',
    ];

    protected $fillable = [
        'run_number', 'period_year', 'period_month', 'status', 'bank_account_id',
        'total_net', 'notes', 'approved_by', 'approved_at', 'paid_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_net' => 'decimal:2',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** إعادة احتساب إجمالي صافي المسيّر من بنوده. */
    public function recomputeTotal(): void
    {
        $total = '0';
        foreach ($this->items as $item) {
            $total = bcadd($total, (string) $item->net_salary, 2);
        }

        $this->total_net = $total;
        $this->save();
    }
}
