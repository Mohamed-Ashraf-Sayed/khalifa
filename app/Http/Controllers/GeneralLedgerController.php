<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Client;
use App\Models\Contractor;
use App\Models\ContractorExtract;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Partner;
use App\Models\Project;
use App\Models\ProjectCost;
use App\Models\Revenue;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GeneralLedgerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:reports.view')];
    }

    /**
     * مركز دفتر الأستاذ — يجمع كل كشوف الحسابات في مكان واحد:
     * لكل فئة (بنوك/موردون/مقاولون/شركاء/موظفون/عملاء/مشاريع) قائمة اختيار تفتح الكشف المناسب.
     */
    public function index(): View
    {
        return view('general_ledger.index', [
            'bankAccounts' => BankAccount::orderBy('name')->get(['id', 'name', 'bank_name']),
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name', 'company_name']),
            'contractors' => Contractor::orderBy('name')->get(['id', 'name', 'company_name']),
            'partners' => Partner::orderBy('name')->get(['id', 'name']),
            'employees' => Employee::orderBy('name')->get(['id', 'name', 'job_title']),
            'clients' => Client::orderBy('name')->get(['id', 'name', 'company_name']),
            'projects' => Project::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * دفتر أستاذ المشروع: كشف زمني بكل الأحداث المالية للمشروع مع رصيد صافٍ جارٍ (bcmath).
     * دائن (+): الإيرادات. مدين (−): المصروفات + مستخلصات المقاولين + توريدات المورّدين + بنود التكلفة.
     */
    public function project(Request $request, Project $project): View|\Illuminate\Http\Response|StreamedResponse
    {
        $rows = collect();

        foreach (Revenue::where('project_id', $project->id)->get() as $rev) {
            $rows->push([
                'date' => $rev->revenue_date,
                'id' => $rev->id,
                'label' => 'إيراد: '.($rev->description ?: '—'),
                'credit' => (string) $rev->amount,
                'debit' => '0',
            ]);
        }

        foreach (Expense::where('project_id', $project->id)->get() as $exp) {
            $rows->push([
                'date' => $exp->expense_date,
                'id' => $exp->id,
                'label' => 'مصروف: '.($exp->description ?: '—'),
                'credit' => '0',
                'debit' => (string) $exp->amount,
            ]);
        }

        foreach (ContractorExtract::where('project_id', $project->id)
            ->whereIn('status', ['approved', 'partial', 'paid'])->get() as $ext) {
            $rows->push([
                'date' => $ext->extract_date,
                'id' => $ext->id,
                'label' => 'مستخلص '.($ext->extract_number ?: '#'.$ext->id),
                'credit' => '0',
                'debit' => (string) $ext->net_amount,
            ]);
        }

        foreach (SupplierTransaction::where('project_id', $project->id)->get() as $txn) {
            $rows->push([
                'date' => $txn->transaction_date,
                'id' => $txn->id,
                'label' => 'توريد: '.($txn->item_description ?: '—'),
                'credit' => '0',
                'debit' => (string) $txn->net_amount,
            ]);
        }

        foreach (ProjectCost::where('project_id', $project->id)->get() as $cost) {
            $rows->push([
                'date' => $cost->cost_date,
                'id' => $cost->id,
                'label' => 'بند تكلفة: '.($cost->work_item ?: ($cost->description ?: '—')),
                'credit' => '0',
                'debit' => (string) $cost->amount,
            ]);
        }

        $rows = $rows
            ->sortBy([
                fn ($a, $b) => optional($a['date'])->timestamp <=> optional($b['date'])->timestamp,
                fn ($a, $b) => $a['id'] <=> $b['id'],
            ])
            ->values();

        $running = '0';
        $totalCredit = '0';
        $totalDebit = '0';
        $rows = $rows->map(function ($row) use (&$running, &$totalCredit, &$totalDebit) {
            $running = bcsub(bcadd($running, $row['credit'], 2), $row['debit'], 2);
            $totalCredit = bcadd($totalCredit, $row['credit'], 2);
            $totalDebit = bcadd($totalDebit, $row['debit'], 2);
            $row['running'] = $running;

            return $row;
        });

        $net = bcsub($totalCredit, $totalDebit, 2);
        $format = (string) $request->input('format');

        if ($format === 'pdf' || $format === 'xlsx') {
            $company = Setting::get('company_name', 'القروانة');
            $entity = $project->name;

            if ($format === 'xlsx') {
                $headers = ['التاريخ', 'البيان', 'تكلفة (−)', 'إيراد (+)', 'الرصيد الصافي'];
                $excelRows = [];
                foreach ($rows as $row) {
                    $excelRows[] = [
                        optional($row['date'])->format('Y-m-d') ?: '—',
                        $row['label'],
                        bccomp($row['debit'], '0', 2) > 0 ? number_format((float) $row['debit'], 2) : '',
                        bccomp($row['credit'], '0', 2) > 0 ? number_format((float) $row['credit'], 2) : '',
                        number_format((float) $row['running'], 2),
                    ];
                }
                $excelRows[] = ['', 'الإجمالي', number_format((float) $totalDebit, 2), number_format((float) $totalCredit, 2), number_format((float) $net, 2)];

                return app(ExportService::class)->excel(
                    $headers,
                    $excelRows,
                    'ledger-project-'.$project->id,
                    $company.' — دفتر أستاذ المشروع: '.$entity
                );
            }

            $bodyRows = '';
            foreach ($rows as $row) {
                $bodyRows .= '<tr>'
                    .'<td>'.(optional($row['date'])->format('Y-m-d') ?: '—').'</td>'
                    .'<td>'.e($row['label']).'</td>'
                    .'<td style="text-align:left;color:#b02a37">'.(bccomp($row['debit'], '0', 2) > 0 ? number_format((float) $row['debit'], 2) : '').'</td>'
                    .'<td style="text-align:left;color:#1a7d3c">'.(bccomp($row['credit'], '0', 2) > 0 ? number_format((float) $row['credit'], 2) : '').'</td>'
                    .'<td style="text-align:left;font-weight:600">'.number_format((float) $row['running'], 2).'</td>'
                    .'</tr>';
            }
            if ($rows->isEmpty()) {
                $bodyRows = '<tr><td colspan="5" style="text-align:center;color:#666">لا توجد حركات.</td></tr>';
            }

            $html = $this->statementHtml(
                $company,
                'دفتر أستاذ المشروع',
                $entity,
                ['التاريخ', 'البيان', 'تكلفة (−)', 'إيراد (+)', 'الرصيد الصافي'],
                $bodyRows,
                '<tr style="background:#f4f1ec;font-weight:700"><td colspan="2">الإجمالي</td>'
                    .'<td style="text-align:left;color:#b02a37">'.number_format((float) $totalDebit, 2).'</td>'
                    .'<td style="text-align:left;color:#1a7d3c">'.number_format((float) $totalCredit, 2).'</td>'
                    .'<td style="text-align:left">'.number_format((float) $net, 2).'</td></tr>'
            );

            return app(ExportService::class)->pdf($html, 'ledger-project-'.$project->id.'.pdf');
        }

        return view('general_ledger.project', [
            'project' => $project,
            'rows' => $rows,
            'totalCredit' => $totalCredit,
            'totalDebit' => $totalDebit,
            'net' => $net,
        ]);
    }

    /** يبني HTML عربي مكتفٍ ذاتياً (RTL) لكشف حساب لتصديره PDF. */
    private function statementHtml(string $company, string $title, string $entity, array $headers, string $bodyRows, string $footRow): string
    {
        $ths = '';
        foreach ($headers as $h) {
            $ths .= '<th style="border:1px solid #ccc;padding:6px;background:#8b7355;color:#fff;text-align:right">'.e($h).'</th>';
        }

        return '<html><head><meta charset="utf-8"><style>'
            .'body{font-family:dejavusans;direction:rtl;font-size:12px;color:#222}'
            .'h2,h4{margin:0;text-align:center}'
            .'.head{text-align:center;margin-bottom:14px}'
            .'.muted{color:#666;font-size:11px}'
            .'table{width:100%;border-collapse:collapse;margin-top:10px}'
            .'td{border:1px solid #ccc;padding:6px}'
            .'</style></head><body>'
            .'<div class="head"><h2>'.e($company).'</h2><h4>'.e($title).'</h4>'
            .'<div class="muted">'.e($entity).'</div>'
            .'<div class="muted">تاريخ الإصدار: '.now()->format('Y-m-d').'</div></div>'
            .'<table><thead><tr>'.$ths.'</tr></thead>'
            .'<tbody>'.$bodyRows.'</tbody>'
            .'<tfoot>'.$footRow.'</tfoot></table></body></html>';
    }
}
