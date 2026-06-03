<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\PartnerTransaction;
use App\Models\Project;
use App\Models\Setting;
use App\Services\ExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PartnerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:partners.view', only: ['index', 'show', 'statement']),
            new Middleware('can:partners.create', only: ['create', 'store']),
            new Middleware('can:partners.edit', only: ['edit', 'update']),
            new Middleware('can:partners.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));

        $partners = Partner::query()
            ->when($search !== '', fn ($q) => $q->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
            ))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('partners.index', compact('partners', 'search'));
    }

    public function show(Partner $partner): View
    {
        $partner->load(['creator', 'transactions' => fn ($q) => $q->latest('transaction_date')]);

        return view('partners.show', compact('partner'));
    }

    public function create(): View
    {
        return view('partners.form', [
            'partner' => new Partner(),
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        Partner::create($data);

        return redirect()->route('partners.index')->with('success', 'تمت إضافة الشريك بنجاح.');
    }

    public function edit(Partner $partner): View
    {
        return view('partners.form', [
            'partner' => $partner,
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Partner $partner): RedirectResponse
    {
        $partner->update($this->validateData($request));

        return redirect()->route('partners.index')->with('success', 'تم تحديث بيانات الشريك.');
    }

    public function destroy(Partner $partner): RedirectResponse
    {
        $partner->delete();

        return back()->with('success', 'تم حذف الشريك.');
    }

    /** كشف حساب الشريك: حركات مرتّبة زمنياً مع رصيد جارٍ محسوب بـ bcmath. */
    public function statement(Partner $partner, Request $request): View|\Illuminate\Http\Response|StreamedResponse
    {
        $transactions = $partner->transactions()
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $opening = (string) $partner->opening_balance;
        $running = $opening;
        $rows = $transactions->map(function ($txn) use (&$running) {
            $running = $txn->type === 'deposit'
                ? bcadd($running, (string) $txn->amount, 2)
                : bcsub($running, (string) $txn->amount, 2);

            return ['txn' => $txn, 'running' => $running];
        });

        $format = (string) $request->input('format');

        if ($format === 'pdf' || $format === 'xlsx') {
            $company = Setting::get('company_name', 'القروانة');
            $entity = $partner->name;

            if ($format === 'xlsx') {
                $headers = ['التاريخ', 'النوع', 'البيان', 'مدين (−)', 'دائن (+)', 'الرصيد الجاري'];
                $excelRows = [['', '', 'رصيد افتتاحي', '', '', number_format((float) $opening, 2)]];
                foreach ($rows as $row) {
                    $excelRows[] = [
                        optional($row['txn']->transaction_date)->format('Y-m-d') ?: '—',
                        PartnerTransaction::TYPES[$row['txn']->type] ?? $row['txn']->type,
                        $row['txn']->description ?: '—',
                        $row['txn']->type === 'deposit' ? '' : number_format((float) $row['txn']->amount, 2),
                        $row['txn']->type === 'deposit' ? number_format((float) $row['txn']->amount, 2) : '',
                        number_format((float) $row['running'], 2),
                    ];
                }
                $excelRows[] = ['', '', 'الرصيد الحالي', '', '', number_format((float) $partner->currentBalance(), 2)];

                return app(ExportService::class)->excel(
                    $headers,
                    $excelRows,
                    'statement-partner-'.$partner->id,
                    $company.' — كشف حساب شريك: '.$entity
                );
            }

            $bodyRows = '<tr style="background:#f4f1ec"><td colspan="5" style="font-weight:600">رصيد افتتاحي</td><td style="text-align:left;font-weight:600">'.number_format((float) $opening, 2).'</td></tr>';
            foreach ($rows as $row) {
                $isDeposit = $row['txn']->type === 'deposit';
                $bodyRows .= '<tr>'
                    .'<td>'.(optional($row['txn']->transaction_date)->format('Y-m-d') ?: '—').'</td>'
                    .'<td>'.e(PartnerTransaction::TYPES[$row['txn']->type] ?? $row['txn']->type).'</td>'
                    .'<td>'.e($row['txn']->description ?: '—').'</td>'
                    .'<td style="text-align:left;color:#b02a37">'.($isDeposit ? '' : number_format((float) $row['txn']->amount, 2)).'</td>'
                    .'<td style="text-align:left;color:#1a7d3c">'.($isDeposit ? number_format((float) $row['txn']->amount, 2) : '').'</td>'
                    .'<td style="text-align:left;font-weight:600">'.number_format((float) $row['running'], 2).'</td>'
                    .'</tr>';
            }

            $html = $this->statementHtml(
                $company,
                'كشف حساب شريك',
                $entity,
                ['التاريخ', 'النوع', 'البيان', 'مدين (−)', 'دائن (+)', 'الرصيد الجاري'],
                $bodyRows,
                '<tr style="background:#f4f1ec;font-weight:700"><td colspan="5">الرصيد الحالي</td>'
                    .'<td style="text-align:left">'.number_format((float) $partner->currentBalance(), 2).'</td></tr>'
            );

            return app(ExportService::class)->pdf($html, 'statement-partner-'.$partner->id.'.pdf');
        }

        $deposits = $partner->deposits()->latest('deposit_date')->get();

        return view('partners.statement', compact('partner', 'rows', 'deposits', 'opening'));
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

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'national_id' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'join_date' => ['required', 'date'],
            'status' => ['required', Rule::in(array_keys(Partner::STATUSES))],
            'project_id' => ['nullable', 'exists:projects,id'],
            'notes' => ['nullable', 'string'],
            'opening_balance' => ['nullable', 'numeric'],
        ]);
    }
}
