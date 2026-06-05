<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Quotation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QuotationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:quotations.view', only: ['index', 'show']),
            new Middleware('can:quotations.create', only: ['create', 'store']),
            new Middleware('can:quotations.edit', only: ['edit', 'update', 'convertToInvoice']),
            new Middleware('can:quotations.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $status = (string) $request->input('status', '');
        $search = trim((string) $request->input('search', ''));

        $quotations = Quotation::query()
            ->with(['client', 'project'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where('quotation_number', 'like', "%{$search}%"))
            ->latest('issue_date')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'count' => Quotation::count(),
            'value' => (float) Quotation::sum('total_amount'),
            'accepted' => Quotation::where('status', 'accepted')->count(),
        ];

        return view('quotations.index', compact('quotations', 'status', 'search', 'stats'));
    }

    public function create(): View
    {
        return view('quotations.form', $this->formData(new Quotation([
            'quotation_number' => $this->nextNumber(),
            'status' => 'draft',
            'issue_date' => now()->toDateString(),
            'tax_rate' => 0,
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        $quotation = Quotation::create($data);
        $quotation->recomputeTotals();

        return redirect()->route('quotations.show', $quotation)->with('success', 'تم إنشاء عرض السعر. أضف البنود الآن.');
    }

    public function show(Quotation $quotation): View
    {
        $quotation->load('items', 'client', 'project', 'creator');

        return view('quotations.show', compact('quotation'));
    }

    public function edit(Quotation $quotation): View
    {
        return view('quotations.form', $this->formData($quotation));
    }

    public function update(Request $request, Quotation $quotation): RedirectResponse
    {
        $quotation->update($this->validateData($request, $quotation));
        $quotation->recomputeTotals(); // tax_rate قد يكون تغيّر

        return redirect()->route('quotations.show', $quotation)->with('success', 'تم تحديث عرض السعر.');
    }

    public function destroy(Quotation $quotation): RedirectResponse
    {
        $quotation->delete(); // البنود تُحذف cascade

        return redirect()->route('quotations.index')->with('success', 'تم حذف عرض السعر.');
    }

    /** تحويل عرض سعر مقبول إلى فاتورة مع نقل البنود. */
    public function convertToInvoice(Quotation $quotation): RedirectResponse
    {
        if ($quotation->status !== 'accepted' || $quotation->converted_invoice_id) {
            return back()->with('error', 'لا يمكن التحويل إلا لعرض سعر مقبول وغير مُحوّل من قبل.');
        }

        $invoice = DB::transaction(function () use ($quotation) {
            $invoice = Invoice::create([
                'invoice_number' => $this->nextInvoiceNumber(),
                'client_id' => $quotation->client_id,
                'project_id' => $quotation->project_id,
                'invoice_type' => 'progress',
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'tax_rate' => $quotation->tax_rate,
                'status' => 'draft',
                'created_by' => $quotation->created_by,
            ]);

            foreach ($quotation->items as $item) {
                $invoice->items()->create([
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                ]);
            }

            $invoice->recomputeTotals();

            $quotation->converted_invoice_id = $invoice->id;
            $quotation->save();

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'تم تحويل عرض السعر إلى فاتورة.');
    }

    private function nextNumber(): string
    {
        $year = now()->year;
        $count = Quotation::withTrashed()->whereYear('created_at', $year)->count() + 1;

        return sprintf('QUO-%d-%04d', $year, $count);
    }

    private function nextInvoiceNumber(): string
    {
        $year = now()->year;
        $count = Invoice::withTrashed()->whereYear('created_at', $year)->count() + 1;

        return sprintf('INV-%d-%04d', $year, $count);
    }

    private function formData(Quotation $quotation): array
    {
        return [
            'quotation' => $quotation,
            'clients' => Client::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
        ];
    }

    private function validateData(Request $request, ?Quotation $quotation = null): array
    {
        return $request->validate([
            'quotation_number' => ['required', 'string', 'max:50', 'unique:quotations,quotation_number,'.($quotation?->id ?? 'NULL')],
            'client_id' => ['required', 'exists:clients,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'issue_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', 'in:'.implode(',', array_keys(Quotation::STATUSES))],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
