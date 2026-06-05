<?php

namespace App\Http\Controllers;

use App\Models\ChangeOrder;
use App\Models\Project;
use App\Models\ProjectContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChangeOrderController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:contracts.view', only: ['index', 'show']),
            new Middleware('can:contracts.create', only: ['create', 'store']),
            new Middleware('can:contracts.edit', only: ['edit', 'update', 'approve', 'reject']),
            new Middleware('can:contracts.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');
        $projectId = (string) $request->input('project_id', '');

        $changeOrders = ChangeOrder::query()
            ->with(['project', 'contract'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('co_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            }))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($projectId !== '', fn ($q) => $q->where('project_id', $projectId))
            ->latest('request_date')
            ->paginate(15)
            ->withQueryString();

        $approved = ChangeOrder::where('status', 'approved')->get();
        $additions = $approved->where('change_type', 'addition')
            ->reduce(fn ($c, $o) => bcadd($c, (string) $o->amount, 2), '0');
        $deductions = $approved->where('change_type', 'deduction')
            ->reduce(fn ($c, $o) => bcadd($c, (string) $o->amount, 2), '0');

        $stats = [
            'count' => ChangeOrder::count(),
            'additions' => (float) $additions,
            'deductions' => (float) $deductions,
            'net' => (float) bcsub($additions, $deductions, 2),
        ];

        return view('change_orders.index', [
            'changeOrders' => $changeOrders,
            'search' => $search,
            'status' => $status,
            'projectId' => $projectId,
            'stats' => $stats,
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function show(ChangeOrder $changeOrder): View
    {
        $changeOrder->load(['project', 'contract', 'creator', 'approver']);

        return view('change_orders.show', ['changeOrder' => $changeOrder]);
    }

    public function create(): View
    {
        return view('change_orders.form', $this->formData(
            new ChangeOrder([
                'change_type' => 'addition',
                'status' => 'pending',
                'request_date' => now()->toDateString(),
            ])
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['co_number'] = $this->nextNumber();
        $data['created_by'] = $request->user()->id;
        ChangeOrder::create($data);

        return redirect()->route('change_orders.index')->with('success', 'تمت إضافة أمر التغيير بنجاح.');
    }

    public function edit(ChangeOrder $changeOrder): View
    {
        return view('change_orders.form', $this->formData($changeOrder));
    }

    public function update(Request $request, ChangeOrder $changeOrder): RedirectResponse
    {
        $changeOrder->update($this->validateData($request));

        return redirect()->route('change_orders.index')->with('success', 'تم تحديث أمر التغيير.');
    }

    /** اعتماد أمر التغيير (معلّق -> معتمد). */
    public function approve(Request $request, ChangeOrder $changeOrder): RedirectResponse
    {
        if ($changeOrder->status !== 'pending') {
            return back()->with('error', 'لا يمكن اعتماد أمر التغيير في حالته الحالية.');
        }

        $changeOrder->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'تم اعتماد أمر التغيير.');
    }

    /** رفض أمر التغيير (معلّق -> مرفوض). */
    public function reject(ChangeOrder $changeOrder): RedirectResponse
    {
        if ($changeOrder->status !== 'pending') {
            return back()->with('error', 'لا يمكن رفض أمر التغيير في حالته الحالية.');
        }

        $changeOrder->update(['status' => 'rejected']);

        return back()->with('success', 'تم رفض أمر التغيير.');
    }

    public function destroy(ChangeOrder $changeOrder): RedirectResponse
    {
        $changeOrder->delete();

        return back()->with('success', 'تم حذف أمر التغيير.');
    }

    private function nextNumber(): string
    {
        $year = now()->format('Y');
        $count = ChangeOrder::whereYear('created_at', $year)->count() + 1;

        return sprintf('CO-%s-%04d', $year, $count);
    }

    private function formData(ChangeOrder $changeOrder): array
    {
        return [
            'changeOrder' => $changeOrder,
            'coNumber' => $changeOrder->exists ? $changeOrder->co_number : $this->nextNumber(),
            'projects' => Project::orderBy('name')->get(),
            'contracts' => ProjectContract::when($changeOrder->project_id, fn ($q) => $q->where('project_id', $changeOrder->project_id))
                ->orderBy('contract_number')->get(),
        ];
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'contract_id' => ['nullable', Rule::exists('project_contracts', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'change_type' => ['required', 'in:'.implode(',', array_keys(ChangeOrder::TYPES))],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:'.implode(',', array_keys(ChangeOrder::STATUSES))],
            'request_date' => ['required', 'date'],
        ]);
    }
}
