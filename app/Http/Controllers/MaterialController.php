<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Project;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class MaterialController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:materials.view', only: ['index', 'show', 'report']),
            new Middleware('can:materials.create', only: ['create', 'store']),
            new Middleware('can:materials.edit', only: ['edit', 'update']),
            new Middleware('can:materials.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $category = (string) $request->input('category', '');
        $supplierId = (string) $request->input('supplier_id', '');
        $projectId = (string) $request->input('project_id', '');
        $lowStock = (string) $request->input('low_stock', '');

        $base = Material::query()
            ->with(['project', 'supplier'])
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($category !== '', fn ($q) => $q->where('category', $category))
            ->when($supplierId !== '', fn ($q) => $q->where('supplier_id', $supplierId))
            ->when($projectId !== '', fn ($q) => $q->where('project_id', $projectId))
            ->when($lowStock === '1', fn ($q) => $q->lowStock());

        // إحصائيات على نفس مجموعة الفلترة
        $statsCollection = (clone $base)->get();
        $stats = [
            'count' => $statsCollection->count(),
            'value' => $statsCollection->reduce(
                fn (string $carry, Material $m) => bcadd($carry, $m->stockValue(), 2),
                '0.00'
            ),
            'low_stock' => $statsCollection->filter(fn (Material $m) => $m->isLowStock())->count(),
        ];

        $materials = (clone $base)->latest()->paginate(15)->withQueryString();

        $suppliers = Supplier::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();

        return view('materials.index', compact(
            'materials', 'search', 'category', 'supplierId', 'projectId', 'lowStock',
            'stats', 'suppliers', 'projects'
        ));
    }

    public function report(Request $request): View|StreamedResponse
    {
        $materials = Material::query()->with(['project', 'supplier'])->orderBy('name')->get();

        $totalValue = $materials->reduce(
            fn (string $carry, Material $m) => bcadd($carry, $m->stockValue(), 2),
            '0.00'
        );

        $lowStockMaterials = $materials->filter(fn (Material $m) => $m->isLowStock());

        if ((string) $request->input('export') === 'csv') {
            return $this->exportCsv($materials);
        }

        return view('materials.report', compact('materials', 'totalValue', 'lowStockMaterials'));
    }

    private function exportCsv($materials): StreamedResponse
    {
        $filename = 'inventory_valuation_'.date('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($materials) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM لضمان العرض الصحيح للعربية في Excel
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['name', 'category', 'unit', 'current_stock', 'min_stock', 'unit_price', 'stock_value']);
            foreach ($materials as $m) {
                fputcsv($out, [
                    $m->name,
                    Material::CATEGORIES[$m->category] ?? $m->category,
                    $m->unit,
                    $m->current_stock,
                    $m->min_stock,
                    $m->unit_price,
                    $m->stockValue(),
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(Material $material): View
    {
        $material->load([
            'project', 'supplier', 'creator',
            'movements' => fn ($q) => $q->with(['project', 'employee', 'toProject'])
                ->latest('movement_date')->latest('id')->limit(20),
        ]);

        return view('materials.show', compact('material'));
    }

    public function create(): View
    {
        return view('materials.form', [
            'material' => new Material(['category' => 'other']),
            'projects' => Project::orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        Material::create($data);

        return redirect()->route('materials.index')->with('success', 'تمت إضافة المادة بنجاح.');
    }

    public function edit(Material $material): View
    {
        return view('materials.form', [
            'material' => $material,
            'projects' => Project::orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Material $material): RedirectResponse
    {
        $material->update($this->validateData($request));

        return redirect()->route('materials.index')->with('success', 'تم تحديث المادة.');
    }

    public function destroy(Material $material): RedirectResponse
    {
        $material->delete();

        return back()->with('success', 'تم حذف المادة.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:'.implode(',', array_keys(Material::CATEGORIES))],
            'unit' => ['required', 'string', 'max:20'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'current_stock' => ['required', 'numeric', 'min:0'],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'warehouse_location' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
