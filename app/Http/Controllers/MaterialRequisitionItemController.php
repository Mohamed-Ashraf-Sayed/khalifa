<?php

namespace App\Http\Controllers;

use App\Models\MaterialRequisition;
use App\Models\MaterialRequisitionItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class MaterialRequisitionItemController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:materials.edit')];
    }

    public function store(Request $request, MaterialRequisition $materialRequisition): RedirectResponse
    {
        if ($materialRequisition->status !== 'pending') {
            return back()->with('error', 'لا يمكن تعديل الأصناف إلا قبل الاعتماد.');
        }

        $data = $request->validate([
            'material_id' => ['required', 'exists:materials,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $materialRequisition->items()->create([
            'material_id' => $data['material_id'],
            'quantity' => $data['quantity'],
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'تمت إضافة الصنف.');
    }

    public function destroy(MaterialRequisitionItem $materialRequisitionItem): RedirectResponse
    {
        $requisition = $materialRequisitionItem->requisition;
        if ($requisition && $requisition->status !== 'pending') {
            return back()->with('error', 'لا يمكن حذف الأصناف إلا قبل الاعتماد.');
        }

        $materialRequisitionItem->delete();

        return back()->with('success', 'تم حذف الصنف.');
    }
}
