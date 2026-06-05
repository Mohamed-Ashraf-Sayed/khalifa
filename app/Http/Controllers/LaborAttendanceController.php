<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LaborAttendance;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class LaborAttendanceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:projects.view', only: ['index', 'show']),
            new Middleware('can:projects.create', only: ['create', 'store']),
            new Middleware('can:projects.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $projectId = (string) $request->input('project_id', '');
        $date = (string) $request->input('attendance_date', '');
        $from = (string) $request->input('from', '');
        $to = (string) $request->input('to', '');

        $attendances = LaborAttendance::query()
            ->with(['project', 'employee'])
            ->when($projectId !== '', fn ($q) => $q->where('project_id', $projectId))
            ->when($date !== '', fn ($q) => $q->whereDate('attendance_date', $date))
            ->when($from !== '', fn ($q) => $q->whereDate('attendance_date', '>=', $from))
            ->when($to !== '', fn ($q) => $q->whereDate('attendance_date', '<=', $to))
            ->latest('attendance_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        // ملخّص حسب نفس عوامل التصفية
        $summaryQuery = LaborAttendance::query()
            ->when($projectId !== '', fn ($q) => $q->where('project_id', $projectId))
            ->when($date !== '', fn ($q) => $q->whereDate('attendance_date', $date))
            ->when($from !== '', fn ($q) => $q->whereDate('attendance_date', '>=', $from))
            ->when($to !== '', fn ($q) => $q->whereDate('attendance_date', '<=', $to));

        $rows = (clone $summaryQuery)->get();
        $summary = [
            'present' => (int) (clone $summaryQuery)->where('present', true)->count(),
            'hours' => (float) $rows->reduce(fn ($c, $r) => bcadd($c, (string) $r->hours, 2), '0'),
            'wage' => (float) $rows->reduce(fn ($c, $r) => bcadd($c, (string) ($r->wage ?? 0), 2), '0'),
        ];

        $projects = Project::orderBy('name')->get(['id', 'name']);

        return view('labor_attendances.index', compact('attendances', 'summary', 'projects', 'projectId', 'date', 'from', 'to'));
    }

    public function show(LaborAttendance $labor_attendance): View
    {
        $labor_attendance->load(['project', 'employee', 'creator']);

        return view('labor_attendances.show', ['attendance' => $labor_attendance]);
    }

    public function create(Request $request): View
    {
        $projects = Project::orderBy('name')->get(['id', 'name']);
        $projectId = (string) $request->input('project_id', optional($projects->first())->id);
        $date = (string) $request->input('attendance_date', now()->toDateString());

        $employees = collect();
        if ($projectId !== '') {
            $project = Project::with('assignedEmployees')->find($projectId);
            $employees = $project ? $project->assignedEmployees : collect();
        }

        return view('labor_attendances.create', compact('projects', 'projectId', 'date', 'employees'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'attendance_date' => ['required', 'date'],
            'rows' => ['nullable', 'array'],
            'rows.*.employee_id' => ['nullable', 'exists:employees,id'],
            'rows.*.present' => ['nullable', 'boolean'],
            'rows.*.hours' => ['nullable', 'numeric', 'min:0'],
            'rows.*.wage' => ['nullable', 'numeric', 'min:0'],
            'laborer_name' => ['nullable', 'string', 'max:255'],
            'laborer_hours' => ['nullable', 'numeric', 'min:0'],
            'laborer_wage' => ['nullable', 'numeric', 'min:0'],
            'laborer_present' => ['nullable', 'boolean'],
        ]);

        $userId = $request->user()->id;
        $count = 0;

        foreach ($request->input('rows', []) as $row) {
            $present = (bool) ($row['present'] ?? false);
            if (! $present) {
                continue;
            }
            if (empty($row['employee_id'])) {
                continue;
            }
            LaborAttendance::create([
                'project_id' => $data['project_id'],
                'attendance_date' => $data['attendance_date'],
                'employee_id' => $row['employee_id'],
                'hours' => $row['hours'] ?? 0,
                'present' => true,
                'wage' => $row['wage'] ?? null,
                'created_by' => $userId,
            ]);
            $count++;
        }

        // عامل يدوي إضافي (ad-hoc)
        if (! empty($data['laborer_name'] ?? null)) {
            LaborAttendance::create([
                'project_id' => $data['project_id'],
                'attendance_date' => $data['attendance_date'],
                'laborer_name' => $data['laborer_name'],
                'hours' => $data['laborer_hours'] ?? 0,
                'present' => (bool) ($request->input('laborer_present', true)),
                'wage' => $data['laborer_wage'] ?? null,
                'created_by' => $userId,
            ]);
            $count++;
        }

        if ($count === 0) {
            return back()->withInput()->with('error', 'لم يتم تحديد أي عامل حاضر.');
        }

        return redirect()
            ->route('labor_attendances.index', ['project_id' => $data['project_id'], 'attendance_date' => $data['attendance_date']])
            ->with('success', "تم تسجيل حضور {$count} عامل.");
    }

    public function destroy(LaborAttendance $labor_attendance): RedirectResponse
    {
        $labor_attendance->delete();

        return back()->with('success', 'تم حذف سجل الحضور.');
    }
}
