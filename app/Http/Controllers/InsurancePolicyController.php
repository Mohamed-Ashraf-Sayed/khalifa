<?php

namespace App\Http\Controllers;

use App\Models\InsurancePolicy;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InsurancePolicyController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:guarantees.view', only: ['index', 'show']),
            new Middleware('can:guarantees.create', only: ['create', 'store']),
            new Middleware('can:guarantees.edit', only: ['edit', 'update']),
            new Middleware('can:guarantees.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');

        $policies = InsurancePolicy::query()
            ->with(['project'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('policy_number', 'like', "%{$search}%")
                    ->orWhere('provider', 'like', "%{$search}%");
            }))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $active = InsurancePolicy::where('status', 'active')->get();
        $stats = [
            'count' => InsurancePolicy::count(),
            'coverage' => (float) $active->reduce(fn ($c, $p) => bcadd($c, (string) $p->coverage_amount, 2), '0'),
            'expiring' => $active->filter->isExpiringSoon(30)->count(),
        ];

        return view('insurance_policies.index', compact('policies', 'search', 'status', 'stats'));
    }

    public function show(InsurancePolicy $insurancePolicy): View
    {
        $insurancePolicy->load(['project', 'creator']);

        return view('insurance_policies.show', ['policy' => $insurancePolicy]);
    }

    public function create(): View
    {
        return view('insurance_policies.form', [
            'policy' => new InsurancePolicy(['status' => 'active', 'start_date' => now()]),
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['policy_number'] = $this->nextNumber();
        $data['created_by'] = $request->user()->id;
        InsurancePolicy::create($data);

        return redirect()->route('insurance.index')->with('success', 'تمت إضافة وثيقة التأمين بنجاح.');
    }

    public function edit(InsurancePolicy $insurancePolicy): View
    {
        return view('insurance_policies.form', [
            'policy' => $insurancePolicy,
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, InsurancePolicy $insurancePolicy): RedirectResponse
    {
        $insurancePolicy->update($this->validateData($request, $insurancePolicy));

        return redirect()->route('insurance.index')->with('success', 'تم تحديث وثيقة التأمين.');
    }

    public function destroy(InsurancePolicy $insurancePolicy): RedirectResponse
    {
        $insurancePolicy->delete();

        return back()->with('success', 'تم حذف وثيقة التأمين.');
    }

    private function nextNumber(): string
    {
        $year = now()->format('Y');
        $count = InsurancePolicy::whereYear('created_at', $year)->count() + 1;

        return sprintf('INS-%s-%04d', $year, $count);
    }

    private function validateData(Request $request, ?InsurancePolicy $insurancePolicy = null): array
    {
        return $request->validate([
            'type' => ['required', 'in:'.implode(',', array_keys(InsurancePolicy::TYPES))],
            'provider' => ['required', 'string', 'max:255'],
            'coverage_amount' => ['required', 'numeric', 'min:0'],
            'premium' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'expiry_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:'.implode(',', array_keys(InsurancePolicy::STATUSES))],
            'project_id' => ['nullable', Rule::exists('projects', 'id')],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
