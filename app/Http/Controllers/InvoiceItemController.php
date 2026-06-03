<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class InvoiceItemController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:invoices.edit')];
    }

    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $invoice->items()->create([
            'description' => $data['description'],
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'],
            'total_price' => bcmul((string) $data['quantity'], (string) $data['unit_price'], 2),
        ]);
        $invoice->recomputeTotals();

        return back()->with('success', 'تمت إضافة البند.');
    }

    public function destroy(InvoiceItem $invoice_item): RedirectResponse
    {
        $invoice = $invoice_item->invoice;
        $invoice_item->delete();
        $invoice->recomputeTotals();

        return back()->with('success', 'تم حذف البند.');
    }
}
