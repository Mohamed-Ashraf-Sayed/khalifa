<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectEmployee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;

class ProjectEmployeeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:projects.edit')];
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => [
                'required',
                'exists:employees,id',
                Rule::unique('project_employees', 'employee_id')->where('project_id', $project->id),
            ],
            'role' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
        ]);

        $project->projectEmployees()->create($data);

        return back()->with('success', 'تمت إضافة الموظف للمشروع.');
    }

    public function destroy(ProjectEmployee $project_employee): RedirectResponse
    {
        $project_employee->delete();

        return back()->with('success', 'تم حذف الموظف من المشروع.');
    }
}
