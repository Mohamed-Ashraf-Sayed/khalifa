<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnerDeposit extends Model
{
    protected $fillable = [
        'partner_id', 'amount', 'deposit_date', 'profit_rate', 'duration_months',
        'payout_frequency', 'bank_account_id', 'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'deposit_date' => 'date',
            'profit_rate' => 'decimal:2',
        ];
    }

    public const PAYOUT_FREQUENCIES = [
        'monthly' => 'شهري',
        'quarterly' => 'ربع سنوي',
        'semiannual' => 'نصف سنوي',
        'annual' => 'سنوي',
    ];

    public const STATUSES = [
        'active' => 'نشط',
        'settled' => 'مُسوّى',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(PartnerProfitSchedule::class);
    }

    /** عدد أشهر الدورة الواحدة حسب الدورية. */
    public function periodMonths(): int
    {
        return match ($this->payout_frequency) {
            'monthly' => 1,
            'quarterly' => 3,
            'semiannual' => 6,
            'annual' => 12,
            default => 12,
        };
    }

    /** قيمة الربح المستحقّة في الدورة الواحدة (bcmath). */
    public function profitPerPeriod(): string
    {
        $annual = bcdiv(bcmul((string) $this->amount, (string) $this->profit_rate, 6), '100', 6);

        return bcdiv(bcmul($annual, (string) $this->periodMonths(), 6), '12', 2);
    }

    /** يولّد جدول صرف الأرباح: يحذف غير المدفوع ثم ينشئ الدفعات حسب المدة والدورية. */
    public function generateSchedule(): void
    {
        $this->schedules()->where('is_paid', false)->delete();

        $pm = $this->periodMonths();
        $n = intdiv((int) $this->duration_months, $pm);

        for ($i = 1; $i <= $n; $i++) {
            $this->schedules()->create([
                'due_date' => \Carbon\Carbon::parse($this->deposit_date)->copy()->addMonths($i * $pm)->toDateString(),
                'amount' => $this->profitPerPeriod(),
                'is_paid' => false,
            ]);
        }
    }

    /** إجمالي الأرباح المجدولة. */
    public function totalScheduledProfit(): string
    {
        return (string) $this->schedules()->sum('amount');
    }

    /** إجمالي الأرباح المصروفة فعلياً. */
    public function totalPaidProfit(): string
    {
        return (string) $this->schedules()->where('is_paid', true)->sum('amount');
    }
}
