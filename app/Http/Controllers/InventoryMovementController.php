<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\InventoryMovement;
use App\Models\Material;
use App\Models\MaterialRequisition;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryMovementController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:materials.view', only: ['index', 'show']),
            new Middleware('can:materials.edit', only: ['create', 'store', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $type = (string) $request->input('type', '');
        $materialId = (string) $request->input('material_id', '');

        $base = InventoryMovement::query()
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->when($materialId !== '', fn ($q) => $q->where('material_id', $materialId));

        $movements = (clone $base)
            ->with(['material', 'project', 'employee', 'toProject'])
            ->latest('movement_date')
            ->paginate(15)
            ->withQueryString();

        // بطاقات إحصائية على نفس مجموعة الفلترة (عرض فقط لقيم محسوبة مسبقاً)
        $stats = [
            'count' => (clone $base)->count(),
            'in_value' => (string) ((clone $base)->where('type', 'in')->sum('total_value')),
            'out_value' => (string) ((clone $base)->where('type', 'out')->sum('total_value')),
        ];

        $materials = Material::orderBy('name')->get();

        return view('inventory_movements.index', compact('movements', 'type', 'materialId', 'materials', 'stats'));
    }

    public function show(InventoryMovement $inventory_movement): View
    {
        $inventory_movement->load(['material', 'project', 'employee', 'toProject', 'creator']);

        // المستند المرجعي: إذن صرف مواد (عند الصرف من إذن)
        $requisition = null;
        if ($inventory_movement->reference_type === 'material_requisition' && $inventory_movement->reference_id) {
            $requisition = MaterialRequisition::find($inventory_movement->reference_id);
        }

        return view('inventory_movements.show', [
            'movement' => $inventory_movement,
            'requisition' => $requisition,
        ]);
    }

    public function create(): View
    {
        return view('inventory_movements.form', [
            'materials' => Material::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
            'employees' => Employee::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'material_id' => ['required', 'exists:materials,id'],
            'type' => ['required', 'in:'.implode(',', array_keys(InventoryMovement::TYPES))],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'movement_date' => ['required', 'date'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'to_project_id' => ['nullable', 'exists:projects,id', 'required_if:type,transfer'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'warehouse_location' => ['nullable', 'string', 'max:100'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'adjustment_direction' => ['nullable', 'in:increase,decrease', 'required_if:type,adjustment'],
        ]);
        $data['created_by'] = $request->user()->id;

        try {
            DB::transaction(function () use ($data) {
                $material = Material::lockForUpdate()->findOrFail($data['material_id']);

                $before = (string) $material->current_stock;
                $qty = (string) $data['quantity'];
                $type = $data['type'];

                // حساب الرصيد بعد الحركة حسب النوع (التحويل لا يغيّر الرصيد)
                if ($type === 'in') {
                    $after = bcadd($before, $qty, 2);
                } elseif ($type === 'out') {
                    if (bccomp($before, $qty, 2) < 0) {
                        throw new \DomainException('الكمية المطلوب صرفها أكبر من الرصيد المتاح ('.$before.').');
                    }
                    $after = bcsub($before, $qty, 2);
                } elseif ($type === 'adjustment') {
                    if (($data['adjustment_direction'] ?? '') === 'increase') {
                        $after = bcadd($before, $qty, 2);
                    } else {
                        if (bccomp($before, $qty, 2) < 0) {
                            throw new \DomainException('كمية التسوية بالنقص أكبر من الرصيد المتاح ('.$before.').');
                        }
                        $after = bcsub($before, $qty, 2);
                    }
                } else { // transfer => relocation only, stock unchanged
                    $after = $before;
                }

                $unitPrice = ($data['unit_price'] ?? '') === '' || $data['unit_price'] === null
                    ? (string) $material->unit_price
                    : (string) $data['unit_price'];

                $data['unit_price'] = $unitPrice;
                $data['total_value'] = bcmul($qty, $unitPrice, 2);
                $data['stock_before'] = $before;
                $data['stock_after'] = $after;

                InventoryMovement::create($data);

                // حفظ الرصيد فقط عند تغيّره (التحويل لا يغيّر الرصيد)
                if ($type !== 'transfer') {
                    $material->current_stock = $after;
                    $material->save();
                }
            });
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('inventory_movements.index')->with('success', 'تم تسجيل الحركة وتحديث المخزون.');
    }

    public function destroy(InventoryMovement $inventory_movement): RedirectResponse
    {
        DB::transaction(function () use ($inventory_movement) {
            $material = Material::lockForUpdate()->find($inventory_movement->material_id);
            if ($material) {
                $type = $inventory_movement->type;
                // عكس أثر الحركة على المخزون (التحويل ليس له أثر)
                if ($type === 'in') {
                    $material->current_stock = bcsub((string) $material->current_stock, (string) $inventory_movement->quantity, 2);
                    $material->save();
                } elseif ($type === 'out') {
                    $material->current_stock = bcadd((string) $material->current_stock, (string) $inventory_movement->quantity, 2);
                    $material->save();
                } elseif ($type === 'adjustment') {
                    // عكس اتجاه التسوية: الفرق بين stock_after و stock_before
                    $material->current_stock = bcsub(
                        (string) $material->current_stock,
                        bcsub((string) $inventory_movement->stock_after, (string) $inventory_movement->stock_before, 2),
                        2
                    );
                    $material->save();
                }
            }
            $inventory_movement->delete();
        });

        return back()->with('success', 'تم حذف الحركة وعكس أثرها على المخزون.');
    }
}
