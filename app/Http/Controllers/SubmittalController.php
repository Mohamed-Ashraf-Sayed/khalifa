<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Submittal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class SubmittalController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:projects.view', only: ['index', 'show']),
            new Middleware('can:projects.create', only: ['create', 'store']),
            new Middleware('can:projects.edit', only: ['edit', 'update', 'review']),
            new Middleware('can:projects.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');
        $type = (string) $request->input('type', '');
        $projectId = (string) $request->input('project_id', '');

        $submittals = Submittal::query()
            ->with(['project', 'creator'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('submittal_number', 'like', "%{$search}%");
            }))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->when($projectId !== '', fn ($q) => $q->where('project_id', $projectId))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $today = now()->toDateString();
        $stats = [
            'submitted' => Submittal::where('status', 'submitted')->count(),
            'under_review' => Submittal::where('status', 'under_review')->count(),
            'approved' => Submittal::where('status', 'approved')->count(),
            'overdue' => Submittal::whereIn('status', ['submitted', 'under_review'])
                ->whereNotNull('due_date')->whereDate('due_date', '<', $today)->count(),
        ];

        return view('submittals.index', [
            'submittals' => $submittals,
            'search' => $search,
            'status' => $status,
            'type' => $type,
            'projectId' => $projectId,
            'projects' => Project::orderBy('name')->get(),
            'stats' => $stats,
        ]);
    }

    public function show(Submittal $submittal): View
    {
        $submittal->load(['project', 'creator']);

        return view('submittals.show', ['submittal' => $submittal]);
    }

    public function create(): View
    {
        return view('submittals.form', [
            'submittal' => new Submittal(['status' => 'submitted']),
            'submittalNumber' => $this->nextNumber(),
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['submittal_number'] = $this->nextNumber();
        $data['status'] = 'submitted';
        $data['created_by'] = $request->user()->id;
        $submittal = Submittal::create($data);

        return redirect()->route('submittals.show', $submittal)->with('success', 'تمت إضافة الاعتماد الفني بنجاح.');
    }

    public function edit(Submittal $submittal): View
    {
        return view('submittals.form', [
            'submittal' => $submittal,
            'submittalNumber' => $submittal->submittal_number,
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Submittal $submittal): RedirectResponse
    {
        $submittal->update($this->validateData($request));

        return redirect()->route('submittals.show', $submittal)->with('success', 'تم تحديث الاعتماد الفني.');
    }

    /** تسجيل نتيجة مراجعة الاستشاري للاعتماد الفني. */
    public function review(Request $request, Submittal $submittal): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,approved_as_noted,rejected,under_review'],
            'review_notes' => ['nullable', 'string'],
        ]);

        $submittal->update([
            'status' => $data['status'],
            'reviewed_at' => now(),
            'review_notes' => $data['review_notes'] ?? null,
        ]);

        return back()->with('success', 'تم تسجيل نتيجة المراجعة.');
    }

    public function destroy(Submittal $submittal): RedirectResponse
    {
        $submittal->delete();

        return back()->with('success', 'تم حذف الاعتماد الفني.');
    }

    private function nextNumber(): string
    {
        return app(\App\Services\DocumentNumberGenerator::class)->generate(Submittal::class, 'SUB');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:' . implode(',', array_keys(Submittal::TYPES))],
            'spec_section' => ['nullable', 'string', 'max:255'],
            'submitted_to' => ['nullable', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ]);
    }
}
