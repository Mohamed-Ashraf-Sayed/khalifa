<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\ChangeOrder;
use App\Models\Contractor;
use App\Models\ContractorExtract;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\Partner;
use App\Models\Project;
use App\Models\ProjectCost;
use App\Models\Revenue;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\SupplierTransaction;
use App\Models\Tax;
use App\Services\ExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:reports.view')];
    }

    public function index(Request $request): View
    {
        $from = $request->date('from');
        $to = $request->date('to');

        $revenueQ = Revenue::query()
            ->when($from, fn ($q) => $q->whereDate('revenue_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('revenue_date', '<=', $to));

        $expenseQ = Expense::query()
            ->when($from, fn ($q) => $q->whereDate('expense_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('expense_date', '<=', $to));

        $totalRevenue = (float) (clone $revenueQ)->sum('amount');
        $totalExpense = (float) (clone $expenseQ)->sum('amount');

        // المصروفات حسب الفئة
        $byCategory = (clone $expenseQ)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        // ملخّص لكل مشروع: إيرادات − مصروفات
        $projects = Project::query()
            ->withSum(['revenues as rev' => function ($q) use ($from, $to) {
                $q->when($from, fn ($qq) => $qq->whereDate('revenue_date', '>=', $from))
                    ->when($to, fn ($qq) => $qq->whereDate('revenue_date', '<=', $to));
            }], 'amount')
            ->withSum(['expenses as exp' => function ($q) use ($from, $to) {
                $q->when($from, fn ($qq) => $qq->whereDate('expense_date', '>=', $from))
                    ->when($to, fn ($qq) => $qq->whereDate('expense_date', '<=', $to));
            }], 'amount')
            ->orderBy('name')
            ->get();

        return view('reports.index', [
            'from' => $from?->toDateString(),
            'to' => $to?->toDateString(),
            'totalRevenue' => $totalRevenue,
            'totalExpense' => $totalExpense,
            'net' => $totalRevenue - $totalExpense,
            'byCategory' => $byCategory,
            'projects' => $projects,
        ]);
    }

    /**
     * الميزانية العمومية — لقطة بتاريخ اليوم (بدون فلتر تاريخ).
     * الأصول مقابل (الخصوم + حقوق الملكية). النموذج مبسّط وقد لا يتوازن تماماً،
     * فيُعرض الفرق كـ"فرق التسوية".
     */
    public function balanceSheet(Request $request)
    {
        // تاريخ "كما في" اختياري. عند غيابه يبقى السلوك الافتراضي (لقطة اليوم) كما هو.
        $asOfDate = $request->date('as_of');
        $asOf = $asOfDate ? $asOfDate->toDateString() : now()->toDateString();

        // ===== الأصول =====
        // النقدية: أرصدة الحسابات البنكية النشطة.
        if ($asOfDate) {
            // كما في تاريخ معيّن: الافتتاحي + الإيداعات − المسحوبات حتى التاريخ، لكل حساب نشط، بـ bcmath.
            $cash = BankAccount::where('is_active', true)->get()->reduce(
                function (string $carry, BankAccount $acc) use ($asOfDate) {
                    $deposits = (string) $acc->transactions()
                        ->where('type', 'deposit')
                        ->whereDate('transaction_date', '<=', $asOfDate)
                        ->sum('amount');
                    $withdrawals = (string) $acc->transactions()
                        ->where('type', 'withdrawal')
                        ->whereDate('transaction_date', '<=', $asOfDate)
                        ->sum('amount');
                    $balance = bcadd(bcsub((string) $acc->opening_balance, $withdrawals, 2), $deposits, 2);

                    return bcadd($carry, $balance, 2);
                },
                '0'
            );
        } else {
            $cash = (string) BankAccount::where('is_active', true)->sum('current_balance');
        }

        // المخزون: مجموع قيمة المواد (الكمية × سعر الوحدة). يبقى بالقيمة الحالية.
        $inventory = Material::all()->reduce(
            fn (string $carry, Material $m) => bcadd($carry, $m->stockValue(), 2),
            '0'
        );

        // الأصول الثابتة: صافي القيمة الدفترية بعد الإهلاك (من موديل الأصل) — باستثناء المُباعة/المستبعدة.
        $fixedAssets = Asset::whereNotIn('status', ['sold', 'disposed'])->get()->reduce(
            fn (string $carry, Asset $a) => bcadd($carry, $a->bookValue(), 2),
            '0'
        );

        // الذمم المدينة: المتبقّي على الفواتير غير الملغاة + المتبقّي على الإيرادات غير المحصّلة بالكامل.
        // كما في تاريخ معيّن: الفواتير المُصدرة حتى التاريخ، والإيرادات بتاريخ إيراد حتى التاريخ.
        $invoiceReceivables = Invoice::where('status', '!=', 'cancelled')
            ->when($asOfDate, fn ($q) => $q->whereDate('issue_date', '<=', $asOfDate))
            ->get()->reduce(
                fn (string $carry, Invoice $inv) => bcadd($carry, $inv->remaining(), 2),
                '0'
            );
        $revenueReceivables = Revenue::where('payment_status', '!=', 'collected')
            ->when($asOfDate, fn ($q) => $q->whereDate('revenue_date', '<=', $asOfDate))
            ->get()->reduce(
                fn (string $carry, Revenue $rev) => bcadd($carry, $rev->remaining(), 2),
                '0'
            );
        $receivables = bcadd($invoiceReceivables, $revenueReceivables, 2);

        $totalAssets = array_reduce(
            [$cash, $inventory, $fixedAssets, $receivables],
            fn (string $carry, string $v) => bcadd($carry, $v, 2),
            '0'
        );

        // ===== الخصوم =====
        // الذمم الدائنة: أرصدة المورّدين والمقاولين المستحقّة (موجبة فقط).
        $supplierPayables = Supplier::all()->reduce(
            fn (string $carry, Supplier $s) => bcadd($carry, $this->positive($s->balanceDue()), 2),
            '0'
        );
        $contractorPayables = Contractor::all()->reduce(
            fn (string $carry, Contractor $c) => bcadd($carry, $this->positive($c->balanceDue()), 2),
            '0'
        );
        $payables = bcadd($supplierPayables, $contractorPayables, 2);

        // ===== حقوق الملكية =====
        // رأس مال الشركاء: مجموع رأس المال النشط.
        $partnerCapital = Partner::all()->reduce(
            fn (string $carry, Partner $p) => bcadd($carry, $p->activeCapital(), 2),
            '0'
        );

        // الأرباح المحتجزة = إجمالي الإيرادات − إجمالي المصروفات (حتى تاريخ "كما في" إن وُجد).
        $totalRevenue = (string) Revenue::query()
            ->when($asOfDate, fn ($q) => $q->whereDate('revenue_date', '<=', $asOfDate))
            ->sum('amount');
        $totalExpense = (string) Expense::query()
            ->when($asOfDate, fn ($q) => $q->whereDate('expense_date', '<=', $asOfDate))
            ->sum('amount');
        $retainedEarnings = bcsub($totalRevenue, $totalExpense, 2);

        $totalEquity = bcadd($partnerCapital, $retainedEarnings, 2);

        $totalLiabilitiesPlusEquity = bcadd($payables, $totalEquity, 2);

        // فرق التسوية (النموذج المبسّط قد لا يتوازن تماماً).
        $settlementDifference = bcsub($totalAssets, $totalLiabilitiesPlusEquity, 2);

        $format = $request->query('format');
        if ($format === 'pdf' || $format === 'xlsx') {
            $rows = [
                ['الأصول', ''],
                ['النقدية والحسابات البنكية', number_format((float) $cash, 2)],
                ['المخزون (المواد)', number_format((float) $inventory, 2)],
                ['الأصول الثابتة (صافي القيمة الدفترية)', number_format((float) $fixedAssets, 2)],
                ['الذمم المدينة', number_format((float) $receivables, 2)],
                ['إجمالي الأصول', number_format((float) $totalAssets, 2)],
                ['الخصوم', ''],
                ['مستحقّات المورّدين', number_format((float) $supplierPayables, 2)],
                ['مستحقّات المقاولين', number_format((float) $contractorPayables, 2)],
                ['إجمالي الخصوم', number_format((float) $payables, 2)],
                ['حقوق الملكية', ''],
                ['رأس مال الشركاء', number_format((float) $partnerCapital, 2)],
                ['الأرباح المحتجزة', number_format((float) $retainedEarnings, 2)],
                ['إجمالي حقوق الملكية', number_format((float) $totalEquity, 2)],
                ['إجمالي الخصوم وحقوق الملكية', number_format((float) $totalLiabilitiesPlusEquity, 2)],
                ['فرق التسوية', number_format((float) $settlementDifference, 2)],
            ];
            $headers = ['البند', 'القيمة'];
            $title = 'الميزانية العمومية — لقطة بتاريخ '.$asOf;

            if ($format === 'xlsx') {
                return app(ExportService::class)->excel($headers, $rows, 'balance_sheet_'.$asOf, $title);
            }

            $html = $this->buildSimpleHtml($title, $headers, $rows);

            return app(ExportService::class)->pdf($html, 'balance_sheet_'.$asOf.'.pdf');
        }

        return view('reports.balance_sheet', [
            'cash' => $cash,
            'inventory' => $inventory,
            'fixedAssets' => $fixedAssets,
            'receivables' => $receivables,
            'invoiceReceivables' => $invoiceReceivables,
            'revenueReceivables' => $revenueReceivables,
            'totalAssets' => $totalAssets,
            'supplierPayables' => $supplierPayables,
            'contractorPayables' => $contractorPayables,
            'payables' => $payables,
            'partnerCapital' => $partnerCapital,
            'retainedEarnings' => $retainedEarnings,
            'totalEquity' => $totalEquity,
            'totalLiabilitiesPlusEquity' => $totalLiabilitiesPlusEquity,
            'settlementDifference' => $settlementDifference,
            'asOf' => $asOf,
        ]);
    }

    /**
     * قائمة الدخل — إيرادات ثم تكلفة المبيعات ثم مجمل الربح ثم المصروفات التشغيلية ثم صافي الربح.
     * فلتر تاريخ اختياري على أعمدة التاريخ الخاصة بكل مصدر.
     */
    public function incomeStatement(Request $request)
    {
        $from = $request->date('from');
        $to = $request->date('to');

        // الإيرادات.
        $revenue = (string) Revenue::query()
            ->when($from, fn ($q) => $q->whereDate('revenue_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('revenue_date', '<=', $to))
            ->sum('amount');

        // تكلفة المبيعات (COGS): مستخلصات المقاولين المعتمدة + توريدات المورّدين + مصروفات التشغيل المباشرة.
        $extractsCogs = (string) ContractorExtract::query()
            ->whereIn('status', ['approved', 'partial', 'paid'])
            ->when($from, fn ($q) => $q->whereDate('extract_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('extract_date', '<=', $to))
            ->sum('net_amount');

        $supplierCogs = (string) SupplierTransaction::query()
            ->when($from, fn ($q) => $q->whereDate('transaction_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('transaction_date', '<=', $to))
            ->sum('net_amount');

        $directExpenseCogs = (string) Expense::query()
            ->whereIn('category', ['materials', 'labor', 'equipment', 'transportation'])
            ->when($from, fn ($q) => $q->whereDate('expense_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('expense_date', '<=', $to))
            ->sum('amount');

        $cogs = array_reduce(
            [$extractsCogs, $supplierCogs, $directExpenseCogs],
            fn (string $carry, string $v) => bcadd($carry, $v, 2),
            '0'
        );

        $grossProfit = bcsub($revenue, $cogs, 2);

        // المصروفات التشغيلية.
        $operatingExpenses = (string) Expense::query()
            ->whereIn('category', ['utilities', 'administrative', 'other'])
            ->when($from, fn ($q) => $q->whereDate('expense_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('expense_date', '<=', $to))
            ->sum('amount');

        $netProfit = bcsub($grossProfit, $operatingExpenses, 2);

        // الهوامش (للعرض فقط).
        $grossMargin = bccomp($revenue, '0', 2) > 0
            ? (float) bcmul(bcdiv($grossProfit, $revenue, 6), '100', 4)
            : 0.0;
        $netMargin = bccomp($revenue, '0', 2) > 0
            ? (float) bcmul(bcdiv($netProfit, $revenue, 6), '100', 4)
            : 0.0;

        $format = $request->query('format');
        if ($format === 'pdf' || $format === 'xlsx') {
            $period = ($from || $to)
                ? 'عن الفترة '.($from?->toDateString() ?: '...').' — '.($to?->toDateString() ?: '...')
                : 'كل الفترات';
            $rows = [
                ['الإيرادات', ''],
                ['إجمالي الإيرادات', number_format((float) $revenue, 2)],
                ['تكلفة المبيعات', ''],
                ['مستخلصات المقاولين المعتمدة', number_format((float) $extractsCogs, 2)],
                ['توريدات المورّدين', number_format((float) $supplierCogs, 2)],
                ['مصروفات مباشرة (مواد/عمالة/معدات/نقل)', number_format((float) $directExpenseCogs, 2)],
                ['إجمالي تكلفة المبيعات', number_format((float) $cogs, 2)],
                ['مجمل الربح', number_format((float) $grossProfit, 2)],
                ['المصروفات التشغيلية', ''],
                ['مصروفات تشغيلية (مرافق/إدارية/أخرى)', number_format((float) $operatingExpenses, 2)],
                ['صافي الربح / الخسارة', number_format((float) $netProfit, 2)],
            ];
            $headers = ['البند', 'القيمة'];
            $title = 'قائمة الدخل — '.$period;

            if ($format === 'xlsx') {
                return app(ExportService::class)->excel($headers, $rows, 'income_statement', $title);
            }

            $html = $this->buildSimpleHtml($title, $headers, $rows);

            return app(ExportService::class)->pdf($html, 'income_statement.pdf');
        }

        return view('reports.income_statement', [
            'from' => $from?->toDateString(),
            'to' => $to?->toDateString(),
            'revenue' => $revenue,
            'extractsCogs' => $extractsCogs,
            'supplierCogs' => $supplierCogs,
            'directExpenseCogs' => $directExpenseCogs,
            'cogs' => $cogs,
            'grossProfit' => $grossProfit,
            'operatingExpenses' => $operatingExpenses,
            'netProfit' => $netProfit,
            'grossMargin' => $grossMargin,
            'netMargin' => $netMargin,
        ]);
    }

    /**
     * قائمة دخل المشروع — P&L مفصّلة لمشروع واحد، متّسقة مع أساس التكلفة في AnalyticsController::projectProfitability.
     * التكلفة المباشرة = مستخلصات المقاولين (معتمد/جزئي/مدفوع) + توريدات المورّدين + تكاليف المشروع + مصروفات المشروع.
     * كله بالـbcmath حفاظاً على الدقّة.
     */
    public function projectIncome(Request $request)
    {
        $projects = Project::orderBy('name')->get();

        $projectId = (int) $request->query('project_id', 0);
        $project = $projectId > 0
            ? $projects->firstWhere('id', $projectId)
            : $projects->first();

        // لا توجد مشاريع: نعرض الصفحة فارغة بدون انهيار.
        if (! $project) {
            if (in_array($request->query('format'), ['pdf', 'xlsx'], true)) {
                return back();
            }

            return view('reports.project_income', [
                'projects' => $projects,
                'project' => null,
            ]);
        }

        // الإيرادات.
        $revenue = (string) Revenue::where('project_id', $project->id)->sum('amount');
        $collected = (string) Revenue::where('project_id', $project->id)->sum('paid_amount');
        $remainingRevenue = bcsub($revenue, $collected, 2);

        // بنود التكلفة المباشرة (مفصّلة) — نفس أساس actualCost في AnalyticsController.
        $contractorExtracts = (string) ContractorExtract::where('project_id', $project->id)
            ->whereIn('status', ['approved', 'partial', 'paid'])
            ->sum('net_amount');
        $supplierSupplies = (string) SupplierTransaction::where('project_id', $project->id)->sum('net_amount');
        $projectCosts = (string) ProjectCost::where('project_id', $project->id)->sum('amount');
        $projectExpenses = (string) Expense::where('project_id', $project->id)->sum('amount');

        $totalCost = array_reduce(
            [$contractorExtracts, $supplierSupplies, $projectCosts, $projectExpenses],
            fn (string $carry, string $v) => bcadd($carry, $v, 2),
            '0'
        );

        $grossProfit = bcsub($revenue, $totalCost, 2);
        $margin = bccomp($revenue, '0', 2) > 0
            ? bcmul(bcdiv($grossProfit, $revenue, 6), '100', 2)
            : '0';

        $contractValue = (string) $project->contract_value;
        $varianceVsContract = bcsub($contractValue, $totalCost, 2);

        $format = $request->query('format');
        if ($format === 'pdf' || $format === 'xlsx') {
            $rows = [
                ['الإيرادات', ''],
                ['إجمالي الإيرادات', number_format((float) $revenue, 2)],
                ['المحصّل', number_format((float) $collected, 2)],
                ['المتبقّي', number_format((float) $remainingRevenue, 2)],
                ['التكاليف المباشرة', ''],
                ['مستخلصات المقاولين', number_format((float) $contractorExtracts, 2)],
                ['توريدات المورّدين', number_format((float) $supplierSupplies, 2)],
                ['تكاليف المشروع', number_format((float) $projectCosts, 2)],
                ['مصروفات المشروع', number_format((float) $projectExpenses, 2)],
                ['إجمالي التكاليف المباشرة', number_format((float) $totalCost, 2)],
                ['مجمل / صافي ربح المشروع', number_format((float) $grossProfit, 2)],
                ['هامش الربح %', number_format((float) $margin, 2)],
                ['قيمة العقد', number_format((float) $contractValue, 2)],
                ['الفرق (قيمة العقد − التكلفة)', number_format((float) $varianceVsContract, 2)],
            ];
            $headers = ['البند', 'القيمة'];
            $title = 'قائمة دخل المشروع — '.$project->name;

            if ($format === 'xlsx') {
                return app(ExportService::class)->excel($headers, $rows, 'project_income_'.$project->id, $title);
            }

            $html = $this->buildSimpleHtml($title, $headers, $rows);

            return app(ExportService::class)->pdf($html, 'project_income_'.$project->id.'.pdf');
        }

        return view('reports.project_income', [
            'projects' => $projects,
            'project' => $project,
            'revenue' => $revenue,
            'collected' => $collected,
            'remainingRevenue' => $remainingRevenue,
            'contractorExtracts' => $contractorExtracts,
            'supplierSupplies' => $supplierSupplies,
            'projectCosts' => $projectCosts,
            'projectExpenses' => $projectExpenses,
            'totalCost' => $totalCost,
            'grossProfit' => $grossProfit,
            'margin' => $margin,
            'contractValue' => $contractValue,
            'varianceVsContract' => $varianceVsContract,
        ]);
    }

    /**
     * تقرير العمل تحت التنفيذ (WIP) — موقف تنفيذي/مالي لكل المشاريع:
     * قيمة العقد المعدّلة (+ أوامر التغيير المعتمدة)، نسبة الإنجاز (من المراحل)،
     * القيمة المكتسبة، المُفوتر، المحصّل، التكلفة الفعلية، فائض/عجز الفوترة، الربح المقدّر.
     */
    public function workInProgress(Request $request)
    {
        $projects = Project::whereNotIn('status', ['cancelled'])->orderBy('name')->get();

        $rows = $projects->map(function (Project $p) {
            // قيمة العقد المعدّلة = الأصلي + صافي أوامر التغيير المعتمدة
            $coAdd = (string) ChangeOrder::where('project_id', $p->id)->where('status', 'approved')->where('change_type', 'addition')->sum('amount');
            $coDed = (string) ChangeOrder::where('project_id', $p->id)->where('status', 'approved')->where('change_type', 'deduction')->sum('amount');
            $coNet = bcsub($coAdd, $coDed, 2);
            $revised = bcadd((string) $p->contract_value, $coNet, 2);

            // نسبة الإنجاز = متوسط تقدّم المراحل (إن وُجدت)
            $milestones = $p->milestones;
            $percent = $milestones->count() > 0
                ? round($milestones->avg('progress_percent'))
                : 0;

            // القيمة المكتسبة = العقد المعدّل × النسبة
            $earned = bcdiv(bcmul($revised, (string) $percent, 4), '100', 2);

            // المُفوتر والمحصّل من الفواتير (عدا الملغاة)
            $invoiced = (string) Invoice::where('project_id', $p->id)->where('status', '!=', 'cancelled')->sum('total_amount');
            $collected = (string) Invoice::where('project_id', $p->id)->where('status', '!=', 'cancelled')->sum('paid_amount');

            // التكلفة الفعلية (نفس أساس قائمة دخل المشروع)
            $cost = array_reduce([
                (string) ContractorExtract::where('project_id', $p->id)->whereIn('status', ['approved', 'partial', 'paid'])->sum('net_amount'),
                (string) SupplierTransaction::where('project_id', $p->id)->sum('net_amount'),
                (string) ProjectCost::where('project_id', $p->id)->sum('amount'),
                (string) Expense::where('project_id', $p->id)->sum('amount'),
            ], fn (string $c, string $v) => bcadd($c, $v, 2), '0');

            // فائض/عجز الفوترة = المُفوتر − القيمة المكتسبة (موجب = فوترة زائدة)
            $overUnder = bcsub($invoiced, $earned, 2);
            // الربح المقدّر = القيمة المكتسبة − التكلفة الفعلية
            $profit = bcsub($earned, $cost, 2);

            return [
                'id' => $p->id,
                'name' => $p->name,
                'status' => Project::STATUSES[$p->status] ?? $p->status,
                'revised' => $revised,
                'percent' => $percent,
                'earned' => $earned,
                'invoiced' => $invoiced,
                'collected' => $collected,
                'cost' => $cost,
                'over_under' => $overUnder,
                'profit' => $profit,
            ];
        });

        $totals = [
            'revised' => $rows->reduce(fn ($c, $r) => bcadd($c, $r['revised'], 2), '0'),
            'earned' => $rows->reduce(fn ($c, $r) => bcadd($c, $r['earned'], 2), '0'),
            'invoiced' => $rows->reduce(fn ($c, $r) => bcadd($c, $r['invoiced'], 2), '0'),
            'collected' => $rows->reduce(fn ($c, $r) => bcadd($c, $r['collected'], 2), '0'),
            'cost' => $rows->reduce(fn ($c, $r) => bcadd($c, $r['cost'], 2), '0'),
            'over_under' => $rows->reduce(fn ($c, $r) => bcadd($c, $r['over_under'], 2), '0'),
            'profit' => $rows->reduce(fn ($c, $r) => bcadd($c, $r['profit'], 2), '0'),
        ];

        if ($request->query('format') === 'xlsx') {
            $headers = ['المشروع', 'الحالة', 'العقد المعدّل', 'الإنجاز %', 'القيمة المكتسبة', 'المُفوتر', 'المحصّل', 'التكلفة الفعلية', 'فائض/عجز الفوترة', 'الربح المقدّر'];
            $data = $rows->map(fn ($r) => [
                $r['name'], $r['status'], number_format((float) $r['revised'], 2), $r['percent'].'%',
                number_format((float) $r['earned'], 2), number_format((float) $r['invoiced'], 2),
                number_format((float) $r['collected'], 2), number_format((float) $r['cost'], 2),
                number_format((float) $r['over_under'], 2), number_format((float) $r['profit'], 2),
            ])->all();

            return app(ExportService::class)->excel($headers, $data, 'work_in_progress', 'تقرير العمل تحت التنفيذ (WIP)');
        }

        return view('reports.work_in_progress', compact('rows', 'totals'));
    }

    /**
     * تقرير الضرائب — ضريبة القيمة المضافة (مخرجات/مدخلات/صافي) + سجلّات الضرائب مجمّعة بالنوع.
     * فلتر تاريخ اختياري.
     */
    public function taxReport(Request $request): View
    {
        $from = $request->date('from');
        $to = $request->date('to');

        // ضريبة المخرجات: ضريبة الفواتير الصادرة.
        $outputVat = (string) Invoice::query()
            ->where('status', '!=', 'cancelled')
            ->when($from, fn ($q) => $q->whereDate('issue_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('issue_date', '<=', $to))
            ->sum('tax_amount');

        // ضريبة المدخلات: ضريبة القيمة المضافة في مدفوعات المورّدين.
        $inputVat = (string) SupplierPayment::query()
            ->when($from, fn ($q) => $q->whereDate('payment_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('payment_date', '<=', $to))
            ->sum('vat');

        $netVat = bcsub($outputVat, $inputVat, 2);

        // سجلّات الضرائب مجمّعة حسب النوع.
        $taxesByType = Tax::query()
            ->where('status', '!=', 'cancelled')
            ->when($from, fn ($q) => $q->whereDate('due_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('due_date', '<=', $to))
            ->selectRaw('tax_type, SUM(amount) as total')
            ->groupBy('tax_type')
            ->pluck('total', 'tax_type');

        return view('reports.taxes', [
            'from' => $from?->toDateString(),
            'to' => $to?->toDateString(),
            'outputVat' => $outputVat,
            'inputVat' => $inputVat,
            'netVat' => $netVat,
            'taxesByType' => $taxesByType,
        ]);
    }

    /**
     * التدفّق النقدي للشركة من حركات الحسابات البنكية.
     * التدفّقات الداخلة = الإيداعات، الخارجة = المسحوبات، مع استبعاد التحويلات بين الحسابات
     * (related_type='bank_transfer') لأنها تتعادل داخلياً. مجمّعة حسب الشهر مع رصيد نقدي تراكمي.
     */
    public function cashFlow(Request $request)
    {
        $from = $request->date('from');
        $to = $request->date('to');

        $transactions = BankTransaction::query()
            ->where(function ($q) {
                $q->whereNull('related_type')->orWhere('related_type', '!=', 'bank_transfer');
            })
            ->when($from, fn ($q) => $q->whereDate('transaction_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('transaction_date', '<=', $to))
            ->orderBy('transaction_date')
            ->get();

        $grouped = $transactions->groupBy(fn ($t) => $t->transaction_date->format('Y-m'));

        $running = '0';
        $months = [];
        $totalInflow = '0';
        $totalOutflow = '0';

        foreach ($grouped->sortKeys() as $month => $items) {
            $inflow = '0';
            $outflow = '0';
            foreach ($items as $t) {
                if ($t->type === 'deposit') {
                    $inflow = bcadd($inflow, (string) $t->amount, 2);
                } elseif ($t->type === 'withdrawal') {
                    $outflow = bcadd($outflow, (string) $t->amount, 2);
                }
            }
            $net = bcsub($inflow, $outflow, 2);
            $running = bcadd($running, $net, 2);
            $totalInflow = bcadd($totalInflow, $inflow, 2);
            $totalOutflow = bcadd($totalOutflow, $outflow, 2);

            $months[] = [
                'month' => $month,
                'inflow' => $inflow,
                'outflow' => $outflow,
                'net' => $net,
                'running' => $running,
            ];
        }

        $totalNet = bcsub($totalInflow, $totalOutflow, 2);

        $format = $request->query('format');
        if ($format === 'pdf' || $format === 'xlsx') {
            $headers = ['الشهر', 'تدفّق داخل', 'تدفّق خارج', 'الصافي', 'الرصيد التراكمي'];
            $rows = [];
            foreach ($months as $m) {
                $rows[] = [
                    $m['month'],
                    number_format((float) $m['inflow'], 2),
                    number_format((float) $m['outflow'], 2),
                    number_format((float) $m['net'], 2),
                    number_format((float) $m['running'], 2),
                ];
            }
            $rows[] = [
                'الإجمالي',
                number_format((float) $totalInflow, 2),
                number_format((float) $totalOutflow, 2),
                number_format((float) $totalNet, 2),
                number_format((float) $running, 2),
            ];
            $title = 'التدفّق النقدي'.(($from || $to) ? ' — '.($from?->toDateString() ?: '...').' إلى '.($to?->toDateString() ?: '...') : '');

            if ($format === 'xlsx') {
                return app(ExportService::class)->excel($headers, $rows, 'cash_flow', $title);
            }

            $html = $this->buildSimpleHtml($title, $headers, $rows);

            return app(ExportService::class)->pdf($html, 'cash_flow.pdf');
        }

        return view('reports.cash_flow', [
            'from' => $from?->toDateString(),
            'to' => $to?->toDateString(),
            'months' => $months,
            'totalInflow' => $totalInflow,
            'totalOutflow' => $totalOutflow,
            'totalNet' => $totalNet,
            'closingCash' => $running,
        ]);
    }

    /**
     * أعمار الذمم المدينة (AR Aging): الفواتير غير الملغاة ذات متبقّي > 0 (حسب تاريخ الاستحقاق/الإصدار)
     * + الإيرادات غير المحصّلة بالكامل (حسب تاريخ الاستحقاق/الإيراد). مجمّعة حسب العميل
     * مع شرائح أعمار: 0-30 / 31-60 / 61-90 / 90+.
     */
    public function arAging(Request $request)
    {
        $today = Carbon::today();
        $clients = [];

        $ensure = function (string &$key, string $name) use (&$clients) {
            if (! isset($clients[$key])) {
                $clients[$key] = [
                    'name' => $name,
                    'b0' => '0', 'b30' => '0', 'b60' => '0', 'b90' => '0', 'total' => '0',
                ];
            }
        };

        $invoices = Invoice::where('status', '!=', 'cancelled')
            ->with('client')->get();
        foreach ($invoices as $inv) {
            $remaining = $inv->remaining();
            if (bccomp($remaining, '0', 2) <= 0) {
                continue;
            }
            $ageDate = $inv->due_date ?: $inv->issue_date;
            $key = 'client_'.($inv->client_id ?: '0');
            $name = $inv->client?->name ?: 'عميل غير محدّد';
            $ensure($key, $name);
            $this->addToBucket($clients[$key], $remaining, $ageDate, $today);
        }

        $revenues = Revenue::where('payment_status', '!=', 'collected')
            ->with('project.client')->get();
        foreach ($revenues as $rev) {
            $remaining = $rev->remaining();
            if (bccomp($remaining, '0', 2) <= 0) {
                continue;
            }
            $ageDate = $rev->due_date ?: $rev->revenue_date;
            $client = $rev->project?->client;
            if ($client) {
                $key = 'client_'.$client->id;
                $name = $client->name;
            } else {
                $key = 'general';
                $name = 'إيرادات عامة';
            }
            $ensure($key, $name);
            $this->addToBucket($clients[$key], $remaining, $ageDate, $today);
        }

        // ترتيب حسب الإجمالي تنازلياً.
        uasort($clients, fn ($a, $b) => bccomp($b['total'], $a['total'], 2));

        $totals = ['b0' => '0', 'b30' => '0', 'b60' => '0', 'b90' => '0', 'total' => '0'];
        foreach ($clients as $c) {
            foreach (['b0', 'b30', 'b60', 'b90', 'total'] as $k) {
                $totals[$k] = bcadd($totals[$k], $c[$k], 2);
            }
        }

        $format = $request->query('format');
        if ($format === 'pdf' || $format === 'xlsx') {
            $headers = ['العميل', '0-30 يوم', '31-60 يوم', '61-90 يوم', '90+ يوم', 'الإجمالي'];
            $rows = [];
            foreach ($clients as $c) {
                $rows[] = [
                    $c['name'],
                    number_format((float) $c['b0'], 2),
                    number_format((float) $c['b30'], 2),
                    number_format((float) $c['b60'], 2),
                    number_format((float) $c['b90'], 2),
                    number_format((float) $c['total'], 2),
                ];
            }
            $rows[] = [
                'الإجمالي',
                number_format((float) $totals['b0'], 2),
                number_format((float) $totals['b30'], 2),
                number_format((float) $totals['b60'], 2),
                number_format((float) $totals['b90'], 2),
                number_format((float) $totals['total'], 2),
            ];
            $title = 'أعمار الذمم المدينة بتاريخ '.$today->toDateString();

            if ($format === 'xlsx') {
                return app(ExportService::class)->excel($headers, $rows, 'ar_aging', $title);
            }

            $html = $this->buildSimpleHtml($title, $headers, $rows);

            return app(ExportService::class)->pdf($html, 'ar_aging.pdf');
        }

        return view('reports.ar_aging', [
            'clients' => array_values($clients),
            'totals' => $totals,
            'asOf' => $today->toDateString(),
        ]);
    }

    /**
     * أعمار الذمم الدائنة (AP Aging): المورّدون والمقاولون ذوو رصيد مستحقّ > 0.
     * يُحتسب العمر من تاريخ أقدم مستند مصدر غير مسدّد (أقدم أمر شراء للمورّد / أقدم مستخلص غير مسدّد
     * للمقاول، وإلا تاريخ اليوم). شرائح: 0-30 / 31-60 / 61-90 / 90+.
     */
    public function apAging(Request $request)
    {
        $today = Carbon::today();
        $rows = [];

        $totals = ['b0' => '0', 'b30' => '0', 'b60' => '0', 'b90' => '0', 'total' => '0'];

        $suppliers = Supplier::with(['purchaseOrders' => function ($q) {
            $q->orderBy('order_date');
        }])->get();
        foreach ($suppliers as $s) {
            $balance = $s->balanceDue();
            if (bccomp($balance, '0', 2) <= 0) {
                continue;
            }
            $oldest = $s->purchaseOrders
                ->whereIn('status', ['partial', 'received'])
                ->sortBy('order_date')
                ->first()?->order_date;
            $bucket = $this->initBucket($s->name, 'مورّد');
            $this->addToBucket($bucket, $balance, $oldest ?: $today, $today);
            $rows[] = $bucket;
        }

        $contractors = Contractor::with(['extracts' => function ($q) {
            $q->orderBy('extract_date');
        }])->get();
        foreach ($contractors as $c) {
            $balance = $c->balanceDue();
            if (bccomp($balance, '0', 2) <= 0) {
                continue;
            }
            $oldest = $c->extracts
                ->whereIn('status', ['approved', 'partial', 'paid'])
                ->filter(fn ($e) => bccomp($e->remaining(), '0', 2) > 0)
                ->sortBy('extract_date')
                ->first()?->extract_date;
            $bucket = $this->initBucket($c->name, 'مقاول');
            $this->addToBucket($bucket, $balance, $oldest ?: $today, $today);
            $rows[] = $bucket;
        }

        usort($rows, fn ($a, $b) => bccomp($b['total'], $a['total'], 2));

        foreach ($rows as $r) {
            foreach (['b0', 'b30', 'b60', 'b90', 'total'] as $k) {
                $totals[$k] = bcadd($totals[$k], $r[$k], 2);
            }
        }

        $format = $request->query('format');
        if ($format === 'pdf' || $format === 'xlsx') {
            $headers = ['الجهة', 'النوع', '0-30 يوم', '31-60 يوم', '61-90 يوم', '90+ يوم', 'الإجمالي'];
            $exportRows = [];
            foreach ($rows as $r) {
                $exportRows[] = [
                    $r['name'],
                    $r['kind'],
                    number_format((float) $r['b0'], 2),
                    number_format((float) $r['b30'], 2),
                    number_format((float) $r['b60'], 2),
                    number_format((float) $r['b90'], 2),
                    number_format((float) $r['total'], 2),
                ];
            }
            $exportRows[] = [
                'الإجمالي',
                '',
                number_format((float) $totals['b0'], 2),
                number_format((float) $totals['b30'], 2),
                number_format((float) $totals['b60'], 2),
                number_format((float) $totals['b90'], 2),
                number_format((float) $totals['total'], 2),
            ];
            $title = 'أعمار الذمم الدائنة بتاريخ '.$today->toDateString();

            if ($format === 'xlsx') {
                return app(ExportService::class)->excel($headers, $exportRows, 'ap_aging', $title);
            }

            $html = $this->buildSimpleHtml($title, $headers, $exportRows);

            return app(ExportService::class)->pdf($html, 'ap_aging.pdf');
        }

        return view('reports.ap_aging', [
            'rows' => $rows,
            'totals' => $totals,
            'asOf' => $today->toDateString(),
        ]);
    }

    /**
     * مقارنة الفترات: الفترة الحالية مقابل فترة سابقة بنفس الطول.
     * تُظهر الإيراد والمصروف والصافي مع القيمة السابقة ومقدار التغيّر ونسبته.
     */
    public function periodComparison(Request $request): View
    {
        $from = $request->date('from') ?: Carbon::today()->startOfMonth();
        $to = $request->date('to') ?: Carbon::today();

        // طول الفترة بالأيام (شامل).
        $lengthDays = $from->diffInDays($to) + 1;
        $prevTo = (clone $from)->subDay();
        $prevFrom = (clone $prevTo)->subDays($lengthDays - 1);

        $current = $this->periodFigures($from, $to);
        $previous = $this->periodFigures($prevFrom, $prevTo);

        $metrics = [];
        foreach (['revenue' => 'الإيرادات', 'expense' => 'المصروفات', 'net' => 'الصافي'] as $key => $label) {
            $cur = $current[$key];
            $prev = $previous[$key];
            $change = bcsub($cur, $prev, 2);
            $absPrev = str_starts_with($prev, '-') ? substr($prev, 1) : $prev;
            $pct = bccomp($absPrev, '0', 2) !== 0
                ? (float) bcmul(bcdiv($change, $absPrev, 6), '100', 2)
                : null;
            $metrics[] = [
                'label' => $label,
                'current' => $cur,
                'previous' => $prev,
                'change' => $change,
                'pct' => $pct,
            ];
        }

        return view('reports.period_comparison', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'prevFrom' => $prevFrom->toDateString(),
            'prevTo' => $prevTo->toDateString(),
            'metrics' => $metrics,
        ]);
    }

    /** إيراد ومصروف وصافي فترة معيّنة (سلاسل bcmath). */
    private function periodFigures(Carbon $from, Carbon $to): array
    {
        $revenue = (string) Revenue::whereDate('revenue_date', '>=', $from)
            ->whereDate('revenue_date', '<=', $to)
            ->sum('amount');
        $expense = (string) Expense::whereDate('expense_date', '>=', $from)
            ->whereDate('expense_date', '<=', $to)
            ->sum('amount');

        return [
            'revenue' => bcadd($revenue, '0', 2),
            'expense' => bcadd($expense, '0', 2),
            'net' => bcsub($revenue, $expense, 2),
        ];
    }

    /** يُهيّئ صفّ شريحة أعمار بقيم صفرية. */
    private function initBucket(string $name, string $kind): array
    {
        return [
            'name' => $name,
            'kind' => $kind,
            'b0' => '0', 'b30' => '0', 'b60' => '0', 'b90' => '0', 'total' => '0',
        ];
    }

    /** يضيف مبلغاً إلى الشريحة المناسبة بحسب عمره بالأيام من اليوم. */
    private function addToBucket(array &$bucket, string $amount, $ageDate, Carbon $today): void
    {
        $date = $ageDate instanceof Carbon ? $ageDate : Carbon::parse($ageDate);
        $days = $date->startOfDay()->diffInDays($today, false);
        $days = max(0, (int) $days);

        if ($days <= 30) {
            $bucket['b0'] = bcadd($bucket['b0'], $amount, 2);
        } elseif ($days <= 60) {
            $bucket['b30'] = bcadd($bucket['b30'], $amount, 2);
        } elseif ($days <= 90) {
            $bucket['b60'] = bcadd($bucket['b60'], $amount, 2);
        } else {
            $bucket['b90'] = bcadd($bucket['b90'], $amount, 2);
        }
        $bucket['total'] = bcadd($bucket['total'], $amount, 2);
    }

    /**
     * يبني HTML عربي مستقلّ (RTL) لجدول بسيط للتصدير إلى PDF.
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string>>  $rows
     */
    private function buildSimpleHtml(string $title, array $headers, array $rows): string
    {
        $th = '';
        foreach ($headers as $h) {
            $th .= '<th>'.e($h).'</th>';
        }

        $tr = '';
        foreach ($rows as $row) {
            $tr .= '<tr>';
            foreach ($row as $i => $cell) {
                $align = $i === 0 ? 'right' : 'left';
                $tr .= '<td style="text-align:'.$align.'">'.e((string) $cell).'</td>';
            }
            $tr .= '</tr>';
        }

        return '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8">'
            .'<style>'
            .'body{font-family:dejavusans,sans-serif;direction:rtl;color:#2c2417;}'
            .'h2{text-align:center;color:#5c4a32;margin:0 0 12px;}'
            .'table{width:100%;border-collapse:collapse;font-size:12px;}'
            .'th,td{border:1px solid #cbb79a;padding:6px 8px;}'
            .'th{background:#8b7355;color:#fff;text-align:center;}'
            .'tbody tr:nth-child(even){background:#faf7f2;}'
            .'</style></head><body>'
            .'<h2>'.e($title).'</h2>'
            .'<table><thead><tr>'.$th.'</tr></thead><tbody>'.$tr.'</tbody></table>'
            .'</body></html>';
    }

    /**
     * صافي القيمة الدفترية للأصل بطريقة القسط الثابت:
     * القيمة الدفترية = قيمة الشراء − مجمّع الإهلاك (مقيّداً بقيمة الشراء).
     * الأصول المُباعة/المُستبعدة/المُستهلكة بالكامل لا تُحمَّل بقيمة.
     */
    private function assetNetBookValue(Asset $asset): string
    {
        if (in_array($asset->status, ['sold', 'disposed', 'fully_depreciated'], true)) {
            return '0.00';
        }

        $purchaseValue = (string) $asset->purchase_value;

        if (! $asset->purchase_date) {
            return $purchaseValue;
        }

        $years = (string) max(0, Carbon::parse($asset->purchase_date)->floatDiffInYears(now()));
        $annualDep = bcdiv(bcmul($purchaseValue, (string) $asset->depreciation_rate, 6), '100', 6);
        $accumulated = bcmul($annualDep, $years, 6);

        // قيّد مجمّع الإهلاك بقيمة الشراء.
        if (bccomp($accumulated, $purchaseValue, 2) > 0) {
            $accumulated = $purchaseValue;
        }

        return bcsub($purchaseValue, $accumulated, 2);
    }

    /** يُعيد القيمة لو كانت موجبة، وإلا صفر (للأرصدة الدائنة فقط). */
    private function positive(string $value): string
    {
        return bccomp($value, '0', 2) > 0 ? $value : '0.00';
    }
}
