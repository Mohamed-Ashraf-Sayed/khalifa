<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class InvoiceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:invoices.view', only: ['index', 'show']),
            new Middleware('can:invoices.create', only: ['create', 'store']),
            new Middleware('can:invoices.edit', only: ['edit', 'update']),
            new Middleware('can:invoices.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $status = (string) $request->input('status', '');
        $search = trim((string) $request->input('search', ''));

        $invoices = Invoice::query()
            ->with(['client', 'project'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where('invoice_number', 'like', "%{$search}%"))
            ->latest('issue_date')
            ->paginate(15)
            ->withQueryString();

        return view('invoices.index', compact('invoices', 'status', 'search'));
    }

    public function create(): View
    {
        return view('invoices.form', $this->formData(new Invoice([
            'invoice_type' => 'progress', 'status' => 'draft', 'issue_date' => now()->toDateString(), 'tax_rate' => 0,
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        $invoice = Invoice::create($data);
        $invoice->recomputeTotals();

        return redirect()->route('invoices.show', $invoice)->with('success', 'تم إنشاء الفاتورة. أضف البنود الآن.');
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load('items', 'client', 'project', 'payments.bankAccount');
        $accounts = \App\Models\BankAccount::where('is_active', true)->orderBy('name')->get();

        return view('invoices.show', compact('invoice', 'accounts'));
    }

    public function edit(Invoice $invoice): View
    {
        return view('invoices.form', $this->formData($invoice));
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $invoice->update($this->validateData($request));
        $invoice->recomputeTotals(); // tax_rate قد يكون تغيّر

        return redirect()->route('invoices.show', $invoice)->with('success', 'تم تحديث الفاتورة.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->delete(); // البنود تُحذف cascade

        return redirect()->route('invoices.index')->with('success', 'تم حذف الفاتورة.');
    }

    private function formData(Invoice $invoice): array
    {
        return [
            'invoice' => $invoice,
            'clients' => Client::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
        ];
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'invoice_number' => ['required', 'string', 'max:50', 'unique:invoices,invoice_number,'.($request->route('invoice')?->id ?? 'NULL')],
            'client_id' => ['required', 'exists:clients,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'invoice_type' => ['required', 'in:'.implode(',', array_keys(Invoice::TYPES))],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', 'in:'.implode(',', array_keys(Invoice::STATUSES))],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
