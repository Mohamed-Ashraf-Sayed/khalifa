<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Rfi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class RfiController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:projects.view', only: ['index', 'show']),
            new Middleware('can:projects.create', only: ['create', 'store']),
            new Middleware('can:projects.edit', only: ['edit', 'update', 'answer', 'close']),
            new Middleware('can:projects.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');
        $projectId = (string) $request->input('project_id', '');

        $rfis = Rfi::query()
            ->with(['project', 'creator'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('rfi_number', 'like', "%{$search}%");
            }))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($projectId !== '', fn ($q) => $q->where('project_id', $projectId))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $today = now()->toDateString();
        $stats = [
            'open' => Rfi::where('status', 'open')->count(),
            'overdue' => Rfi::where('status', 'open')->whereNotNull('due_date')
                ->whereDate('due_date', '<', $today)->count(),
            'answered' => Rfi::where('status', 'answered')->count(),
        ];

        return view('rfis.index', [
            'rfis' => $rfis,
            'search' => $search,
            'status' => $status,
            'projectId' => $projectId,
            'projects' => Project::orderBy('name')->get(),
            'stats' => $stats,
        ]);
    }

    public function show(Rfi $rfi): View
    {
        $rfi->load(['project', 'creator']);

        return view('rfis.show', ['rfi' => $rfi]);
    }

    public function create(): View
    {
        return view('rfis.form', [
            'rfi' => new Rfi(['status' => 'open']),
            'rfiNumber' => $this->nextNumber(),
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['rfi_number'] = $this->nextNumber();
        $data['status'] = 'open';
        $data['created_by'] = $request->user()->id;
        $rfi = Rfi::create($data);

        return redirect()->route('rfis.show', $rfi)->with('success', 'تمت إضافة طلب الاستفسار بنجاح.');
    }

    public function edit(Rfi $rfi): View
    {
        return view('rfis.form', [
            'rfi' => $rfi,
            'rfiNumber' => $rfi->rfi_number,
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Rfi $rfi): RedirectResponse
    {
        $rfi->update($this->validateData($request));

        return redirect()->route('rfis.show', $rfi)->with('success', 'تم تحديث طلب الاستفسار.');
    }

    /** تسجيل الإجابة على طلب الاستفسار. */
    public function answer(Request $request, Rfi $rfi): RedirectResponse
    {
        $data = $request->validate([
            'answer' => ['required', 'string'],
        ]);

        $rfi->update([
            'answer' => $data['answer'],
            'status' => 'answered',
            'answered_at' => now(),
        ]);

        return back()->with('success', 'تم تسجيل الإجابة على طلب الاستفسار.');
    }

    /** إغلاق طلب الاستفسار. */
    public function close(Rfi $rfi): RedirectResponse
    {
        $rfi->update(['status' => 'closed']);

        return back()->with('success', 'تم إغلاق طلب الاستفسار.');
    }

    public function destroy(Rfi $rfi): RedirectResponse
    {
        $rfi->delete();

        return back()->with('success', 'تم حذف طلب الاستفسار.');
    }

    private function nextNumber(): string
    {
        $year = now()->format('Y');
        $count = Rfi::whereYear('created_at', $year)->count() + 1;

        return sprintf('RFI-%s-%04d', $year, $count);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'subject' => ['required', 'string', 'max:255'],
            'question' => ['required', 'string'],
            'raised_to' => ['nullable', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
        ]);
    }
}
