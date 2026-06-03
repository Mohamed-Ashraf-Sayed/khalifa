<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Material;
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
            new Middleware('can:materials.view', only: ['index']),
            new Middleware('can:materials.edit', only: ['create', 'store', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $type = (string) $request->input('type', '');

        $movements = InventoryMovement::query()
            ->with(['material', 'project'])
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->latest('movement_date')
            ->paginate(15)
            ->withQueryString();

        return view('inventory_movements.index', compact('movements', 'type'));
    }

    public function create(): View
    {
        return view('inventory_movements.form', [
            'materials' => Material::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'material_id' => ['required', 'exists:materials,id'],
            'type' => ['required', 'in:in,out'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'movement_date' => ['required', 'date'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['created_by'] = $request->user()->id;

        try {
            DB::transaction(function () use ($data) {
                $material = Material::lockForUpdate()->findOrFail($data['material_id']);

                if ($data['type'] === 'out' && bccomp((string) $material->current_stock, (string) $data['quantity'], 2) < 0) {
                    throw new \DomainException('الكمية المطلوب صرفها أكبر من الرصيد المتاح ('.$material->current_stock.').');
                }

                InventoryMovement::create($data);

                $material->current_stock = $data['type'] === 'in'
                    ? bcadd((string) $material->current_stock, (string) $data['quantity'], 2)
                    : bcsub((string) $material->current_stock, (string) $data['quantity'], 2);
                $material->save();
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
                // عكس أثر الحركة
                $material->current_stock = $inventory_movement->type === 'in'
                    ? bcsub((string) $material->current_stock, (string) $inventory_movement->quantity, 2)
                    : bcadd((string) $material->current_stock, (string) $inventory_movement->quantity, 2);
                $material->save();
            }
            $inventory_movement->delete();
        });

        return back()->with('success', 'تم حذف الحركة وعكس أثرها على المخزون.');
    }
}
