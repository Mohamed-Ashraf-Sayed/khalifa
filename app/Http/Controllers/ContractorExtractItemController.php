<?php

namespace App\Http\Controllers;

use App\Models\ContractorExtract;
use App\Models\ContractorExtractItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ContractorExtractItemController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:contractors.edit')];
    }

    public function store(Request $request, ContractorExtract $contractor_extract): RedirectResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:30'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'prev_quantity' => ['nullable', 'numeric', 'min:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $contractor_extract->items()->create([
            'description' => $data['description'],
            'unit' => $data['unit'] ?? null,
            'quantity' => $data['quantity'],
            'prev_quantity' => $data['prev_quantity'] ?? 0,
            'unit_price' => $data['unit_price'],
            'total_price' => bcmul((string) $data['quantity'], (string) $data['unit_price'], 2),
        ]);
        $contractor_extract->recomputeTotals();

        return back()->with('success', 'تمت إضافة البند.');
    }

    public function destroy(ContractorExtractItem $contractor_extract_item): RedirectResponse
    {
        $extract = $contractor_extract_item->extract;
        $contractor_extract_item->delete();
        $extract->recomputeTotals();

        return back()->with('success', 'تم حذف البند.');
    }
}
