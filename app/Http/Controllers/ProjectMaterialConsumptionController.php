<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMaterialConsumption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ProjectMaterialConsumptionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:projects.edit')];
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'material_id' => ['required', 'exists:materials,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit' => ['nullable', 'string', 'max:30'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'consumption_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        // سجل تكلفة فقط — القيمة = الكمية × سعر الوحدة (bcmath). لا يمسّ المخزون.
        $data['total_value'] = bcmul((string) $data['quantity'], (string) $data['unit_price'], 2);
        $data['created_by'] = $request->user()->id;

        $project->materialConsumptions()->create($data);

        return back()->with('success', 'تم تسجيل استهلاك المادة.');
    }

    public function destroy(ProjectMaterialConsumption $project_material_consumption): RedirectResponse
    {
        $project_material_consumption->delete();

        return back()->with('success', 'تم حذف سجل الاستهلاك.');
    }
}
