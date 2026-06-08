<?php

namespace App\Http\Controllers;

use App\Models\Contractor;
use App\Models\Setting;
use App\Services\ExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractorController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:contractors.view', only: ['index', 'show', 'statement', 'report']),
            new Middleware('can:contractors.create', only: ['create', 'store']),
            new Middleware('can:contractors.edit', only: ['edit', 'update']),
            new Middleware('can:contractors.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));

        $contractors = Contractor::query()
            ->when($search !== '', fn ($q) => $q->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('contractor_code', 'like', "%{$search}%")
            ))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('contractors.index', compact('contractors', 'search'));
    }

    public function show(Contractor $contractor): View
    {
        $contractor->load([
            'creator',
            'extracts' => fn ($q) => $q->with('project')->latest(),
            'payments' => fn ($q) => $q->latest(),
        ]);

        return view('contractors.show', compact('contractor'));
    }

    public function create(): View
    {
        return view('contractors.form', ['contractor' => new Contractor()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        Contractor::create($data);

        return redirect()->route('contractors.index')->with('success', 'تمت إضافة المقاول بنجاح.');
    }

    public function edit(Contractor $contractor): View
    {
        return view('contractors.form', compact('contractor'));
    }

    public function update(Request $request, Contractor $contractor): RedirectResponse
    {
        $contractor->update($this->validateData($request, $contractor));

        return redirect()->route('contractors.index')->with('success', 'تم تحديث بيانات المقاول.');
    }

    public function destroy(Contractor $contractor): RedirectResponse
    {
        $contractor->delete();

        return back()->with('success', 'تم حذف المقاول.');
    }

    /**
     * كشف حساب المقاول: حركات مرتّبة زمنياً مع رصيد جارٍ محسوب بـ bcmath.
     * دائن (+): المستخلصات المعتمدة/الجزئية/المدفوعة (ما علينا للمقاول).
     * مدين (−): دفعات المقاول (سداد).
     */
    public function statement(Contractor $contractor, Request $request): View|\Illuminate\Http\Response|StreamedResponse
    {
        $rows = collect();

        $extracts = $contractor->extracts()
            ->whereIn('status', ['approved', 'partial', 'paid'])
            ->get();
        foreach ($extracts as $extract) {
            $rows->push([
                'date' => $extract->extract_date,
                'id' => $extract->id,
                'label' => 'مستخلص '.$extract->extract_number,
                'credit' => (string) $extract->net_amount,
                'debit' => '0',
            ]);
        }

        foreach ($contractor->payments()->get() as $payment) {
            $rows->push([
                'date' => $payment->payment_date,
                'id' => $payment->id,
                'label' => 'دفعة',
                'credit' => '0',
                'debit' => (string) $payment->amount,
            ]);
        }

        $rows = $rows
            ->sortBy([
                fn ($a, $b) => optional($a['date'])->timestamp <=> optional($b['date'])->timestamp,
                fn ($a, $b) => $a['id'] <=> $b['id'],
            ])
            ->values();

        $opening = (string) $contractor->opening_balance;
        $running = $opening;
        $totalCredit = '0';
        $totalDebit = '0';
        $rows = $rows->map(function ($row) use (&$running, &$totalCredit, &$totalDebit) {
            $running = bcsub(bcadd($running, $row['credit'], 2), $row['debit'], 2);
            $totalCredit = bcadd($totalCredit, $row['credit'], 2);
            $totalDebit = bcadd($totalDebit, $row['debit'], 2);
            $row['running'] = $running;

            return $row;
        });

        $balance = $contractor->balanceDue();
        $format = (string) $request->input('format');

        if ($format === 'pdf' || $format === 'xlsx') {
            $company = Setting::get('company_name', 'القروانة');
            $entity = $contractor->name.($contractor->company_name ? ' — '.$contractor->company_name : '');

            if ($format === 'xlsx') {
                $headers = ['التاريخ', 'البيان', 'دائن (+)', 'مدين (−)', 'الرصيد الجاري'];
                $excelRows = [['', 'رصيد افتتاحي', '', '', number_format((float) $opening, 2)]];
                foreach ($rows as $row) {
                    $excelRows[] = [
                        optional($row['date'])->format('Y-m-d') ?: '—',
                        $row['label'],
                        bccomp($row['credit'], '0', 2) > 0 ? number_format((float) $row['credit'], 2) : '',
                        bccomp($row['debit'], '0', 2) > 0 ? number_format((float) $row['debit'], 2) : '',
                        number_format((float) $row['running'], 2),
                    ];
                }
                $excelRows[] = ['', 'الإجمالي', number_format((float) $totalCredit, 2), number_format((float) $totalDebit, 2), number_format((float) $balance, 2)];

                return app(ExportService::class)->excel(
                    $headers,
                    $excelRows,
                    'statement-contractor-'.$contractor->id,
                    $company.' — كشف حساب مقاول: '.$entity
                );
            }

            $bodyRows = '<tr style="background:#f4f1ec"><td colspan="4" style="font-weight:600">رصيد افتتاحي</td><td style="text-align:left;font-weight:600">'.number_format((float) $opening, 2).'</td></tr>';
            foreach ($rows as $row) {
                $bodyRows .= '<tr>'
                    .'<td>'.(optional($row['date'])->format('Y-m-d') ?: '—').'</td>'
                    .'<td>'.e($row['label']).'</td>'
                    .'<td style="text-align:left;color:#1a7d3c">'.(bccomp($row['credit'], '0', 2) > 0 ? number_format((float) $row['credit'], 2) : '').'</td>'
                    .'<td style="text-align:left;color:#b02a37">'.(bccomp($row['debit'], '0', 2) > 0 ? number_format((float) $row['debit'], 2) : '').'</td>'
                    .'<td style="text-align:left;font-weight:600">'.number_format((float) $row['running'], 2).'</td>'
                    .'</tr>';
            }

            $html = $this->statementHtml(
                $company,
                'كشف حساب مقاول',
                $entity,
                ['التاريخ', 'البيان', 'دائن (+)', 'مدين (−)', 'الرصيد الجاري'],
                $bodyRows,
                '<tr style="background:#f4f1ec;font-weight:700"><td colspan="2">الإجمالي</td>'
                    .'<td style="text-align:left;color:#1a7d3c">'.number_format((float) $totalCredit, 2).'</td>'
                    .'<td style="text-align:left;color:#b02a37">'.number_format((float) $totalDebit, 2).'</td>'
                    .'<td style="text-align:left">'.number_format((float) $balance, 2).'</td></tr>'
            );

            return app(ExportService::class)->pdf($html, 'statement-contractor-'.$contractor->id.'.pdf');
        }

        return view('contractors.statement', [
            'contractor' => $contractor,
            'rows' => $rows,
            'totalCredit' => $totalCredit,
            'totalDebit' => $totalDebit,
            'balance' => $balance,
            'opening' => $opening,
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

    /**
     * تقرير المقاولين: لكل مقاول صافي المستحقّ، المدفوع، الرصيد + الإجماليات.
     * يدعم تصدير CSV (UTF-8 BOM) عند export=csv.
     */
    public function report(Request $request): View|StreamedResponse
    {
        $contractors = Contractor::query()
            ->orderBy('name')
            ->get()
            ->map(function (Contractor $contractor) {
                $totalEarned = (string) $contractor->extracts()
                    ->whereIn('status', ['approved', 'partial', 'paid'])
                    ->sum('net_amount');
                $totalPaid = (string) $contractor->payments()->sum('amount');

                return [
                    'contractor' => $contractor,
                    'totalEarned' => $totalEarned,
                    'totalPaid' => $totalPaid,
                    'balance' => bcsub($totalEarned, $totalPaid, 2),
                ];
            });

        $grandEarned = $contractors->reduce(fn ($carry, $row) => bcadd($carry, $row['totalEarned'], 2), '0');
        $grandPaid = $contractors->reduce(fn ($carry, $row) => bcadd($carry, $row['totalPaid'], 2), '0');
        $grandBalance = bcsub($grandEarned, $grandPaid, 2);

        if ((string) $request->input('export') === 'csv') {
            return $this->exportReportCsv($contractors);
        }

        return view('contractors.report', [
            'contractors' => $contractors,
            'grandEarned' => $grandEarned,
            'grandPaid' => $grandPaid,
            'grandBalance' => $grandBalance,
        ]);
    }

    private function exportReportCsv($contractors): StreamedResponse
    {
        $filename = 'contractors_report_'.date('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($contractors) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM لضمان العرض الصحيح للعربية في Excel
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['name', 'totalEarned', 'totalPaid', 'balance']);
            foreach ($contractors as $row) {
                fputcsv($out, [
                    $row['contractor']->name,
                    $row['totalEarned'],
                    $row['totalPaid'],
                    $row['balance'],
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function validateData(Request $request, ?Contractor $contractor = null): array
    {
        return $request->validate([
            'contractor_code' => [
                'required', 'string', 'max:50',
                Rule::unique('contractors', 'contractor_code')->ignore($contractor),
            ],
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'phone2' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'opening_balance' => ['nullable', 'numeric'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
