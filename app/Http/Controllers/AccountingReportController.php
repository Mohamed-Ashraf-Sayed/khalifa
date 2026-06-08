<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntryLine;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AccountingReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:accounting.view'),
        ];
    }

    /**
     * ميزان المراجعة — لكل حساب غير تجميعي: حركة المدين والدائن والرصيد الختامي (حتى تاريخ اختياري).
     * يتحقّق من توازن مجموع الحركات ومجموع الأرصدة الختامية.
     */
    public function trialBalance(Request $request)
    {
        $to = $request->date('to');
        $toStr = $to?->toDateString();

        $accounts = Account::query()
            ->where('is_group', false)
            ->orderBy('code')
            ->get();

        $rows = [];
        $sumDebitMovement = '0';
        $sumCreditMovement = '0';
        $sumClosingDebit = '0';
        $sumClosingCredit = '0';

        foreach ($accounts as $account) {
            $debitMovement = $this->num($account->postedDebit(null, $to));
            $creditMovement = $this->num($account->postedCredit(null, $to));

            // الرصيد الختامي: موجب يعني الرصيد في الجهة الطبيعية للحساب.
            $balance = $this->num($account->balance($to));
            $natural = $this->naturalSide($account->type);

            // تجاهل الحسابات بلا حركة ولا رصيد للتقليل من الضوضاء.
            if (bccomp($debitMovement, '0', 2) === 0
                && bccomp($creditMovement, '0', 2) === 0
                && bccomp($balance, '0', 2) === 0) {
                continue;
            }

            // توزيع الرصيد الختامي على عمود مدين/دائن بحسب الإشارة والطبيعة.
            $closingDebit = '0';
            $closingCredit = '0';
            if ($natural === 'debit') {
                if (bccomp($balance, '0', 2) >= 0) {
                    $closingDebit = $balance;
                } else {
                    $closingCredit = bcmul($balance, '-1', 2);
                }
            } else {
                if (bccomp($balance, '0', 2) >= 0) {
                    $closingCredit = $balance;
                } else {
                    $closingDebit = bcmul($balance, '-1', 2);
                }
            }

            $rows[] = [
                'code' => $account->code,
                'name' => $account->name,
                'debit_movement' => $debitMovement,
                'credit_movement' => $creditMovement,
                'closing_debit' => $closingDebit,
                'closing_credit' => $closingCredit,
            ];

            $sumDebitMovement = bcadd($sumDebitMovement, $debitMovement, 2);
            $sumCreditMovement = bcadd($sumCreditMovement, $creditMovement, 2);
            $sumClosingDebit = bcadd($sumClosingDebit, $closingDebit, 2);
            $sumClosingCredit = bcadd($sumClosingCredit, $closingCredit, 2);
        }

        $movementBalanced = bccomp($sumDebitMovement, $sumCreditMovement, 2) === 0;
        $closingBalanced = bccomp($sumClosingDebit, $sumClosingCredit, 2) === 0;

        if ($request->query('export') === 'xlsx') {
            $headers = ['الكود', 'اسم الحساب', 'حركة مدينة', 'حركة دائنة', 'رصيد ختامي مدين', 'رصيد ختامي دائن'];
            $data = [];
            foreach ($rows as $r) {
                $data[] = [
                    $r['code'], $r['name'],
                    number_format((float) $r['debit_movement'], 2),
                    number_format((float) $r['credit_movement'], 2),
                    number_format((float) $r['closing_debit'], 2),
                    number_format((float) $r['closing_credit'], 2),
                ];
            }
            $data[] = [
                'الإجمالي', '',
                number_format((float) $sumDebitMovement, 2),
                number_format((float) $sumCreditMovement, 2),
                number_format((float) $sumClosingDebit, 2),
                number_format((float) $sumClosingCredit, 2),
            ];
            $title = 'ميزان المراجعة'.($toStr ? ' حتى '.$toStr : '');

            return app(ExportService::class)->excel($headers, $data, 'trial_balance', $title);
        }

        return view('reports.trial_balance', [
            'rows' => $rows,
            'to' => $toStr,
            'sumDebitMovement' => $sumDebitMovement,
            'sumCreditMovement' => $sumCreditMovement,
            'sumClosingDebit' => $sumClosingDebit,
            'sumClosingCredit' => $sumClosingCredit,
            'movementBalanced' => $movementBalanced,
            'closingBalanced' => $closingBalanced,
        ]);
    }

    /**
     * دفتر الأستاذ — حركات حساب واحد المرحّلة مرتّبة بالتاريخ ثم الرقم، مع رصيد جارٍ مشتقّ.
     */
    public function accountLedger(Request $request)
    {
        $accounts = Account::query()
            ->where('is_group', false)
            ->orderBy('code')
            ->get();

        $accountId = (int) $request->query('account_id', 0);
        $account = $accountId > 0
            ? $accounts->firstWhere('id', $accountId)
            : $accounts->first();

        if (! $account) {
            return view('reports.account_ledger', [
                'accounts' => $accounts,
                'account' => null,
                'lines' => [],
                'opening' => '0',
                'closing' => '0',
                'totalDebit' => '0',
                'totalCredit' => '0',
            ]);
        }

        $natural = $this->naturalSide($account->type);
        $opening = $this->num($account->opening_balance ?? '0');

        $entryLines = JournalEntryLine::query()
            ->where('account_id', $account->id)
            ->whereHas('entry', fn ($q) => $q->where('status', 'posted'))
            ->with('entry')
            ->get()
            ->sortBy(fn ($l) => [optional($l->entry)->entry_date?->format('Y-m-d') ?? '', $l->id])
            ->values();

        $running = $opening;
        $totalDebit = '0';
        $totalCredit = '0';
        $lines = [];

        foreach ($entryLines as $line) {
            $debit = $this->num($line->debit ?? '0');
            $credit = $this->num($line->credit ?? '0');

            // الحركة الموقّعة على اتجاه الطبيعة: مدين موجب للحسابات المدينة، والعكس.
            $signed = $natural === 'debit'
                ? bcsub($debit, $credit, 2)
                : bcsub($credit, $debit, 2);
            $running = bcadd($running, $signed, 2);

            $totalDebit = bcadd($totalDebit, $debit, 2);
            $totalCredit = bcadd($totalCredit, $credit, 2);

            $lines[] = [
                'date' => optional($line->entry)->entry_date?->toDateString() ?? '',
                'entry_number' => optional($line->entry)->entry_number ?? '',
                'description' => $line->description ?? (optional($line->entry)->description ?? ''),
                'debit' => $debit,
                'credit' => $credit,
                'running' => $running,
            ];
        }

        return view('reports.account_ledger', [
            'accounts' => $accounts,
            'account' => $account,
            'lines' => $lines,
            'opening' => $opening,
            'closing' => $running,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
        ]);
    }

    /**
     * قائمة الدخل (محاسبية من دفتر الأستاذ العام) — الإيرادات (طبيعتها دائنة) ناقص المصروفات (طبيعتها مدينة)،
     * مجمّعة حسب الحساب التجميعي الأب. صافي الربح = إجمالي الإيراد − إجمالي المصروف.
     */
    public function incomeStatement(Request $request)
    {
        // السنة المختارة (افتراضياً أحدث سنة مالية أو السنة الحالية) + سنة المقارنة السابقة
        $defaultYear = \App\Models\FiscalYear::orderByDesc('start_date')->value('name') ?: (string) now()->year;
        $year = (int) $request->query('year', $defaultYear);
        $priorYear = $year - 1;

        $cur = $this->statementFigures($year.'-01-01', $year.'-12-31');
        $prior = $this->statementFigures($priorYear.'-01-01', $priorYear.'-12-31');

        // قائمة السنوات المتاحة للاختيار
        $years = \App\Models\FiscalYear::orderByDesc('start_date')->pluck('name')->map(fn ($n) => (int) $n)->all();
        if (! in_array($year, $years, true)) {
            $years[] = $year;
            rsort($years);
        }

        $company = [
            'name' => \App\Models\Setting::get('company_name') ?: config('app.name'),
            'legal_form' => \App\Models\Setting::get('legal_form', 'شركة فردية'),
            'commercial_register' => \App\Models\Setting::get('commercial_register'),
            'tax_number' => \App\Models\Setting::get('tax_number'),
            'currency' => \App\Models\Setting::get('currency', 'ج.م'),
        ];

        // ترتيب بنود القائمة (مطابق للنموذج المصري) — مفتاح، تسمية، النوع
        $lines = $this->incomeStatementLines();

        if ($request->query('export') === 'xlsx') {
            $headers = ['بيان', (string) $year, (string) $priorYear];
            $data = [];
            foreach ($lines as $ln) {
                if ($ln['type'] === 'header') {
                    $data[] = [$ln['label'], '', ''];

                    continue;
                }
                $data[] = [
                    $ln['label'],
                    $this->fmtCell($cur[$ln['key']] ?? '0', $ln),
                    $this->fmtCell($prior[$ln['key']] ?? '0', $ln),
                ];
            }
            $title = 'قائمة الدخل عن السنة المنتهية في 31 ديسمبر '.$year;

            return app(ExportService::class)->excel($headers, $data, 'income_statement_'.$year, $title);
        }

        return view('reports.gl_income_statement', compact('year', 'priorYear', 'cur', 'prior', 'years', 'company', 'lines'));
    }

    /** تنسيق خلية للتصدير: المخصومات بين قوسين. */
    private function fmtCell(string $value, array $line): string
    {
        if (bccomp($value, '0', 2) === 0 && ($line['blank_if_zero'] ?? false)) {
            return '';
        }
        $n = number_format((float) $value, 0);

        return ($line['deduct'] ?? false) ? '('.$n.')' : $n;
    }

    /** تعريف بنود قائمة الدخل بالترتيب المعتمد. */
    private function incomeStatementLines(): array
    {
        return [
            ['key' => 'activity_revenue', 'label' => 'إيراد النشاط', 'type' => 'line'],
            ['key' => 'activity_cost', 'label' => 'يخصم: تكاليف النشاط', 'type' => 'line', 'deduct' => true],
            ['key' => 'gross_profit', 'label' => 'مجمل الربح', 'type' => 'total'],
            ['key' => '_sep1', 'label' => '(يخصم) / يضاف:', 'type' => 'header'],
            ['key' => 'admin_general', 'label' => 'مصروفات إدارية وعمومية', 'type' => 'line', 'deduct' => true, 'blank_if_zero' => true],
            ['key' => 'selling', 'label' => 'مصروفات بيعية وتسويقية', 'type' => 'line', 'deduct' => true, 'blank_if_zero' => true],
            ['key' => 'takaful', 'label' => 'المساهمة التكافلية في منظمة التأمين الصحي', 'type' => 'line', 'deduct' => true, 'blank_if_zero' => true],
            ['key' => 'financing', 'label' => 'مصروفات وفوائد تمويلية', 'type' => 'line', 'deduct' => true, 'blank_if_zero' => true],
            ['key' => 'ecl', 'label' => 'الخسائر الائتمانية المتوقعة', 'type' => 'line', 'deduct' => true, 'blank_if_zero' => true],
            ['key' => 'fx', 'label' => 'خسائر وأرباح فروق عملة', 'type' => 'line', 'blank_if_zero' => true],
            ['key' => 'other_income', 'label' => 'إيرادات أخرى', 'type' => 'line', 'blank_if_zero' => true],
            ['key' => 'net_before_tax', 'label' => 'صافي أرباح (خسائر) الفترة قبل الضرائب', 'type' => 'total'],
            ['key' => '_sep2', 'label' => 'يخصم:', 'type' => 'header'],
            ['key' => 'income_tax', 'label' => 'ضريبة الدخل', 'type' => 'line', 'deduct' => true, 'blank_if_zero' => true],
            ['key' => 'deferred_tax', 'label' => 'الضريبة المؤجلة - التزام', 'type' => 'line', 'deduct' => true, 'blank_if_zero' => true],
            ['key' => 'net_after_tax', 'label' => 'صافي أرباح (خسائر) الفترة بعد الضرائب', 'type' => 'total'],
            ['key' => 'distributions', 'label' => 'أرباح موزعة', 'type' => 'line', 'deduct' => true, 'blank_if_zero' => true],
            ['key' => 'retained', 'label' => 'أرباح محتجزة للعام التالي', 'type' => 'grand'],
        ];
    }

    /** يحسب بنود قائمة الدخل لفترة [from..to] من القيود المرحّلة. */
    private function statementFigures(string $from, string $to): array
    {
        $activityRevenue = $this->periodAmount(fn ($q) => $q->where('type', 'revenue')->where('code', '4101'), 'credit', $from, $to);
        $otherIncome = $this->periodAmount(fn ($q) => $q->where('type', 'revenue')->where('code', '!=', '4101'), 'credit', $from, $to);
        $activityCost = $this->periodAmount(fn ($q) => $q->where('type', 'expense')->where('code', 'like', '51%'), 'debit', $from, $to);

        // كل المصروفات عدا تكاليف النشاط المباشرة (51x) — تُوزَّع على بنود القائمة بحسب نطاق الكود.
        $operatingTotal = $this->periodAmount(fn ($q) => $q->where('type', 'expense')->where('code', 'not like', '51%'), 'debit', $from, $to);

        // اشتقاق البنود الفرعية من نطاقات أكواد دليل الحسابات بدلاً من تثبيتها على صفر.
        // 53x مصروفات بيعية · 54x المساهمة التكافلية · 55x خسائر ائتمانية متوقعة · 56x مصروفات تمويلية · 57x فروق عملة.
        $selling = $this->periodAmount(fn ($q) => $q->where('type', 'expense')->where('code', 'like', '53%'), 'debit', $from, $to);
        $takaful = $this->periodAmount(fn ($q) => $q->where('type', 'expense')->where('code', 'like', '54%'), 'debit', $from, $to);
        $ecl = $this->periodAmount(fn ($q) => $q->where('type', 'expense')->where('code', 'like', '55%'), 'debit', $from, $to);
        $financing = $this->periodAmount(fn ($q) => $q->where('type', 'expense')->where('code', 'like', '56%'), 'debit', $from, $to);
        $fx = $this->periodAmount(fn ($q) => $q->where('type', 'expense')->where('code', 'like', '57%'), 'debit', $from, $to);

        // الباقي (52x وما لا ينتمي لنطاق معروف) يُعرض كمصروفات إدارية وعمومية — بحيث يبقى إجمالي المصروفات التشغيلية ثابتاً.
        $adminGeneral = bcsub($operatingTotal, bcadd(bcadd(bcadd(bcadd($selling, $takaful, 2), $ecl, 2), $financing, 2), $fx, 2), 2);

        $gross = bcsub($activityRevenue, $activityCost, 2);
        $deductions = ['selling' => $selling, 'takaful' => $takaful, 'financing' => $financing, 'ecl' => $ecl, 'fx' => $fx];
        // صافي قبل الضرائب = مجمل الربح − إجمالي المصروفات التشغيلية + الإيرادات الأخرى (لا يتغيّر بإعادة التوزيع أعلاه).
        $netBeforeTax = bcadd(bcsub($gross, $operatingTotal, 2), $otherIncome, 2);
        $incomeTax = '0';
        $deferredTax = '0';
        $netAfterTax = bcsub(bcsub($netBeforeTax, $incomeTax, 2), $deferredTax, 2);
        $distributions = '0';
        $retained = bcsub($netAfterTax, $distributions, 2);

        return array_merge([
            'activity_revenue' => $activityRevenue,
            'activity_cost' => $activityCost,
            'gross_profit' => $gross,
            'admin_general' => $adminGeneral,
            'other_income' => $otherIncome,
            'net_before_tax' => $netBeforeTax,
            'income_tax' => $incomeTax,
            'deferred_tax' => $deferredTax,
            'net_after_tax' => $netAfterTax,
            'distributions' => $distributions,
            'retained' => $retained,
        ], $deductions);
    }

    /** صافي حركة مجموعة حسابات (مرحّلة) خلال فترة، باتجاه الطبيعة. */
    private function periodAmount(\Closure $scope, string $natural, string $from, string $to): string
    {
        $ids = $scope(Account::query()->where('is_group', false))->pluck('id')->all();
        if (empty($ids)) {
            return '0';
        }

        $base = fn () => JournalEntryLine::query()
            ->whereIn('account_id', $ids)
            ->whereHas('entry', fn ($q) => $q->where('status', 'posted')->whereBetween('entry_date', [$from, $to]));

        $debit = $this->num($base()->sum('debit'));
        $credit = $this->num($base()->sum('credit'));

        return $natural === 'credit' ? bcsub($credit, $debit, 2) : bcsub($debit, $credit, 2);
    }

    /**
     * المركز المالي (محاسبي من دفتر الأستاذ) كما في تاريخ اختياري — الأصول مقابل الخصوم + حقوق الملكية + صافي ربح الفترة.
     * يتحقّق من معادلة الميزانية.
     */
    public function balanceSheet(Request $request)
    {
        $to = $request->date('to');
        $toStr = $to?->toDateString();

        $assets = $this->balanceSection('asset', 'debit', $to);
        $liabilities = $this->balanceSection('liability', 'credit', $to);
        $equity = $this->balanceSection('equity', 'credit', $to);

        // صافي الربح حتى التاريخ = إيرادات − مصروفات (يُضاف إلى حقوق الملكية).
        $totalRevenue = $this->typeBalance('revenue', 'credit', null, $to);
        $totalExpense = $this->typeBalance('expense', 'debit', null, $to);
        $netProfit = bcsub($totalRevenue, $totalExpense, 2);

        $totalAssets = $assets['total'];
        $totalEquityWithProfit = bcadd($equity['total'], $netProfit, 2);
        $totalLiabilitiesEquity = bcadd($liabilities['total'], $totalEquityWithProfit, 2);

        $difference = bcsub($totalAssets, $totalLiabilitiesEquity, 2);
        $balanced = bccomp($difference, '0', 2) === 0;

        if ($request->query('export') === 'xlsx') {
            $headers = ['البند', 'القيمة'];
            $data = [['الأصول', '']];
            foreach ($assets['rows'] as $r) {
                $data[] = [$r['name'], number_format((float) $r['value'], 2)];
            }
            $data[] = ['إجمالي الأصول', number_format((float) $totalAssets, 2)];
            $data[] = ['الخصوم', ''];
            foreach ($liabilities['rows'] as $r) {
                $data[] = [$r['name'], number_format((float) $r['value'], 2)];
            }
            $data[] = ['إجمالي الخصوم', number_format((float) $liabilities['total'], 2)];
            $data[] = ['حقوق الملكية', ''];
            foreach ($equity['rows'] as $r) {
                $data[] = [$r['name'], number_format((float) $r['value'], 2)];
            }
            $data[] = ['صافي ربح الفترة', number_format((float) $netProfit, 2)];
            $data[] = ['إجمالي حقوق الملكية', number_format((float) $totalEquityWithProfit, 2)];
            $data[] = ['إجمالي الخصوم وحقوق الملكية', number_format((float) $totalLiabilitiesEquity, 2)];
            $title = 'المركز المالي (محاسبي)'.($toStr ? ' حتى '.$toStr : '');

            return app(ExportService::class)->excel($headers, $data, 'gl_balance_sheet', $title);
        }

        return view('reports.gl_balance_sheet', [
            'to' => $toStr,
            'assets' => $assets['rows'],
            'liabilities' => $liabilities['rows'],
            'equity' => $equity['rows'],
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $liabilities['total'],
            'totalEquity' => $equity['total'],
            'netProfit' => $netProfit,
            'totalEquityWithProfit' => $totalEquityWithProfit,
            'totalLiabilitiesEquity' => $totalLiabilitiesEquity,
            'difference' => $difference,
            'balanced' => $balanced,
        ]);
    }

    // ===== Helpers =====

    /** الجهة الطبيعية للنوع المحاسبي. */
    private function naturalSide(string $type): string
    {
        return in_array($type, ['asset', 'expense'], true) ? 'debit' : 'credit';
    }

    /** يُطبّع أي قيمة إلى سلسلة عشرية بدقّة 2. */
    private function num($value): string
    {
        return bcadd((string) ($value ?? '0'), '0', 2);
    }

    /**
     * يجمع حسابات نوع معيّن (إيراد/مصروف) حسب الحساب التجميعي الأب للفترة.
     *
     * @return array{groups: array<int, array{name: string, total: string}>, total: string}
     */
    private function groupSection(string $type, $from, $to): array
    {
        $accounts = Account::query()
            ->where('type', $type)
            ->where('is_group', false)
            ->orderBy('code')
            ->get();

        $natural = $this->naturalSide($type);
        $groups = [];
        $total = '0';

        foreach ($accounts as $account) {
            $debit = $this->num($account->postedDebit($from, $to));
            $credit = $this->num($account->postedCredit($from, $to));
            $amount = $natural === 'credit'
                ? bcsub($credit, $debit, 2)
                : bcsub($debit, $credit, 2);

            if (bccomp($amount, '0', 2) === 0) {
                continue;
            }

            $groupName = $this->parentGroupName($account);
            if (! isset($groups[$groupName])) {
                $groups[$groupName] = '0';
            }
            $groups[$groupName] = bcadd($groups[$groupName], $amount, 2);
            $total = bcadd($total, $amount, 2);
        }

        $result = [];
        foreach ($groups as $name => $value) {
            $result[] = ['name' => $name, 'total' => $value];
        }

        return ['groups' => $result, 'total' => $total];
    }

    /**
     * أرصدة حسابات قسم في الميزانية كما في تاريخ.
     *
     * @return array{rows: array<int, array{name: string, value: string}>, total: string}
     */
    private function balanceSection(string $type, string $natural, $to): array
    {
        $accounts = Account::query()
            ->where('type', $type)
            ->where('is_group', false)
            ->orderBy('code')
            ->get();

        $rows = [];
        $total = '0';

        foreach ($accounts as $account) {
            $balance = $this->num($account->balance($to));
            // عرض الرصيد في جهته الطبيعية كقيمة موجبة.
            $value = $natural === $this->naturalSide($account->type)
                ? $balance
                : bcmul($balance, '-1', 2);

            if (bccomp($value, '0', 2) === 0) {
                continue;
            }

            $rows[] = ['name' => $account->name, 'value' => $value, 'code' => $account->code];
            $total = bcadd($total, $value, 2);
        }

        return ['rows' => $rows, 'total' => $total];
    }

    /** إجمالي رصيد نوع معيّن في جهته الطبيعية كقيمة موجبة. */
    private function typeBalance(string $type, string $natural, $from, $to): string
    {
        $accounts = Account::query()
            ->where('type', $type)
            ->where('is_group', false)
            ->get();

        $total = '0';
        foreach ($accounts as $account) {
            $debit = $this->num($account->postedDebit($from, $to));
            $credit = $this->num($account->postedCredit($from, $to));
            $amount = $natural === 'credit'
                ? bcsub($credit, $debit, 2)
                : bcsub($debit, $credit, 2);
            $total = bcadd($total, $amount, 2);
        }

        return $total;
    }

    /** اسم الحساب التجميعي الأب (وإلا اسم الحساب نفسه). */
    private function parentGroupName(Account $account): string
    {
        if ($account->parent_id) {
            $parent = Account::find($account->parent_id);
            if ($parent) {
                return $parent->code.' - '.$parent->name;
            }
        }

        return $account->name;
    }
}
