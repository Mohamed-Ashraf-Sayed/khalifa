<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Project;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class MaterialController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:materials.view', only: ['index', 'show']),
            new Middleware('can:materials.create', only: ['create', 'store']),
            new Middleware('can:materials.edit', only: ['edit', 'update']),
            new Middleware('can:materials.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $category = (string) $request->input('category', '');

        $materials = Material::query()
            ->with(['project', 'supplier'])
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($category !== '', fn ($q) => $q->where('category', $category))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('materials.index', compact('materials', 'search', 'category'));
    }

    public function show(Material $material): View
    {
        $material->load(['project', 'supplier', 'creator']);

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
            'notes' => ['nullable', 'string'],
        ]);
    }
}
