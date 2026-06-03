<?php

namespace App\Http\Controllers;

use App\Models\CostCenter;
use App\Models\Project;
use App\Models\ProjectContract;
use App\Models\ProjectCost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class ProjectCostController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:projects.view', only: ['index', 'show', 'report']),
            new Middleware('can:projects.create', only: ['create', 'store']),
            new Middleware('can:projects.edit', only: ['edit', 'update']),
            new Middleware('can:projects.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $projectId = (string) $request->input('project_id', '');
        $workItem = trim((string) $request->input('work_item', ''));
        $contractorSupplier = trim((string) $request->input('contractor_supplier', ''));

        $base = ProjectCost::query()
            ->when($projectId !== '', fn ($q) => $q->where('project_id', $projectId))
            ->when($workItem !== '', fn ($q) => $q->where('work_item', 'like', "%{$workItem}%"))
            ->when($contractorSupplier !== '', fn ($q) => $q->where('contractor_supplier', 'like', "%{$contractorSupplier}%"));

        $costs = (clone $base)
            ->with(['project'])
            ->latest('cost_date')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => (string) (clone $base)->sum('amount'),
            'count' => (clone $base)->count(),
            'work_items' => (clone $base)->distinct('work_item')->count('work_item'),
        ];

        return view('project_costs.index', [
            'costs' => $costs,
            'projects' => Project::orderBy('name')->get(),
            'projectId' => $projectId,
            'workItem' => $workItem,
            'contractorSupplier' => $contractorSupplier,
            'stats' => $stats,
        ]);
    }

    public function show(ProjectCost $project_cost): View
    {
        $project_cost->load(['project', 'creator']);

        return view('project_costs.show', ['cost' => $project_cost]);
    }

    public function create(): View
    {
        return view('project_costs.form', $this->formData(new ProjectCost([
            'cost_date' => now()->toDateString(),
            'quantity' => 1,
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;

        ProjectCost::create($data);

        return redirect()->route('project_costs.index')->with('success', 'تمت إضافة التكلفة.');
    }

    public function edit(ProjectCost $projectCost): View
    {
        return view('project_costs.form', $this->formData($projectCost));
    }

    public function update(Request $request, ProjectCost $projectCost): RedirectResponse
    {
        $projectCost->update($this->validateData($request));

        return redirect()->route('project_costs.index')->with('success', 'تم تحديث التكلفة.');
    }

    public function destroy(ProjectCost $projectCost): RedirectResponse
    {
        $projectCost->delete();

        return back()->with('success', 'تم حذف التكلفة.');
    }

    public function report(Request $request): View|StreamedResponse
    {
        $projects = Project::orderBy('name')->get();
        $projectId = (string) $request->input('project_id', '');

        if ($projectId === '' && $projects->isNotEmpty()) {
            $projectId = (string) $projects->first()->id;
        }

        $project = $projectId !== '' ? Project::find($projectId) : null;

        $costs = $project
            ? ProjectCost::where('project_id', $project->id)->latest('cost_date')->get()
            : collect();

        // تجميعات محايدة لقاعدة البيانات عبر الـ collections
        $byWorkItem = $costs
            ->groupBy('work_item')
            ->map(fn ($group) => $group->reduce(fn ($carry, $c) => bcadd($carry, (string) $c->amount, 2), '0'))
            ->sortDesc();

        $byContractor = $costs
            ->groupBy(fn ($c) => $c->contractor_supplier ?: 'غير محدد')
            ->map(fn ($group) => $group->reduce(fn ($carry, $c) => bcadd($carry, (string) $c->amount, 2), '0'))
            ->sortDesc();

        $totalCost = $costs->reduce(fn ($carry, $c) => bcadd($carry, (string) $c->amount, 2), '0');

        // قيمة العقد (إن وُجد عقد للمشروع) لحساب الفرق
        $contractValue = $project
            ? ProjectContract::where('project_id', $project->id)->sum('contract_value')
            : null;
        $contractValue = $contractValue !== null && (string) $contractValue !== '' ? (string) $contractValue : null;
        $variance = $contractValue !== null ? bcsub($contractValue, $totalCost, 2) : null;

        if ($project && $request->input('export') === 'csv') {
            return $this->exportCsv($project, $costs);
        }

        return view('project_costs.report', [
            'projects' => $projects,
            'project' => $project,
            'byWorkItem' => $byWorkItem,
            'byContractor' => $byContractor,
            'totalCost' => $totalCost,
            'contractValue' => $contractValue,
            'variance' => $variance,
        ]);
    }

    private function exportCsv(Project $project, $costs): StreamedResponse
    {
        $filename = 'project-costs-'.$project->id.'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->stream(function () use ($costs) {
            $out = fopen('php://output', 'w');
            // BOM لدعم العربية في Excel
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['work_item', 'contractor_supplier', 'unit', 'quantity', 'unit_price', 'amount', 'cost_date']);
            foreach ($costs as $c) {
                fputcsv($out, [
                    $c->work_item,
                    $c->contractor_supplier,
                    $c->unit,
                    $c->quantity,
                    $c->unit_price,
                    $c->amount,
                    $c->cost_date?->format('Y-m-d'),
                ]);
            }
            fclose($out);
        }, 200, $headers);
    }

    private function formData(ProjectCost $projectCost): array
    {
        return [
            'cost' => $projectCost,
            'projects' => Project::orderBy('name')->get(),
            'costCenters' => CostCenter::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'cost_center_id' => ['nullable', 'exists:cost_centers,id'],
            'work_item' => ['required', 'string', 'max:255'],
            'contractor_supplier' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:30'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'cost_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        // الإجمالي يُحسب على السيرفر: الكمية × سعر الوحدة
        $data['amount'] = bcmul((string) $data['quantity'], (string) $data['unit_price'], 2);

        return $data;
    }
}
