<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BankAccount;
use App\Models\Contractor;
use App\Models\ContractorExtract;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\Partner;
use App\Models\Project;
use App\Models\Revenue;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\SupplierTransaction;
use App\Models\Tax;
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
    public function balanceSheet(Request $request): View
    {
        // ===== الأصول =====
        // النقدية: أرصدة الحسابات البنكية النشطة.
        $cash = (string) BankAccount::where('is_active', true)->sum('current_balance');

        // المخزون: مجموع قيمة المواد (الكمية × سعر الوحدة).
        $inventory = Material::all()->reduce(
            fn (string $carry, Material $m) => bcadd($carry, $m->stockValue(), 2),
            '0'
        );

        // الأصول الثابتة: صافي القيمة الدفترية بعد الإهلاك للأصول النشطة.
        $fixedAssets = Asset::all()->reduce(
            fn (string $carry, Asset $a) => bcadd($carry, $this->assetNetBookValue($a), 2),
            '0'
        );

        // الذمم المدينة: المتبقّي على الفواتير غير الملغاة + المتبقّي على الإيرادات غير المحصّلة بالكامل.
        $invoiceReceivables = Invoice::where('status', '!=', 'cancelled')->get()->reduce(
            fn (string $carry, Invoice $inv) => bcadd($carry, $inv->remaining(), 2),
            '0'
        );
        $revenueReceivables = Revenue::where('payment_status', '!=', 'collected')->get()->reduce(
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

        // الأرباح المحتجزة = إجمالي الإيرادات − إجمالي المصروفات.
        $totalRevenue = (string) Revenue::sum('amount');
        $totalExpense = (string) Expense::sum('amount');
        $retainedEarnings = bcsub($totalRevenue, $totalExpense, 2);

        $totalEquity = bcadd($partnerCapital, $retainedEarnings, 2);

        $totalLiabilitiesPlusEquity = bcadd($payables, $totalEquity, 2);

        // فرق التسوية (النموذج المبسّط قد لا يتوازن تماماً).
        $settlementDifference = bcsub($totalAssets, $totalLiabilitiesPlusEquity, 2);

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
            'asOf' => now()->toDateString(),
        ]);
    }

    /**
     * قائمة الدخل — إيرادات ثم تكلفة المبيعات ثم مجمل الربح ثم المصروفات التشغيلية ثم صافي الربح.
     * فلتر تاريخ اختياري على أعمدة التاريخ الخاصة بكل مصدر.
     */
    public function incomeStatement(Request $request): View
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
