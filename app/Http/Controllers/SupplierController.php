<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Supplier;
use App\Services\ExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:suppliers.view', only: ['index', 'show', 'statement']),
            new Middleware('can:suppliers.create', only: ['create', 'store']),
            new Middleware('can:suppliers.edit', only: ['edit', 'update']),
            new Middleware('can:suppliers.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));

        $suppliers = Supplier::query()
            ->when($search !== '', fn ($q) => $q->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
            ))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers', 'search'));
    }

    public function show(Supplier $supplier): View
    {
        $supplier->load([
            'creator',
            'purchaseOrders' => fn ($q) => $q->latest(),
            'payments' => fn ($q) => $q->latest(),
        ]);

        return view('suppliers.show', compact('supplier'));
    }

    public function create(): View
    {
        return view('suppliers.form', ['supplier' => new Supplier()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        Supplier::create($data);

        return redirect()->route('suppliers.index')->with('success', 'تمت إضافة المورد بنجاح.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validateData($request));

        return redirect()->route('suppliers.index')->with('success', 'تم تحديث بيانات المورد.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        return back()->with('success', 'تم حذف المورد.');
    }

    /**
     * كشف حساب المورّد: حركات مرتّبة زمنياً مع رصيد جارٍ محسوب بـ bcmath.
     * مدين (+): توريدات المورّد + أوامر الشراء المستلَمة (ما علينا).
     * دائن (−): مدفوعات المورّد (سداد).
     */
    public function statement(Supplier $supplier, Request $request): View|\Illuminate\Http\Response|StreamedResponse
    {
        $rows = collect();

        foreach ($supplier->transactions()->get() as $txn) {
            $rows->push([
                'date' => $txn->transaction_date,
                'id' => $txn->id,
                'label' => 'توريد: '.($txn->item_description ?: '—'),
                'debit' => (string) $txn->net_amount,
                'credit' => '0',
            ]);
        }

        foreach ($supplier->purchaseOrders()->whereIn('status', ['partial', 'received'])->get() as $po) {
            $rows->push([
                'date' => $po->order_date,
                'id' => $po->id,
                'label' => 'أمر شراء '.$po->order_number,
                'debit' => (string) $po->net_amount,
                'credit' => '0',
            ]);
        }

        foreach ($supplier->payments()->get() as $payment) {
            $rows->push([
                'date' => $payment->payment_date,
                'id' => $payment->id,
                'label' => 'دفعة',
                'debit' => '0',
                'credit' => (string) $payment->amount,
            ]);
        }

        $rows = $rows
            ->sortBy([
                fn ($a, $b) => optional($a['date'])->timestamp <=> optional($b['date'])->timestamp,
                fn ($a, $b) => $a['id'] <=> $b['id'],
            ])
            ->values();

        $opening = (string) $supplier->opening_balance;
        $running = $opening;
        $totalDebit = '0';
        $totalCredit = '0';
        $rows = $rows->map(function ($row) use (&$running, &$totalDebit, &$totalCredit) {
            $running = bcsub(bcadd($running, $row['debit'], 2), $row['credit'], 2);
            $totalDebit = bcadd($totalDebit, $row['debit'], 2);
            $totalCredit = bcadd($totalCredit, $row['credit'], 2);
            $row['running'] = $running;

            return $row;
        });

        $balance = $supplier->balanceDue();
        $format = (string) $request->input('format');

        if ($format === 'pdf' || $format === 'xlsx') {
            $company = Setting::get('company_name', 'القروانة');
            $entity = $supplier->name.($supplier->company_name ? ' — '.$supplier->company_name : '');

            if ($format === 'xlsx') {
                $headers = ['التاريخ', 'البيان', 'مدين (+)', 'دائن (−)', 'الرصيد الجاري'];
                $excelRows = [['', 'رصيد افتتاحي', '', '', number_format((float) $opening, 2)]];
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
                    'statement-supplier-'.$supplier->id,
                    $company.' — كشف حساب مورّد: '.$entity
                );
            }

            $bodyRows = '<tr style="background:#f4f1ec"><td colspan="4" style="font-weight:600">رصيد افتتاحي</td><td style="text-align:left;font-weight:600">'.number_format((float) $opening, 2).'</td></tr>';
            foreach ($rows as $row) {
                $bodyRows .= '<tr>'
                    .'<td>'.(optional($row['date'])->format('Y-m-d') ?: '—').'</td>'
                    .'<td>'.e($row['label']).'</td>'
                    .'<td style="text-align:left;color:#b02a37">'.(bccomp($row['debit'], '0', 2) > 0 ? number_format((float) $row['debit'], 2) : '').'</td>'
                    .'<td style="text-align:left;color:#1a7d3c">'.(bccomp($row['credit'], '0', 2) > 0 ? number_format((float) $row['credit'], 2) : '').'</td>'
                    .'<td style="text-align:left;font-weight:600">'.number_format((float) $row['running'], 2).'</td>'
                    .'</tr>';
            }

            $html = $this->statementHtml(
                $company,
                'كشف حساب مورّد',
                $entity,
                ['التاريخ', 'البيان', 'مدين (+)', 'دائن (−)', 'الرصيد الجاري'],
                $bodyRows,
                '<tr style="background:#f4f1ec;font-weight:700"><td colspan="2">الإجمالي</td>'
                    .'<td style="text-align:left;color:#b02a37">'.number_format((float) $totalDebit, 2).'</td>'
                    .'<td style="text-align:left;color:#1a7d3c">'.number_format((float) $totalCredit, 2).'</td>'
                    .'<td style="text-align:left">'.number_format((float) $balance, 2).'</td></tr>'
            );

            return app(ExportService::class)->pdf($html, 'statement-supplier-'.$supplier->id.'.pdf');
        }

        return view('suppliers.statement', [
            'supplier' => $supplier,
            'rows' => $rows,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
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

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:external,internal'],
            'phone' => ['required', 'string', 'max:30'],
            'phone2' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'commercial_register' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'opening_balance' => ['nullable', 'numeric'],
            'credit_limit' => ['nullable', 'numeric'],
            'payment_terms' => ['nullable', 'numeric'],
        ]);
    }
}
