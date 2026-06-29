<?php

namespace App\Http\Controllers;

use App\Models\InspectionRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class InspectionRequestController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:projects.view', only: ['index', 'show']),
            new Middleware('can:projects.create', only: ['create', 'store']),
            new Middleware('can:projects.edit', only: ['edit', 'update', 'inspect']),
            new Middleware('can:projects.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');
        $type = (string) $request->input('type', '');
        $projectId = (string) $request->input('project_id', '');

        $inspectionRequests = InspectionRequest::query()
            ->with(['project', 'creator'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('ir_number', 'like', "%{$search}%");
            }))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->when($projectId !== '', fn ($q) => $q->where('project_id', $projectId))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $today = now()->toDateString();
        $stats = [
            'pending' => InspectionRequest::where('status', 'pending')->count(),
            'overdue' => InspectionRequest::where('status', 'pending')->whereNotNull('scheduled_date')
                ->whereDate('scheduled_date', '<', $today)->count(),
            'approved' => InspectionRequest::where('status', 'approved')->count(),
        ];

        return view('inspection_requests.index', [
            'inspectionRequests' => $inspectionRequests,
            'search' => $search,
            'status' => $status,
            'type' => $type,
            'projectId' => $projectId,
            'projects' => Project::orderBy('name')->get(),
            'stats' => $stats,
        ]);
    }

    public function show(InspectionRequest $inspectionRequest): View
    {
        $inspectionRequest->load(['project', 'creator']);

        return view('inspection_requests.show', ['inspectionRequest' => $inspectionRequest]);
    }

    public function create(): View
    {
        return view('inspection_requests.form', [
            'inspectionRequest' => new InspectionRequest(['status' => 'pending', 'type' => 'general']),
            'irNumber' => $this->nextNumber(),
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['ir_number'] = $this->nextNumber();
        $data['status'] = 'pending';
        $data['created_by'] = $request->user()->id;
        $inspectionRequest = InspectionRequest::create($data);

        return redirect()->route('inspection_requests.show', $inspectionRequest)->with('success', 'تمت إضافة طلب الفحص بنجاح.');
    }

    public function edit(InspectionRequest $inspectionRequest): View
    {
        return view('inspection_requests.form', [
            'inspectionRequest' => $inspectionRequest,
            'irNumber' => $inspectionRequest->ir_number,
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, InspectionRequest $inspectionRequest): RedirectResponse
    {
        $inspectionRequest->update($this->validateData($request));

        return redirect()->route('inspection_requests.show', $inspectionRequest)->with('success', 'تم تحديث طلب الفحص.');
    }

    /** تسجيل نتيجة الفحص والمعاينة. */
    public function inspect(Request $request, InspectionRequest $inspectionRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected,closed'],
            'result' => ['nullable', 'string'],
            'inspector' => ['nullable', 'string', 'max:255'],
        ]);

        $inspectionRequest->update([
            'status' => $data['status'],
            'result' => $data['result'] ?? null,
            'inspector' => $data['inspector'] ?? null,
            'inspected_at' => now(),
        ]);

        return back()->with('success', 'تم تسجيل نتيجة الفحص.');
    }

    public function destroy(InspectionRequest $inspectionRequest): RedirectResponse
    {
        $inspectionRequest->delete();

        return back()->with('success', 'تم حذف طلب الفحص.');
    }

    private function nextNumber(): string
    {
        return app(\App\Services\DocumentNumberGenerator::class)->generate(InspectionRequest::class, 'IR');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.implode(',', array_keys(InspectionRequest::TYPES))],
            'location' => ['nullable', 'string', 'max:255'],
            'scheduled_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
