<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Material;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PurchaseOrderController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:purchase_orders.view', only: ['index', 'show']),
            new Middleware('can:purchase_orders.create', only: ['create', 'store']),
            new Middleware('can:purchase_orders.edit', only: ['edit', 'update', 'approve', 'receive']),
            new Middleware('can:purchase_orders.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');

        $purchaseOrders = PurchaseOrder::query()
            ->with(['supplier', 'project'])
            ->when($search !== '', fn ($q) => $q->where('order_number', 'like', "%{$search}%"))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'count' => PurchaseOrder::count(),
            'net' => (float) PurchaseOrder::sum('net_amount'),
            'paid' => (float) PurchaseOrder::sum('paid_amount'),
            'pending' => PurchaseOrder::whereIn('status', ['draft', 'pending'])->count(),
        ];

        return view('purchase_orders.index', compact('purchaseOrders', 'search', 'status', 'stats'));
    }

    public function show(PurchaseOrder $purchase_order): View
    {
        $purchase_order->load(['supplier', 'project', 'creator', 'approver', 'items']);

        return view('purchase_orders.show', ['purchaseOrder' => $purchase_order]);
    }

    public function create(): View
    {
        return view('purchase_orders.form', $this->formData(
            new PurchaseOrder([
                'order_number' => $this->nextNumber(),
                'order_date' => now()->toDateString(),
                'status' => 'draft',
            ])
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        $order = PurchaseOrder::create($data);
        $order->recomputeTotals();

        return redirect()->route('purchase_orders.show', $order)->with('success', 'تم إنشاء أمر الشراء. أضِف الأصناف الآن.');
    }

    public function edit(PurchaseOrder $purchaseOrder): View
    {
        return view('purchase_orders.form', $this->formData($purchaseOrder));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $purchaseOrder->update($this->validateData($request, $purchaseOrder));
        $purchaseOrder->recomputeTotals();

        return redirect()->route('purchase_orders.show', $purchaseOrder)->with('success', 'تم تحديث أمر الشراء.');
    }

    public function approve(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $purchaseOrder->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'تم اعتماد أمر الشراء.');
    }

    /**
     * استلام أمر الشراء: يحدّد الكميات المستلَمة، ويضيف الأصناف للمخزون
     * (لو add_to_inventory مفعّل) عبر حركات مخزون من نوع in مع تحديث الرصيد.
     */
    public function receive(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if ($purchaseOrder->status === 'received') {
            return back()->with('error', 'أمر الشراء مستلَم بالفعل.');
        }

        DB::transaction(function () use ($purchaseOrder, $request) {
            foreach ($purchaseOrder->items as $item) {
                $item->update(['received_quantity' => $item->quantity]);

                if ($purchaseOrder->add_to_inventory && $item->material_id) {
                    $material = Material::lockForUpdate()->find($item->material_id);
                    if ($material) {
                        $before = (string) $material->current_stock;
                        $after = bcadd($before, (string) $item->quantity, 2);

                        InventoryMovement::create([
                            'material_id' => $material->id,
                            'type' => 'in',
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'total_value' => bcmul((string) $item->quantity, (string) $item->unit_price, 2),
                            'stock_before' => $before,
                            'stock_after' => $after,
                            'movement_date' => now()->toDateString(),
                            'project_id' => $purchaseOrder->project_id,
                            'reason' => 'استلام أمر شراء '.$purchaseOrder->order_number,
                            'reference_type' => 'purchase_order',
                            'reference_id' => $purchaseOrder->id,
                            'created_by' => $request->user()->id,
                        ]);

                        $material->current_stock = $after;
                        $material->save();
                    }
                }
            }

            $purchaseOrder->update([
                'status' => 'received',
                'actual_delivery' => $purchaseOrder->actual_delivery ?? now()->toDateString(),
            ]);
        });

        return back()->with('success', 'تم استلام أمر الشراء'.($purchaseOrder->add_to_inventory ? ' وإضافة الأصناف للمخزون.' : '.'));
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $purchaseOrder->delete();

        return back()->with('success', 'تم حذف أمر الشراء.');
    }

    private function nextNumber(): string
    {
        $year = now()->format('Y');
        $count = PurchaseOrder::whereYear('created_at', $year)->count() + 1;

        return sprintf('PO-%s-%04d', $year, $count);
    }

    private function formData(PurchaseOrder $purchaseOrder): array
    {
        return [
            'purchaseOrder' => $purchaseOrder,
            'suppliers' => Supplier::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
            'materials' => Material::orderBy('name')->get(),
        ];
    }

    private function validateData(Request $request, ?PurchaseOrder $purchaseOrder = null): array
    {
        return $request->validate([
            'order_number' => [
                'required', 'string', 'max:50',
                Rule::unique('purchase_orders', 'order_number')->ignore($purchaseOrder?->id),
            ],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'order_date' => ['required', 'date'],
            'expected_delivery' => ['nullable', 'date'],
            'actual_delivery' => ['nullable', 'date'],
            'status' => ['required', 'in:'.implode(',', array_keys(PurchaseOrder::STATUSES))],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'add_to_inventory' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
