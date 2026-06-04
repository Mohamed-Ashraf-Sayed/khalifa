<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Employee;
use App\Models\Material;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ProjectController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:projects.view', only: ['index', 'show']),
            new Middleware('can:projects.create', only: ['create', 'store']),
            new Middleware('can:projects.edit', only: ['edit', 'update']),
            new Middleware('can:projects.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');

        $projects = Project::query()
            ->with('client')
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('projects.index', compact('projects', 'search', 'status'));
    }

    public function show(Project $project): View
    {
        $project->load([
            'client', 'manager', 'creator',
            'assignedEmployees',
            'materialConsumptions.material',
            'invoices',
            'contractorExtracts.contractor',
            'purchaseOrders.supplier',
            'supplierTransactions.supplier',
            'projectCosts',
            'revenues',
            'expenses',
            'contracts',
            'files',
            'partners',
        ]);

        // الملخّص المالي للمشروع (bcmath للحفاظ على الدقّة)
        $contractValue = (string) $project->contract_value;
        $revenue = (string) $project->revenues->sum('amount');
        $collected = (string) $project->revenues->sum('paid_amount');
        $revenueRemaining = bcsub($revenue, $collected, 2);

        // التكلفة الفعلية — مطابقة تماماً لـ AnalyticsController::actualCost
        $costs = (string) $project->projectCosts->sum('amount');
        $expenses = (string) $project->expenses->sum('amount');
        $extracts = (string) $project->contractorExtracts
            ->whereIn('status', ['approved', 'partial', 'paid'])
            ->sum('net_amount');
        $supplier = (string) $project->supplierTransactions->sum('net_amount');
        $actualCost = array_reduce(
            [$costs, $expenses, $extracts, $supplier],
            fn (string $carry, string $v) => bcadd($carry, $v, 2),
            '0'
        );

        $profit = bcsub($revenue, $actualCost, 2);
        $margin = bccomp($revenue, '0', 2) > 0
            ? (float) bcmul(bcdiv($profit, $revenue, 6), '100', 2)
            : 0.0;
        $costVsContract = bccomp($contractValue, '0', 2) > 0
            ? (float) bcmul(bcdiv($actualCost, $contractValue, 6), '100', 2)
            : 0.0;

        $invoicedTotal = (string) $project->invoices
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');
        $invoicePaid = (string) $project->invoices->sum('paid_amount');

        $summary = [
            'contractValue' => $contractValue,
            'revenue' => $revenue,
            'collected' => $collected,
            'revenueRemaining' => $revenueRemaining,
            'actualCost' => $actualCost,
            'profit' => $profit,
            'margin' => $margin,
            'costVsContract' => $costVsContract,
            'invoicedTotal' => $invoicedTotal,
            'invoicePaid' => $invoicePaid,
        ];

        return view('projects.show', [
            'project' => $project,
            'summary' => $summary,
            'employees' => Employee::where('is_active', true)->orderBy('name')->get(),
            'materials' => Material::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('projects.form', [
            'project' => new Project(['status' => 'pending', 'project_type' => 'building']),
            'clients' => Client::orderBy('name')->get(),
            'managers' => User::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        Project::create($data);

        return redirect()->route('projects.index')->with('success', 'تمت إضافة المشروع بنجاح.');
    }

    public function edit(Project $project): View
    {
        return view('projects.form', [
            'project' => $project,
            'clients' => Client::orderBy('name')->get(),
            'managers' => User::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $project->update($this->validateData($request));

        return redirect()->route('projects.index')->with('success', 'تم تحديث المشروع.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return back()->with('success', 'تم حذف المشروع.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'client_id' => ['required', 'exists:clients,id'],
            'project_type' => ['required', 'in:'.implode(',', array_keys(Project::TYPES))],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'contract_value' => ['required', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:'.implode(',', array_keys(Project::STATUSES))],
            'manager_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
