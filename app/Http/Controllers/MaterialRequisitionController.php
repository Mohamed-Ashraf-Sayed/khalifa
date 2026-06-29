<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Material;
use App\Models\MaterialRequisition;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MaterialRequisitionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:materials.view', only: ['index', 'show', 'print']),
            new Middleware('can:materials.create', only: ['create', 'store']),
            new Middleware('can:materials.edit', only: ['edit', 'update', 'approve', 'issue', 'reject']),
            new Middleware('can:materials.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');

        $requisitions = MaterialRequisition::query()
            ->with(['project', 'creator'])
            ->when($search !== '', fn ($q) => $q->where('requisition_number', 'like', "%{$search}%"))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'count' => MaterialRequisition::count(),
            'pending' => MaterialRequisition::where('status', 'pending')->count(),
            'issued' => MaterialRequisition::where('status', 'issued')->count(),
        ];

        return view('material_requisitions.index', compact('requisitions', 'search', 'status', 'stats'));
    }

    public function show(MaterialRequisition $materialRequisition): View
    {
        $materialRequisition->load(['project', 'creator', 'approver', 'items.material']);

        return view('material_requisitions.show', [
            'requisition' => $materialRequisition,
            'materials' => Material::orderBy('name')->get(),
        ]);
    }

    public function print(MaterialRequisition $materialRequisition): View
    {
        $materialRequisition->load(['project', 'creator', 'approver', 'items.material']);

        return view('material_requisitions.print', [
            'requisition' => $materialRequisition,
        ]);
    }

    public function create(): View
    {
        return view('material_requisitions.form', $this->formData(
            new MaterialRequisition([
                'requisition_number' => $this->nextNumber(),
                'request_date' => now()->toDateString(),
                'status' => 'pending',
            ])
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        $requisition = MaterialRequisition::create($data);

        return redirect()->route('material_requisitions.show', $requisition)->with('success', 'تم إنشاء إذن الصرف. أضِف الأصناف الآن.');
    }

    public function edit(MaterialRequisition $materialRequisition): View
    {
        return view('material_requisitions.form', $this->formData($materialRequisition));
    }

    public function update(Request $request, MaterialRequisition $materialRequisition): RedirectResponse
    {
        $materialRequisition->update($this->validateData($request, $materialRequisition));

        return redirect()->route('material_requisitions.show', $materialRequisition)->with('success', 'تم تحديث إذن الصرف.');
    }

    /** اعتماد إذن الصرف: pending -> approved مع تسجيل المعتمِد وتاريخه. */
    public function approve(Request $request, MaterialRequisition $materialRequisition): RedirectResponse
    {
        if ($materialRequisition->status !== 'pending') {
            return back()->with('error', 'لا يمكن اعتماد إذن الصرف في حالته الحالية.');
        }

        $materialRequisition->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'تم اعتماد إذن الصرف.');
    }

    /** رفض إذن الصرف وهو بانتظار الاعتماد. */
    public function reject(Request $request, MaterialRequisition $materialRequisition): RedirectResponse
    {
        if ($materialRequisition->status !== 'pending') {
            return back()->with('error', 'لا يمكن رفض إذن الصرف في حالته الحالية.');
        }

        $materialRequisition->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'تم رفض إذن الصرف.');
    }

    /**
     * صرف الأصناف من المخزون: متاح فقط للإذن المعتمَد.
     * لكل صنف يُنشئ حركة مخزون من نوع out مع خصم الرصيد، ويمنع الصرف عند عدم كفاية الرصيد.
     */
    public function issue(Request $request, MaterialRequisition $materialRequisition): RedirectResponse
    {
        if ($materialRequisition->status !== 'approved') {
            return back()->with('error', 'لا يمكن صرف إذن غير معتمَد.');
        }

        if ($materialRequisition->items()->count() === 0) {
            return back()->with('error', 'لا توجد أصناف للصرف.');
        }

        try {
            DB::transaction(function () use ($materialRequisition, $request) {
                foreach ($materialRequisition->items as $item) {
                    $material = Material::lockForUpdate()->find($item->material_id);
                    if (! $material) {
                        continue;
                    }

                    $before = (string) $material->current_stock;
                    $after = bcsub($before, (string) $item->quantity, 2);

                    if (bccomp($after, '0', 2) < 0) {
                        throw new \RuntimeException('الرصيد غير كافٍ للصنف: '.$material->name);
                    }

                    $unitPrice = (string) ($material->unit_price ?? 0);

                    InventoryMovement::create([
                        'material_id' => $material->id,
                        'type' => 'out',
                        'quantity' => $item->quantity,
                        'unit_price' => $unitPrice,
                        'total_value' => bcmul((string) $item->quantity, $unitPrice, 2),
                        'stock_before' => $before,
                        'stock_after' => $after,
                        'movement_date' => now()->toDateString(),
                        'project_id' => $materialRequisition->project_id,
                        'reason' => 'صرف إذن مواد '.$materialRequisition->requisition_number,
                        'reference_type' => 'material_requisition',
                        'reference_id' => $materialRequisition->id,
                        'created_by' => $request->user()->id,
                    ]);

                    $material->current_stock = $after;
                    $material->save();

                    $item->update(['issued_quantity' => $item->quantity]);
                }

                $materialRequisition->update(['status' => 'issued']);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم صرف الأصناف وخصمها من المخزون.');
    }

    public function destroy(MaterialRequisition $materialRequisition): RedirectResponse
    {
        $materialRequisition->delete();

        return back()->with('success', 'تم حذف إذن الصرف.');
    }

    private function nextNumber(): string
    {
        return app(\App\Services\DocumentNumberGenerator::class)->generate(MaterialRequisition::class, 'MR');
    }

    private function formData(MaterialRequisition $materialRequisition): array
    {
        return [
            'requisition' => $materialRequisition,
            'projects' => Project::orderBy('name')->get(),
        ];
    }

    private function validateData(Request $request, ?MaterialRequisition $materialRequisition = null): array
    {
        return $request->validate([
            'requisition_number' => [
                'required', 'string', 'max:50',
                Rule::unique('material_requisitions', 'requisition_number')->ignore($materialRequisition?->id),
            ],
            'project_id' => ['nullable', 'exists:projects,id'],
            'request_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
