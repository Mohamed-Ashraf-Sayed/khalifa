<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\QuotationItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class QuotationItemController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:quotations.edit')];
    }

    public function store(Request $request, Quotation $quotation): RedirectResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $quotation->items()->create([
            'description' => $data['description'],
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'],
            'total_price' => bcmul((string) $data['quantity'], (string) $data['unit_price'], 2),
        ]);
        $quotation->recomputeTotals();

        return back()->with('success', 'تمت إضافة البند.');
    }

    public function destroy(QuotationItem $quotation_item): RedirectResponse
    {
        $quotation = $quotation_item->quotation;
        $quotation_item->delete();
        $quotation->recomputeTotals();

        return back()->with('success', 'تم حذف البند.');
    }
}
