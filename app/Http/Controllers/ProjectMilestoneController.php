<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMilestone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ProjectMilestoneController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:projects.edit', only: ['store', 'update', 'destroy']),
        ];
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $data = $this->validateData($request);
        $project->milestones()->create($data);

        return back()->with('success', 'تمت إضافة المرحلة بنجاح.');
    }

    public function update(Request $request, ProjectMilestone $projectMilestone): RedirectResponse
    {
        $projectMilestone->update($this->validateData($request));

        return back()->with('success', 'تم تحديث المرحلة.');
    }

    public function destroy(ProjectMilestone $projectMilestone): RedirectResponse
    {
        $projectMilestone->delete();

        return back()->with('success', 'تم حذف المرحلة.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'planned_start' => ['nullable', 'date'],
            'planned_end' => ['nullable', 'date'],
            'actual_start' => ['nullable', 'date'],
            'actual_end' => ['nullable', 'date'],
            'progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'status' => ['required', 'in:'.implode(',', array_keys(ProjectMilestone::STATUSES))],
            'sort' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
