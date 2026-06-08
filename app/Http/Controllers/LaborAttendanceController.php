<?php

namespace App\Http\Controllers;

use App\Models\LaborAttendance;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LaborAttendanceController extends Controller implements HasMiddleware
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
        $date = (string) $request->input('attendance_date', '');
        $from = (string) $request->input('from', '');
        $to = (string) $request->input('to', '');

        $filters = fn ($q) => $q
            ->when($projectId !== '', fn ($q) => $q->where('project_id', $projectId))
            ->when($date !== '', fn ($q) => $q->whereDate('attendance_date', $date))
            ->when($from !== '', fn ($q) => $q->whereDate('attendance_date', '>=', $from))
            ->when($to !== '', fn ($q) => $q->whereDate('attendance_date', '<=', $to));

        $attendances = LaborAttendance::query()
            ->with(['project', 'employee'])
            ->tap($filters)
            ->latest('attendance_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        // ملخّص حسب نفس عوامل التصفية
        $rows = LaborAttendance::query()->tap($filters)->get();
        $summary = [
            'present' => $rows->where('present', true)->count(),
            'absent' => $rows->where('present', false)->count(),
            'hours' => (float) $rows->reduce(fn ($c, $r) => bcadd($c, (string) $r->hours, 2), '0'),
            'wage' => (float) $rows->where('present', true)->reduce(fn ($c, $r) => bcadd($c, (string) ($r->wage ?? 0), 2), '0'),
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
        $existing = collect();
        if ($projectId !== '') {
            $project = Project::with('assignedEmployees')->find($projectId);
            $employees = $project ? $project->assignedEmployees : collect();

            // سجلات اليوم الحالية لنفس المشروع/التاريخ → لإعادة فتح الكشف كمحرّر
            $existing = LaborAttendance::where('project_id', $projectId)
                ->whereDate('attendance_date', $date)
                ->get();
        }

        // مفاتيح الموظفين المسجّلين مسبقاً + العمال اليدويون المسجّلون
        $existingByEmployee = $existing->whereNotNull('employee_id')->keyBy('employee_id');
        $existingLaborers = $existing->whereNull('employee_id')->values();

        return view('labor_attendances.create', compact(
            'projects', 'projectId', 'date', 'employees', 'existingByEmployee', 'existingLaborers'
        ));
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
        $present = 0;
        $absent = 0;

        DB::transaction(function () use ($request, $data, $userId, &$present, &$absent) {
            // تسجيل/تحديث الموظفين المسندين (حاضر وغائب) بدون تكرار لنفس اليوم
            foreach ($request->input('rows', []) as $row) {
                if (empty($row['employee_id'])) {
                    continue;
                }
                $isPresent = (bool) ($row['present'] ?? false);

                $this->upsertRecord(
                    fn ($q) => $q->where('employee_id', $row['employee_id']),
                    ['employee_id' => $row['employee_id']],
                    $data, $isPresent, $row['hours'] ?? 0, $row['wage'] ?? null, $userId
                );

                $isPresent ? $present++ : $absent++;
            }

            // عامل يدوي إضافي (ad-hoc) — تحديث إن وُجد بنفس الاسم/اليوم وإلا إنشاء
            if (! empty($data['laborer_name'] ?? null)) {
                $isPresent = (bool) $request->input('laborer_present', true);

                $this->upsertRecord(
                    fn ($q) => $q->whereNull('employee_id')->where('laborer_name', $data['laborer_name']),
                    ['laborer_name' => $data['laborer_name']],
                    $data, $isPresent, $data['laborer_hours'] ?? 0, $data['laborer_wage'] ?? null, $userId
                );

                $isPresent ? $present++ : $absent++;
            }
        });

        if ($present === 0 && $absent === 0) {
            return back()->withInput()->with('error', 'لا يوجد عمال في الكشف لحفظهم.');
        }

        return redirect()
            ->route('labor_attendances.index', ['project_id' => $data['project_id'], 'attendance_date' => $data['attendance_date']])
            ->with('success', "تم حفظ الكشف: {$present} حاضر، {$absent} غائب.");
    }

    /**
     * يحدّث سجل حضور موجود لنفس المشروع/اليوم/العامل أو يُنشئه — مطابقة بـ whereDate
     * كي تعمل على SQLite وMySQL معاً (عمود التاريخ قد يُخزَّن مع جزء وقت 00:00:00).
     */
    private function upsertRecord(callable $match, array $identity, array $data, bool $isPresent, $hours, $wage, int $userId): void
    {
        $record = LaborAttendance::query()
            ->where('project_id', $data['project_id'])
            ->whereDate('attendance_date', $data['attendance_date'])
            ->tap($match)
            ->first();

        $attrs = [
            'hours' => $isPresent ? ($hours ?? 0) : 0,
            'present' => $isPresent,
            'wage' => $isPresent ? ($wage ?? null) : null,
            'created_by' => $userId,
        ];

        if ($record) {
            $record->update($attrs);

            return;
        }

        LaborAttendance::create(array_merge($identity, $attrs, [
            'project_id' => $data['project_id'],
            'attendance_date' => $data['attendance_date'],
        ]));
    }

    public function edit(LaborAttendance $labor_attendance): View
    {
        $labor_attendance->load(['project', 'employee']);

        return view('labor_attendances.edit', ['attendance' => $labor_attendance]);
    }

    public function update(Request $request, LaborAttendance $labor_attendance): RedirectResponse
    {
        $data = $request->validate([
            'present' => ['nullable', 'boolean'],
            'hours' => ['nullable', 'numeric', 'min:0'],
            'wage' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $present = (bool) ($data['present'] ?? false);
        $labor_attendance->update([
            'present' => $present,
            'hours' => $present ? ($data['hours'] ?? 0) : 0,
            'wage' => $present ? ($data['wage'] ?? null) : null,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('labor_attendances.show', $labor_attendance)
            ->with('success', 'تم تحديث سجل الحضور.');
    }

    public function destroy(LaborAttendance $labor_attendance): RedirectResponse
    {
        $labor_attendance->delete();

        return back()->with('success', 'تم حذف سجل الحضور.');
    }
}
