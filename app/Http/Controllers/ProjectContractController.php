<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectContractController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:contracts.view', only: ['index', 'show']),
            new Middleware('can:contracts.create', only: ['create', 'store']),
            new Middleware('can:contracts.edit', only: ['edit', 'update']),
            new Middleware('can:contracts.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');

        $contracts = ProjectContract::query()
            ->with('project')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('contract_number', 'like', "%{$search}%");
            }))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('contracts.index', compact('contracts', 'search', 'status'));
    }

    public function show(ProjectContract $contract): View
    {
        $contract->load(['project', 'creator']);

        return view('contracts.show', compact('contract'));
    }

    public function create(Request $request): View
    {
        return view('contracts.form', [
            'contract' => new ProjectContract([
                'status' => 'draft',
                'contract_type' => 'main',
                'project_id' => $request->integer('project_id') ?: null,
            ]),
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        ProjectContract::create($data);

        return redirect()->route('contracts.index')->with('success', 'تمت إضافة العقد بنجاح.');
    }

    public function edit(ProjectContract $contract): View
    {
        return view('contracts.form', [
            'contract' => $contract,
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, ProjectContract $contract): RedirectResponse
    {
        $contract->update($this->validateData($request, $contract));

        return redirect()->route('contracts.index')->with('success', 'تم تحديث العقد.');
    }

    public function destroy(ProjectContract $contract): RedirectResponse
    {
        $contract->delete();

        return back()->with('success', 'تم حذف العقد.');
    }

    private function validateData(Request $request, ?ProjectContract $contract = null): array
    {
        return $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'contract_number' => ['required', 'string', 'max:50', Rule::unique('project_contracts', 'contract_number')->ignore($contract)],
            'contract_type' => ['required', 'in:'.implode(',', array_keys(ProjectContract::TYPES))],
            'title' => ['required', 'string', 'max:255'],
            'first_party' => ['required', 'string', 'max:255'],
            'second_party' => ['required', 'string', 'max:255'],
            'signing_date' => ['required', 'date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'contract_value' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:'.implode(',', array_keys(ProjectContract::STATUSES))],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'signed_date' => ['nullable', 'date'],
            'advance_payment' => ['nullable', 'numeric', 'min:0'],
            'retention_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'warranty_months' => ['nullable', 'integer', 'min:0'],
            'consultant' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
