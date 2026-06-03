<?php

namespace App\Http\Controllers;

use App\Models\Contractor;
use App\Models\ContractorExtract;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ContractorExtractController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:contractors.view', only: ['index']),
            new Middleware('can:contractors.create', only: ['create', 'store']),
            new Middleware('can:contractors.edit', only: ['edit', 'update']),
            new Middleware('can:contractors.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');

        $extracts = ContractorExtract::query()
            ->with(['contractor', 'project'])
            ->when($search !== '', fn ($q) => $q->where('extract_number', 'like', "%{$search}%"))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest('extract_date')
            ->paginate(15)
            ->withQueryString();

        return view('contractor_extracts.index', compact('extracts', 'search', 'status'));
    }

    public function create(): View
    {
        return view('contractor_extracts.form', $this->formData(new ContractorExtract([
            'extract_date' => now()->toDateString(),
            'status' => 'pending',
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;

        ContractorExtract::create($data);

        return redirect()->route('contractor_extracts.index')->with('success', 'تمت إضافة المستخلص.');
    }

    public function edit(ContractorExtract $contractorExtract): View
    {
        return view('contractor_extracts.form', $this->formData($contractorExtract));
    }

    public function update(Request $request, ContractorExtract $contractorExtract): RedirectResponse
    {
        $contractorExtract->update($this->validateData($request));

        return redirect()->route('contractor_extracts.index')->with('success', 'تم تحديث المستخلص.');
    }

    public function destroy(ContractorExtract $contractorExtract): RedirectResponse
    {
        $contractorExtract->delete();

        return back()->with('success', 'تم حذف المستخلص.');
    }

    private function formData(ContractorExtract $contractorExtract): array
    {
        return [
            'contractorExtract' => $contractorExtract,
            'contractors' => Contractor::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
        ];
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'extract_number' => ['required', 'string', 'max:50'],
            'contractor_id' => ['required', 'exists:contractors,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'extract_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'deductions' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:'.implode(',', array_keys(ContractorExtract::STATUSES))],
            'notes' => ['nullable', 'string'],
        ]);

        // صافي المستخلص = الإجمالي - الخصومات (يُحسب على السيرفر)
        $data['net_amount'] = (float) $data['total_amount'] - (float) $data['deductions'];

        return $data;
    }
}
