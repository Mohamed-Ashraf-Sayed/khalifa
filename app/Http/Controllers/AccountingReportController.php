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
        $from = $request->date('from');
        $to = $request->date('to');

        $revenueGroups = $this->groupSection('revenue', $from, $to);
        $expenseGroups = $this->groupSection('expense', $from, $to);

        $totalRevenue = $revenueGroups['total'];
        $totalExpense = $expenseGroups['total'];
        $netProfit = bcsub($totalRevenue, $totalExpense, 2);

        if ($request->query('export') === 'xlsx') {
            $headers = ['البند', 'القيمة'];
            $data = [['الإيرادات', '']];
            foreach ($revenueGroups['groups'] as $g) {
                $data[] = [$g['name'], number_format((float) $g['total'], 2)];
            }
            $data[] = ['إجمالي الإيرادات', number_format((float) $totalRevenue, 2)];
            $data[] = ['المصروفات', ''];
            foreach ($expenseGroups['groups'] as $g) {
                $data[] = [$g['name'], number_format((float) $g['total'], 2)];
            }
            $data[] = ['إجمالي المصروفات', number_format((float) $totalExpense, 2)];
            $data[] = ['صافي الربح / الخسارة', number_format((float) $netProfit, 2)];
            $title = 'قائمة الدخل (محاسبي)';

            return app(ExportService::class)->excel($headers, $data, 'gl_income_statement', $title);
        }

        return view('reports.gl_income_statement', [
            'from' => $from?->toDateString(),
            'to' => $to?->toDateString(),
            'revenueGroups' => $revenueGroups['groups'],
            'expenseGroups' => $expenseGroups['groups'],
            'totalRevenue' => $totalRevenue,
            'totalExpense' => $totalExpense,
            'netProfit' => $netProfit,
        ]);
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
