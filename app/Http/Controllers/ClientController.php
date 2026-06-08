<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\ExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:clients.view', only: ['index', 'show', 'statement']),
            new Middleware('can:clients.create', only: ['create', 'store']),
            new Middleware('can:clients.edit', only: ['edit', 'update']),
            new Middleware('can:clients.delete', only: ['destroy', 'bulkDestroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));

        $clients = Client::query()
            ->when($search !== '', fn ($q) => $q->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
            ))
            ->withCount('projects')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // بطاقات إحصائية (للعرض فقط).
        $invoiced = (string) Invoice::where('status', '!=', 'cancelled')->sum('total_amount');
        $paid = (string) Invoice::where('status', '!=', 'cancelled')->sum('paid_amount');

        $stats = [
            'count' => Client::count(),
            'with_projects' => Client::has('projects')->count(),
            'balance_due' => bcsub($invoiced, $paid, 2),
        ];

        return view('clients.index', compact('clients', 'search', 'stats'));
    }

    public function show(Client $client): View
    {
        $client->load(['projects' => fn ($q) => $q->latest()]);

        // قيم مالية محسوبة للعرض فقط (مطابقة لمنطق كشف الحساب).
        $totalInvoiced = $client->totalInvoiced();
        $totalPaid = $client->totalPaid();
        $balanceDue = bcsub($totalInvoiced, $totalPaid, 2);

        return view('clients.show', compact('client', 'totalInvoiced', 'totalPaid', 'balanceDue'));
    }

    public function create(): View
    {
        return view('clients.form', ['client' => new Client()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        Client::create($data);

        return redirect()->route('clients.index')->with('success', 'تمت إضافة العميل بنجاح.');
    }

    public function edit(Client $client): View
    {
        return view('clients.form', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $client->update($this->validateData($request));

        return redirect()->route('clients.index')->with('success', 'تم تحديث بيانات العميل.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        if ($client->projects()->exists()) {
            return back()->with('error', 'لا يمكن حذف عميل مرتبط بمشاريع.');
        }

        $client->delete();

        return back()->with('success', 'تم حذف العميل.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:clients,id'],
        ]);

        // تجاهل العملاء المرتبطين بمشاريع (لا يمكن حذفهم).
        $deletable = Client::whereIn('id', $data['ids'])
            ->whereDoesntHave('projects')
            ->get();

        $deletable->each(fn (Client $client) => $client->delete());

        $skipped = count($data['ids']) - $deletable->count();
        $message = 'تم حذف العملاء المحددين.';
        if ($skipped > 0) {
            $message .= " (تم تجاهل {$skipped} مرتبطين بمشاريع)";
        }

        return back()->with('success', $message);
    }

    /**
     * كشف حساب العميل (ذمم مدينة): حركات مرتّبة زمنياً مع رصيد جارٍ محسوب بـ bcmath.
     * مدين (+، على العميل): فواتير العميل غير الملغاة.
     * دائن (−، سداد): دفعات تلك الفواتير.
     */
    public function statement(Request $request, Client $client): View|\Illuminate\Http\Response|StreamedResponse
    {
        $invoices = Invoice::where('client_id', $client->id)
            ->where('status', '!=', 'cancelled')
            ->with('payments')
            ->get();

        $rows = collect();

        foreach ($invoices as $invoice) {
            $rows->push([
                'date' => $invoice->issue_date,
                'id' => $invoice->id,
                'label' => 'فاتورة '.$invoice->invoice_number,
                'debit' => (string) $invoice->total_amount,
                'credit' => '0',
            ]);

            foreach ($invoice->payments as $payment) {
                $rows->push([
                    'date' => $payment->payment_date,
                    'id' => $payment->id,
                    'label' => 'دفعة فاتورة '.$invoice->invoice_number,
                    'debit' => '0',
                    'credit' => (string) $payment->amount,
                ]);
            }
        }

        $rows = $rows
            ->sortBy([
                fn ($a, $b) => optional($a['date'])->timestamp <=> optional($b['date'])->timestamp,
                fn ($a, $b) => $a['id'] <=> $b['id'],
            ])
            ->values();

        $running = '0';
        $totalDebit = '0';
        $totalCredit = '0';
        $rows = $rows->map(function ($row) use (&$running, &$totalDebit, &$totalCredit) {
            $running = bcsub(bcadd($running, $row['debit'], 2), $row['credit'], 2);
            $totalDebit = bcadd($totalDebit, $row['debit'], 2);
            $totalCredit = bcadd($totalCredit, $row['credit'], 2);
            $row['running'] = $running;

            return $row;
        });

        $balance = bcsub($totalDebit, $totalCredit, 2);
        $format = (string) $request->input('format');

        if ($format === 'pdf' || $format === 'xlsx') {
            $company = Setting::get('company_name', 'القروانة');
            $entity = $client->name.($client->company_name ? ' — '.$client->company_name : '');

            if ($format === 'xlsx') {
                $headers = ['التاريخ', 'البيان', 'مدين (+)', 'دائن (−)', 'الرصيد الجاري'];
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
                $excelRows[] = ['', 'الإجمالي', number_format((float) $totalDebit, 2), number_format((float) $totalCredit, 2), number_format((float) $balance, 2)];

                return app(ExportService::class)->excel(
                    $headers,
                    $excelRows,
                    'statement-client-'.$client->id,
                    $company.' — كشف حساب عميل: '.$entity
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
                'كشف حساب عميل',
                $entity,
                ['التاريخ', 'البيان', 'مدين (+)', 'دائن (−)', 'الرصيد الجاري'],
                $bodyRows,
                '<tr style="background:#f4f1ec;font-weight:700"><td colspan="2">الإجمالي</td>'
                    .'<td style="text-align:left;color:#b02a37">'.number_format((float) $totalDebit, 2).'</td>'
                    .'<td style="text-align:left;color:#1a7d3c">'.number_format((float) $totalCredit, 2).'</td>'
                    .'<td style="text-align:left">'.number_format((float) $balance, 2).'</td></tr>'
            );

            return app(ExportService::class)->pdf($html, 'statement-client-'.$client->id.'.pdf');
        }

        return view('clients.statement', [
            'client' => $client,
            'rows' => $rows,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'balance' => $balance,
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

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'phone2' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'commercial_register' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
