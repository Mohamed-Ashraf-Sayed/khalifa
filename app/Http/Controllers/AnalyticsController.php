<?php

namespace App\Http\Controllers;

use App\Models\Contractor;
use App\Models\ContractorExtract;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\PartnerProfitSchedule;
use App\Models\Project;
use App\Models\ProjectCost;
use App\Models\Revenue;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use App\Services\ExportService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:reports.view')];
    }

    /**
     * التكلفة الفعلية للمشروع = تكاليف المشروع + المصروفات + مستخلصات المقاولين (معتمد/جزئي/مدفوع) + توريدات المورّدين.
     * كله بالـbcmath حفاظاً على الدقّة في التجميع.
     */
    private function actualCost(int $projectId): string
    {
        $costs = (string) ProjectCost::where('project_id', $projectId)->sum('amount');
        $expenses = (string) Expense::where('project_id', $projectId)->sum('amount');
        $extracts = (string) ContractorExtract::where('project_id', $projectId)
            ->whereIn('status', ['approved', 'partial', 'paid'])
            ->sum('net_amount');
        $supplier = (string) SupplierTransaction::where('project_id', $projectId)->sum('net_amount');

        return array_reduce(
            [$costs, $expenses, $extracts, $supplier],
            fn (string $carry, string $v) => bcadd($carry, $v, 2),
            '0'
        );
    }

    /** النسبة المئوية للقسمة بالـbcmath مع حماية من القسمة على صفر. للعرض فقط (float). */
    private function percentage(string $numerator, string $denominator): float
    {
        if (bccomp($denominator, '0', 2) <= 0) {
            return 0.0;
        }

        return (float) bcmul(bcdiv($numerator, $denominator, 6), '100', 2);
    }

    /**
     * ربحية المشاريع — لكل مشروع: قيمة العقد، التكلفة الفعلية، الإيراد، المحصّل، الربح، هامش الربح.
     */
    public function projectProfitability(Request $request): View|StreamedResponse
    {
        $projects = Project::orderBy('name')->get();

        $rows = [];
        $totContract = '0';
        $totCost = '0';
        $totRevenue = '0';
        $totCollected = '0';
        $totProfit = '0';

        foreach ($projects as $project) {
            $contract = (string) $project->contract_value;
            $cost = $this->actualCost($project->id);
            $revenue = (string) Revenue::where('project_id', $project->id)->sum('amount');
            $collected = (string) Revenue::where('project_id', $project->id)->sum('paid_amount');
            $profit = bcsub($revenue, $cost, 2);
            $margin = $this->percentage($profit, $revenue);

            $rows[] = [
                'id' => $project->id,
                'name' => $project->name,
                'contract' => $contract,
                'cost' => $cost,
                'revenue' => $revenue,
                'collected' => $collected,
                'profit' => $profit,
                'margin' => $margin,
            ];

            $totContract = bcadd($totContract, $contract, 2);
            $totCost = bcadd($totCost, $cost, 2);
            $totRevenue = bcadd($totRevenue, $revenue, 2);
            $totCollected = bcadd($totCollected, $collected, 2);
            $totProfit = bcadd($totProfit, $profit, 2);
        }

        $totals = [
            'contract' => $totContract,
            'cost' => $totCost,
            'revenue' => $totRevenue,
            'collected' => $totCollected,
            'profit' => $totProfit,
            'margin' => $this->percentage($totProfit, $totRevenue),
        ];

        if ($request->input('format') === 'xlsx') {
            $headers = ['المشروع', 'قيمة العقد', 'التكلفة الفعلية', 'الإيراد', 'المحصّل', 'الربح', 'هامش الربح %'];
            $excelRows = [];
            foreach ($rows as $r) {
                $excelRows[] = [
                    $r['name'],
                    number_format((float) $r['contract'], 2),
                    number_format((float) $r['cost'], 2),
                    number_format((float) $r['revenue'], 2),
                    number_format((float) $r['collected'], 2),
                    number_format((float) $r['profit'], 2),
                    number_format($r['margin'], 2),
                ];
            }
            $excelRows[] = [
                'الإجمالي',
                number_format((float) $totals['contract'], 2),
                number_format((float) $totals['cost'], 2),
                number_format((float) $totals['revenue'], 2),
                number_format((float) $totals['collected'], 2),
                number_format((float) $totals['profit'], 2),
                number_format($totals['margin'], 2),
            ];

            return app(ExportService::class)->excel($headers, $excelRows, 'project-profitability', 'ربحية المشاريع');
        }

        return view('analytics.project_profitability', compact('rows', 'totals'));
    }

    /**
     * الموازنة مقابل الفعلي — لكل مشروع: الموازنة (قيمة العقد)، الفعلي، الانحراف، نسبة الاستهلاك.
     */
    public function budgetVsActual(Request $request): View|StreamedResponse
    {
        $projects = Project::orderBy('name')->get();

        $rows = [];
        $totBudget = '0';
        $totActual = '0';

        foreach ($projects as $project) {
            $budget = (string) $project->contract_value;
            $actual = $this->actualCost($project->id);
            $variance = bcsub($budget, $actual, 2);
            $used = $this->percentage($actual, $budget);

            $rows[] = [
                'id' => $project->id,
                'name' => $project->name,
                'budget' => $budget,
                'actual' => $actual,
                'variance' => $variance,
                'used' => $used,
                'over' => bccomp($actual, $budget, 2) > 0,
            ];

            $totBudget = bcadd($totBudget, $budget, 2);
            $totActual = bcadd($totActual, $actual, 2);
        }

        $totals = [
            'budget' => $totBudget,
            'actual' => $totActual,
            'variance' => bcsub($totBudget, $totActual, 2),
            'used' => $this->percentage($totActual, $totBudget),
        ];

        if ($request->input('format') === 'xlsx') {
            $headers = ['المشروع', 'الموازنة', 'الفعلي', 'الانحراف', 'نسبة الاستهلاك %'];
            $excelRows = [];
            foreach ($rows as $r) {
                $excelRows[] = [
                    $r['name'],
                    number_format((float) $r['budget'], 2),
                    number_format((float) $r['actual'], 2),
                    number_format((float) $r['variance'], 2),
                    number_format($r['used'], 2),
                ];
            }
            $excelRows[] = [
                'الإجمالي',
                number_format((float) $totals['budget'], 2),
                number_format((float) $totals['actual'], 2),
                number_format((float) $totals['variance'], 2),
                number_format($totals['used'], 2),
            ];

            return app(ExportService::class)->excel($headers, $excelRows, 'budget-vs-actual', 'الموازنة مقابل الفعلي');
        }

        return view('analytics.budget_vs_actual', compact('rows', 'totals'));
    }

    /**
     * أداء المورّدين — لكل مورّد: المشتريات، المسدّد، الرصيد المستحقّ، عدد أوامر الشراء. مرتّب تنازلياً بالمشتريات.
     */
    public function supplierPerformance(Request $request): View|StreamedResponse
    {
        $suppliers = Supplier::with(['purchaseOrders', 'payments', 'transactions'])->get();

        $rows = [];
        foreach ($suppliers as $supplier) {
            $poNet = (string) $supplier->purchaseOrders
                ->whereIn('status', ['partial', 'received'])
                ->sum('net_amount');
            $txnNet = (string) $supplier->transactions->sum('net_amount');
            $purchases = bcadd($poNet, $txnNet, 2);

            $paymentsPaid = (string) $supplier->payments->sum('amount');
            $txnPaid = (string) $supplier->transactions->sum('paid_amount');
            $paid = bcadd($paymentsPaid, $txnPaid, 2);

            $rows[] = [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'purchases' => $purchases,
                'paid' => $paid,
                'balance' => $supplier->balanceDue(),
                'orders' => $supplier->purchaseOrders->count(),
            ];
        }

        usort($rows, fn ($a, $b) => bccomp($b['purchases'], $a['purchases'], 2));

        $totals = [
            'purchases' => '0',
            'paid' => '0',
            'balance' => '0',
        ];
        foreach ($rows as $r) {
            $totals['purchases'] = bcadd($totals['purchases'], $r['purchases'], 2);
            $totals['paid'] = bcadd($totals['paid'], $r['paid'], 2);
            $totals['balance'] = bcadd($totals['balance'], $r['balance'], 2);
        }

        if ($request->input('format') === 'xlsx') {
            $headers = ['المورّد', 'المشتريات', 'المسدّد', 'الرصيد المستحقّ', 'عدد الأوامر'];
            $excelRows = [];
            foreach ($rows as $r) {
                $excelRows[] = [
                    $r['name'],
                    number_format((float) $r['purchases'], 2),
                    number_format((float) $r['paid'], 2),
                    number_format((float) $r['balance'], 2),
                    $r['orders'],
                ];
            }
            $excelRows[] = [
                'الإجمالي',
                number_format((float) $totals['purchases'], 2),
                number_format((float) $totals['paid'], 2),
                number_format((float) $totals['balance'], 2),
                '',
            ];

            return app(ExportService::class)->excel($headers, $excelRows, 'supplier-performance', 'أداء المورّدين');
        }

        return view('analytics.supplier_performance', compact('rows', 'totals'));
    }

    /**
     * أداء المقاولين — لكل مقاول: عدد المستخلصات، المستحقّ، المسدّد، الرصيد، متوسّط نسبة التنفيذ.
     */
    public function contractorPerformance(Request $request): View|StreamedResponse
    {
        $contractors = Contractor::with(['extracts', 'payments'])->get();

        $rows = [];
        foreach ($contractors as $contractor) {
            $earnedExtracts = $contractor->extracts->whereIn('status', ['approved', 'partial', 'paid']);
            $earned = (string) $earnedExtracts->sum('net_amount');
            $paid = (string) $contractor->payments->sum('amount');
            $avgExecution = (float) ($contractor->extracts->avg('execution_percent') ?? 0);

            $rows[] = [
                'id' => $contractor->id,
                'name' => $contractor->name,
                'extractsCount' => $contractor->extracts->count(),
                'earned' => $earned,
                'paid' => $paid,
                'balance' => $contractor->balanceDue(),
                'avgExecution' => $avgExecution,
            ];
        }

        usort($rows, fn ($a, $b) => bccomp($b['earned'], $a['earned'], 2));

        $totals = [
            'earned' => '0',
            'paid' => '0',
            'balance' => '0',
        ];
        foreach ($rows as $r) {
            $totals['earned'] = bcadd($totals['earned'], $r['earned'], 2);
            $totals['paid'] = bcadd($totals['paid'], $r['paid'], 2);
            $totals['balance'] = bcadd($totals['balance'], $r['balance'], 2);
        }

        if ($request->input('format') === 'xlsx') {
            $headers = ['المقاول', 'عدد المستخلصات', 'المستحقّ', 'المسدّد', 'الرصيد', 'متوسّط التنفيذ %'];
            $excelRows = [];
            foreach ($rows as $r) {
                $excelRows[] = [
                    $r['name'],
                    $r['extractsCount'],
                    number_format((float) $r['earned'], 2),
                    number_format((float) $r['paid'], 2),
                    number_format((float) $r['balance'], 2),
                    number_format($r['avgExecution'], 2),
                ];
            }
            $excelRows[] = [
                'الإجمالي',
                '',
                number_format((float) $totals['earned'], 2),
                number_format((float) $totals['paid'], 2),
                number_format((float) $totals['balance'], 2),
                '',
            ];

            return app(ExportService::class)->excel($headers, $excelRows, 'contractor-performance', 'أداء المقاولين');
        }

        return view('analytics.contractor_performance', compact('rows', 'totals'));
    }

    /**
     * الرواتب — لقطة بالوضع الحالي لكل موظف نشط: الراتب، رصيد السلف، رصيد العهدة،
     * صافي المستحقّ (إرشادي = الراتب − السلف).
     * ملاحظة: الأرقام تعكس الأرصدة القائمة حالياً (وليست مقصورة على شهر معيّن)،
     * لذا أُزيل فلتر الشهر تفادياً للإيحاء بفلترة زمنية لا تُطبَّق على الأرقام.
     */
    public function payroll(Request $request): View|StreamedResponse
    {
        $snapshotDate = now()->toDateString();

        $employees = Employee::where('is_active', true)->orderBy('name')->get();

        $rows = [];
        $totSalary = '0';
        $totAdvance = '0';
        $totCustody = '0';
        $totNet = '0';

        foreach ($employees as $employee) {
            $salary = (string) $employee->salary;
            $advance = $employee->advanceBalance();
            $custody = $employee->custodyBalance();
            $net = bcsub($salary, $advance, 2);

            $rows[] = [
                'name' => $employee->name,
                'jobTitle' => $employee->job_title,
                'salary' => $salary,
                'advance' => $advance,
                'custody' => $custody,
                'net' => $net,
            ];

            $totSalary = bcadd($totSalary, $salary, 2);
            $totAdvance = bcadd($totAdvance, $advance, 2);
            $totCustody = bcadd($totCustody, $custody, 2);
            $totNet = bcadd($totNet, $net, 2);
        }

        $totals = [
            'salary' => $totSalary,
            'advance' => $totAdvance,
            'custody' => $totCustody,
            'net' => $totNet,
        ];

        if ($request->input('format') === 'xlsx') {
            $headers = ['الموظف', 'الوظيفة', 'الراتب', 'رصيد السلف', 'رصيد العهدة', 'صافي المستحقّ'];
            $excelRows = [];
            foreach ($rows as $r) {
                $excelRows[] = [
                    $r['name'],
                    $r['jobTitle'],
                    number_format((float) $r['salary'], 2),
                    number_format((float) $r['advance'], 2),
                    number_format((float) $r['custody'], 2),
                    number_format((float) $r['net'], 2),
                ];
            }
            $excelRows[] = [
                'الإجمالي',
                '',
                number_format((float) $totals['salary'], 2),
                number_format((float) $totals['advance'], 2),
                number_format((float) $totals['custody'], 2),
                number_format((float) $totals['net'], 2),
            ];

            return app(ExportService::class)->excel($headers, $excelRows, 'payroll-'.$snapshotDate, 'كشف الرواتب — لقطة بتاريخ '.$snapshotDate);
        }

        return view('analytics.payroll', compact('rows', 'totals', 'snapshotDate'));
    }

    /**
     * توقّعات أرباح الشركاء — جداول صرف الأرباح غير المدفوعة مجمّعة حسب الشريك (عبر deposit.partner).
     * يعرض المستحقّات القادمة والمتأخّرة (due <= today وغير مدفوعة).
     */
    public function partnerForecast(Request $request): View|StreamedResponse
    {
        $today = now()->startOfDay();

        $schedules = PartnerProfitSchedule::with('deposit.partner')
            ->where('is_paid', false)
            ->orderBy('due_date')
            ->get();

        $partners = [];
        $grandUpcoming = '0';
        $grandOverdue = '0';

        foreach ($schedules as $schedule) {
            $partner = $schedule->deposit?->partner;
            if (! $partner) {
                continue;
            }

            $key = $partner->id;
            if (! isset($partners[$key])) {
                $partners[$key] = [
                    'name' => $partner->name,
                    'items' => [],
                    'total' => '0',
                    'overdue' => '0',
                ];
            }

            $amount = (string) $schedule->amount;
            $isOverdue = Carbon::parse($schedule->due_date)->lte($today);

            $partners[$key]['items'][] = [
                'due_date' => Carbon::parse($schedule->due_date)->toDateString(),
                'amount' => $amount,
                'overdue' => $isOverdue,
            ];
            $partners[$key]['total'] = bcadd($partners[$key]['total'], $amount, 2);
            $grandUpcoming = bcadd($grandUpcoming, $amount, 2);

            if ($isOverdue) {
                $partners[$key]['overdue'] = bcadd($partners[$key]['overdue'], $amount, 2);
                $grandOverdue = bcadd($grandOverdue, $amount, 2);
            }
        }

        $partners = array_values($partners);

        $totals = [
            'upcoming' => $grandUpcoming,
            'overdue' => $grandOverdue,
        ];

        if ($request->input('format') === 'xlsx') {
            $headers = ['الشريك', 'تاريخ الاستحقاق', 'المبلغ', 'الحالة'];
            $excelRows = [];
            foreach ($partners as $p) {
                foreach ($p['items'] as $item) {
                    $excelRows[] = [
                        $p['name'],
                        $item['due_date'],
                        number_format((float) $item['amount'], 2),
                        $item['overdue'] ? 'متأخّر' : 'قادم',
                    ];
                }
            }
            $excelRows[] = ['الإجمالي القادم', '', number_format((float) $totals['upcoming'], 2), ''];
            $excelRows[] = ['الإجمالي المتأخّر', '', number_format((float) $totals['overdue'], 2), ''];

            return app(ExportService::class)->excel($headers, $excelRows, 'partner-forecast', 'توقّعات أرباح الشركاء');
        }

        return view('analytics.partner_forecast', compact('partners', 'totals'));
    }
}
