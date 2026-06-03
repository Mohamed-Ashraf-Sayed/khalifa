<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PurchaseOrderItemController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:purchase_orders.edit')];
    }

    public function store(Request $request, PurchaseOrder $purchase_order): RedirectResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'material_id' => ['nullable', 'exists:materials,id'],
            'unit' => ['nullable', 'string', 'max:30'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $purchase_order->items()->create([
            'description' => $data['description'],
            'material_id' => $data['material_id'] ?? null,
            'unit' => $data['unit'] ?? null,
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'],
            'total_price' => bcmul((string) $data['quantity'], (string) $data['unit_price'], 2),
        ]);
        $purchase_order->recomputeTotals();

        return back()->with('success', 'تمت إضافة البند.');
    }

    public function destroy(PurchaseOrderItem $purchase_order_item): RedirectResponse
    {
        $order = $purchase_order_item->purchaseOrder;
        $purchase_order_item->delete();
        $order->recomputeTotals();

        return back()->with('success', 'تم حذف البند.');
    }
}
