<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Snag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SnagController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:projects.view', only: ['index', 'show']),
            new Middleware('can:projects.create', only: ['create', 'store']),
            new Middleware('can:projects.edit', only: ['edit', 'update', 'close']),
            new Middleware('can:projects.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $projectId = (string) $request->input('project_id', '');
        $status = (string) $request->input('status', '');
        $priority = (string) $request->input('priority', '');

        $snags = Snag::query()
            ->with(['project', 'assignedEmployee'])
            ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->when($projectId !== '', fn ($q) => $q->where('project_id', $projectId))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($priority !== '', fn ($q) => $q->where('priority', $priority))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'open' => Snag::where('status', 'open')->count(),
            'high_open' => Snag::where('status', '!=', 'closed')->where('priority', 'high')->count(),
            'in_progress' => Snag::where('status', 'in_progress')->count(),
            'closed' => Snag::where('status', 'closed')->count(),
        ];

        $projects = Project::orderBy('name')->get();

        return view('snags.index', compact('snags', 'search', 'projectId', 'status', 'priority', 'stats', 'projects'));
    }

    public function show(Snag $snag): View
    {
        $snag->load(['project', 'assignedEmployee', 'creator']);

        return view('snags.show', ['snag' => $snag]);
    }

    public function create(): View
    {
        return view('snags.form', [
            'snag' => new Snag(['priority' => 'medium', 'status' => 'open']),
            'projects' => Project::orderBy('name')->get(),
            'employees' => Employee::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        if ($data['status'] === 'closed') {
            $data['closed_at'] = now();
        }
        Snag::create($data);

        return redirect()->route('snags.index')->with('success', 'تمت إضافة الملاحظة بنجاح.');
    }

    public function edit(Snag $snag): View
    {
        return view('snags.form', [
            'snag' => $snag,
            'projects' => Project::orderBy('name')->get(),
            'employees' => Employee::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Snag $snag): RedirectResponse
    {
        $data = $this->validateData($request);
        if ($data['status'] === 'closed' && ! $snag->closed_at) {
            $data['closed_at'] = now();
        }
        if ($data['status'] !== 'closed') {
            $data['closed_at'] = null;
        }
        $snag->update($data);

        return redirect()->route('snags.index')->with('success', 'تم تحديث الملاحظة.');
    }

    /** إغلاق الملاحظة بعد معالجتها. */
    public function close(Snag $snag): RedirectResponse
    {
        $snag->update(['status' => 'closed', 'closed_at' => now()]);

        return back()->with('success', 'تم إغلاق الملاحظة.');
    }

    public function destroy(Snag $snag): RedirectResponse
    {
        $snag->delete();

        return back()->with('success', 'تم حذف الملاحظة.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'project_id' => ['required', Rule::exists('projects', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', 'in:'.implode(',', array_keys(Snag::PRIORITIES))],
            'status' => ['required', 'in:'.implode(',', array_keys(Snag::STATUSES))],
            'assigned_employee_id' => ['nullable', Rule::exists('employees', 'id')],
            'responsible' => ['nullable', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
        ]);
    }
}
