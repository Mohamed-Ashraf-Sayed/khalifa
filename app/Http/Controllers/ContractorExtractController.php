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
            new Middleware('can:contractors.view', only: ['index', 'show']),
            new Middleware('can:contractors.create', only: ['create', 'store']),
            new Middleware('can:contractors.edit', only: ['edit', 'update', 'approve', 'releaseRetention']),
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

    public function show(ContractorExtract $contractor_extract): View
    {
        $contractor_extract->load(['contractor', 'project', 'creator', 'approver', 'items']);

        return view('contractor_extracts.show', ['extract' => $contractor_extract]);
    }

    public function create(): View
    {
        return view('contractor_extracts.form', $this->formData(new ContractorExtract([
            'extract_number' => $this->nextNumber(),
            'extract_date' => now()->toDateString(),
            'status' => 'pending',
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;

        $extract = ContractorExtract::create($data);
        $extract->recomputeTotals();

        return redirect()->route('contractor_extracts.show', $extract)->with('success', 'تم إنشاء المستخلص. أضِف بنود الأعمال الآن.');
    }

    public function edit(ContractorExtract $contractorExtract): View
    {
        return view('contractor_extracts.form', $this->formData($contractorExtract));
    }

    public function update(Request $request, ContractorExtract $contractorExtract): RedirectResponse
    {
        $contractorExtract->update($this->validateData($request));
        $contractorExtract->recomputeTotals();

        return redirect()->route('contractor_extracts.show', $contractorExtract)->with('success', 'تم تحديث المستخلص.');
    }

    public function approve(Request $request, ContractorExtract $contractorExtract): RedirectResponse
    {
        $contractorExtract->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'تم اعتماد المستخلص.');
    }

    public function releaseRetention(ContractorExtract $contractor_extract): RedirectResponse
    {
        $contractor_extract->releaseRetention();

        return back()->with('success', 'تم تحرير المبلغ المحتجز.');
    }

    public function destroy(ContractorExtract $contractorExtract): RedirectResponse
    {
        // منع حذف مستخلص عليه دفعات
        if ($contractorExtract->paid_amount > 0) {
            return back()->with('error', 'لا يمكن حذف مستخلص سُجّلت عليه دفعات. احذف الدفعات أولاً.');
        }

        $contractorExtract->delete();

        return back()->with('success', 'تم حذف المستخلص.');
    }

    private function nextNumber(): string
    {
        $year = now()->format('Y');
        $count = ContractorExtract::whereYear('created_at', $year)->count() + 1;

        return sprintf('EXT-%s-%04d', $year, $count);
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
        return $request->validate([
            'extract_number' => ['required', 'string', 'max:50'],
            'contractor_id' => ['required', 'exists:contractors,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'extract_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'additions' => ['nullable', 'numeric', 'min:0'],
            'deductions' => ['nullable', 'numeric', 'min:0'],
            'execution_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'retention_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', 'in:'.implode(',', array_keys(ContractorExtract::STATUSES))],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
