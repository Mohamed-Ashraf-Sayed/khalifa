<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Tax;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class TaxController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:taxes.view', only: ['index', 'show']),
            new Middleware('can:taxes.create', only: ['create', 'store']),
            new Middleware('can:taxes.edit', only: ['edit', 'update']),
            new Middleware('can:taxes.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');

        $taxes = Tax::query()
            ->with('project')
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('taxes.index', compact('taxes', 'search', 'status'));
    }

    public function show(Tax $tax): View
    {
        $tax->load(['project', 'creator']);

        return view('taxes.show', compact('tax'));
    }

    public function create(): View
    {
        return view('taxes.form', [
            'tax' => new Tax(['tax_type' => 'vat', 'status' => 'pending']),
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        Tax::create($data);

        return redirect()->route('taxes.index')->with('success', 'تمت إضافة الضريبة بنجاح.');
    }

    public function edit(Tax $tax): View
    {
        return view('taxes.form', [
            'tax' => $tax,
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Tax $tax): RedirectResponse
    {
        $tax->update($this->validateData($request));

        return redirect()->route('taxes.index')->with('success', 'تم تحديث الضريبة.');
    }

    public function destroy(Tax $tax): RedirectResponse
    {
        $tax->delete();

        return back()->with('success', 'تم حذف الضريبة.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tax_type' => ['required', 'in:'.implode(',', array_keys(Tax::TYPES))],
            'project_id' => ['nullable', 'exists:projects,id'],
            'rate' => ['required', 'numeric', 'min:0'],
            'base_amount' => ['required', 'numeric', 'min:0'],
            'amount' => ['required', 'numeric', 'min:0'],
            'period' => ['nullable', 'string', 'max:20'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', 'in:'.implode(',', array_keys(Tax::STATUSES))],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
