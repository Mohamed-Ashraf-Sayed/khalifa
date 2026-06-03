<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PurchaseOrderController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:purchase_orders.view', only: ['index', 'show']),
            new Middleware('can:purchase_orders.create', only: ['create', 'store']),
            new Middleware('can:purchase_orders.edit', only: ['edit', 'update']),
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

        return view('purchase_orders.index', compact('purchaseOrders', 'search', 'status'));
    }

    public function show(PurchaseOrder $purchase_order): View
    {
        $purchase_order->load(['supplier', 'project', 'creator']);

        return view('purchase_orders.show', ['purchaseOrder' => $purchase_order]);
    }

    public function create(): View
    {
        return view('purchase_orders.form', $this->formData(
            new PurchaseOrder(['order_date' => now()->toDateString(), 'status' => 'draft', 'total_amount' => 0])
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        PurchaseOrder::create($data);

        return redirect()->route('purchase_orders.index')->with('success', 'تمت إضافة أمر الشراء.');
    }

    public function edit(PurchaseOrder $purchaseOrder): View
    {
        return view('purchase_orders.form', $this->formData($purchaseOrder));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $purchaseOrder->update($this->validateData($request, $purchaseOrder));

        return redirect()->route('purchase_orders.index')->with('success', 'تم تحديث أمر الشراء.');
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $purchaseOrder->delete();

        return back()->with('success', 'تم حذف أمر الشراء.');
    }

    private function formData(PurchaseOrder $purchaseOrder): array
    {
        return [
            'purchaseOrder' => $purchaseOrder,
            'suppliers' => Supplier::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
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
            'status' => ['required', 'in:'.implode(',', array_keys(PurchaseOrder::STATUSES))],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
