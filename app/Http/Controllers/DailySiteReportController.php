<?php

namespace App\Http\Controllers;

use App\Models\DailySiteReport;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class DailySiteReportController extends Controller implements HasMiddleware
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
        $projectId = (string) $request->input('project_id', '');
        $from = (string) $request->input('from', '');
        $to = (string) $request->input('to', '');

        $reports = DailySiteReport::query()
            ->with('project')
            ->when($projectId !== '', fn ($q) => $q->where('project_id', $projectId))
            ->when($from !== '', fn ($q) => $q->whereDate('report_date', '>=', $from))
            ->when($to !== '', fn ($q) => $q->whereDate('report_date', '<=', $to))
            ->orderByDesc('report_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $projects = Project::orderBy('name')->get(['id', 'name']);

        return view('daily_site_reports.index', compact('reports', 'projects', 'projectId', 'from', 'to'));
    }

    public function show(DailySiteReport $dailySiteReport): View
    {
        $dailySiteReport->load('project', 'creator');

        return view('daily_site_reports.show', ['report' => $dailySiteReport]);
    }

    public function create(): View
    {
        return view('daily_site_reports.form', [
            'report' => new DailySiteReport(['report_date' => now()->toDateString()]),
            'projects' => Project::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        DailySiteReport::create($data);

        return redirect()->route('daily_site_reports.index')->with('success', 'تم تسجيل يومية الموقع بنجاح.');
    }

    public function edit(DailySiteReport $dailySiteReport): View
    {
        return view('daily_site_reports.form', [
            'report' => $dailySiteReport,
            'projects' => Project::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, DailySiteReport $dailySiteReport): RedirectResponse
    {
        $dailySiteReport->update($this->validateData($request));

        return redirect()->route('daily_site_reports.index')->with('success', 'تم تحديث يومية الموقع.');
    }

    public function destroy(DailySiteReport $dailySiteReport): RedirectResponse
    {
        $dailySiteReport->delete();

        return back()->with('success', 'تم حذف يومية الموقع.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'report_date' => ['required', 'date'],
            'weather' => ['nullable', 'string', 'max:255'],
            'work_done' => ['nullable', 'string'],
            'labor_count' => ['required', 'integer', 'min:0'],
            'equipment_notes' => ['nullable', 'string'],
            'progress_notes' => ['nullable', 'string'],
            'incidents' => ['nullable', 'string'],
        ]);
    }
}
