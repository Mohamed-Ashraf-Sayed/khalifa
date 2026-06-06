<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FiscalPeriod;
use App\Models\FiscalYear;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * إدارة السنوات والفترات المالية + الإقفال السنوي (قيد الإقفال).
 *
 * مبادئ:
 *  - قفل الفترة يمنع أي ترحيل محاسبي على تواريخها (عبر FiscalPeriod::isLocked + JournalService).
 *  - قيد الإقفال يصفّر حسابات الإيرادات والمصروفات إلى «الأرباح المرحّلة» (3103) — متوازن.
 *  - النموذج تراكمي: الأرصدة مشتقّة من القيود المرحّلة؛ الإقفال المتسلسل يعطي ربح السنة بدقّة.
 */
class FiscalYearService
{
    public function __construct(private JournalService $journal) {}

    private const MONTHS = [1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل', 5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس', 9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'];

    /** ينشئ سنة مالية مع 12 فترة شهرية. */
    public function createYear(int $year, ?int $userId = null): FiscalYear
    {
        return DB::transaction(function () use ($year, $userId) {
            $start = Carbon::create($year, 1, 1)->startOfDay();
            $end = Carbon::create($year, 12, 31)->endOfDay();

            $fy = FiscalYear::create([
                'name' => (string) $year,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'status' => 'open',
                'created_by' => $userId,
            ]);

            for ($m = 1; $m <= 12; $m++) {
                $ms = Carbon::create($year, $m, 1);
                $fy->periods()->create([
                    'name' => self::MONTHS[$m].' '.$year,
                    'period_number' => $m,
                    'start_date' => $ms->copy()->startOfMonth()->toDateString(),
                    'end_date' => $ms->copy()->endOfMonth()->toDateString(),
                    'status' => 'open',
                ]);
            }

            return $fy;
        });
    }

    /** يقفل/يفتح فترة واحدة. */
    public function setPeriodStatus(FiscalPeriod $period, string $status, ?int $userId = null): void
    {
        $period->update([
            'status' => $status,
            'closed_by' => $status === 'closed' ? $userId : null,
            'closed_at' => $status === 'closed' ? now() : null,
        ]);
    }

    /**
     * الإقفال السنوي: يولّد قيد إقفال (تصفير الإيرادات/المصروفات → الأرباح المرحّلة)
     * ثم يقفل السنة وكل فتراتها.
     */
    public function closeYear(FiscalYear $fy, ?int $userId = null): FiscalYear
    {
        if ($fy->status === 'closed') {
            return $fy;
        }

        return DB::transaction(function () use ($fy, $userId) {
            $asOf = $fy->end_date->toDateString();
            $lines = [];
            $totalRevenue = '0';
            $totalExpense = '0';

            foreach (Account::where('is_group', false)->whereIn('type', ['revenue', 'expense'])->get() as $acc) {
                $bal = $acc->balance($asOf); // رصيد طبيعي موجب
                if (bccomp($bal, '0', 2) <= 0) {
                    continue;
                }
                if ($acc->type === 'revenue') {
                    // الإيراد طبيعته دائنة → نقفله بجعله مديناً
                    $lines[] = ['account_id' => $acc->id, 'debit' => $bal, 'credit' => 0, 'description' => 'إقفال '.$acc->name];
                    $totalRevenue = bcadd($totalRevenue, $bal, 2);
                } else {
                    // المصروف طبيعته مدينة → نقفله بجعله دائناً
                    $lines[] = ['account_id' => $acc->id, 'debit' => 0, 'credit' => $bal, 'description' => 'إقفال '.$acc->name];
                    $totalExpense = bcadd($totalExpense, $bal, 2);
                }
            }

            $closingEntry = null;
            $net = bcsub($totalRevenue, $totalExpense, 2); // موجب = ربح
            $retained = Account::resolve('3103'); // الأرباح المرحّلة

            if (count($lines) >= 1 && $retained && bccomp($net, '0', 2) !== 0) {
                if (bccomp($net, '0', 2) > 0) {
                    $lines[] = ['account_id' => $retained->id, 'debit' => 0, 'credit' => $net, 'description' => 'صافي ربح السنة '.$fy->name];
                } else {
                    $lines[] = ['account_id' => $retained->id, 'debit' => bcmul($net, '-1', 2), 'credit' => 0, 'description' => 'صافي خسارة السنة '.$fy->name];
                }

                $closingEntry = $this->journal->createEntry(
                    ['entry_date' => $asOf, 'description' => 'قيد إقفال السنة المالية '.$fy->name, 'created_by' => $userId],
                    $lines,
                    ['source' => 'closing', 'reference_type' => 'fiscal_year', 'reference_id' => $fy->id],
                );
                $this->journal->post($closingEntry, $userId);
            }

            $fy->periods()->update(['status' => 'closed', 'closed_by' => $userId, 'closed_at' => now()]);
            $fy->update([
                'status' => 'closed',
                'closing_entry_id' => $closingEntry?->id,
                'closed_by' => $userId,
                'closed_at' => now(),
            ]);

            return $fy->fresh();
        });
    }

    /** إعادة فتح السنة: يحذف قيد الإقفال ويفتح كل الفترات. */
    public function reopenYear(FiscalYear $fy): FiscalYear
    {
        return DB::transaction(function () use ($fy) {
            if ($fy->closingEntry) {
                $this->journal->unpost($fy->closingEntry);
                $fy->closingEntry->lines()->delete();
                $fy->closingEntry->delete();
            }

            $fy->periods()->update(['status' => 'open', 'closed_by' => null, 'closed_at' => null]);
            $fy->update(['status' => 'open', 'closing_entry_id' => null, 'closed_by' => null, 'closed_at' => null]);

            return $fy->fresh();
        });
    }
}
