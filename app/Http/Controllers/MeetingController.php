<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class MeetingController extends Controller implements HasMiddleware
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
        $search = trim((string) $request->input('search', ''));
        $projectId = (string) $request->input('project_id', '');
        $from = (string) $request->input('from', '');
        $to = (string) $request->input('to', '');

        $meetings = Meeting::query()
            ->with(['project', 'creator'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('meeting_number', 'like', "%{$search}%");
            }))
            ->when($projectId !== '', fn ($q) => $q->where('project_id', $projectId))
            ->when($from !== '', fn ($q) => $q->whereDate('meeting_date', '>=', $from))
            ->when($to !== '', fn ($q) => $q->whereDate('meeting_date', '<=', $to))
            ->orderByDesc('meeting_date')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => Meeting::count(),
            'this_month' => Meeting::whereYear('meeting_date', now()->year)
                ->whereMonth('meeting_date', now()->month)->count(),
        ];

        return view('meetings.index', [
            'meetings' => $meetings,
            'search' => $search,
            'projectId' => $projectId,
            'from' => $from,
            'to' => $to,
            'projects' => Project::orderBy('name')->get(),
            'stats' => $stats,
        ]);
    }

    public function show(Meeting $meeting): View
    {
        $meeting->load(['project', 'creator']);

        return view('meetings.show', ['meeting' => $meeting]);
    }

    public function create(): View
    {
        return view('meetings.form', [
            'meeting' => new Meeting(),
            'meetingNumber' => Meeting::nextNumber(),
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['meeting_number'] = Meeting::nextNumber();
        $data['created_by'] = $request->user()->id;
        $meeting = Meeting::create($data);

        return redirect()->route('meetings.show', $meeting)->with('success', 'تمت إضافة محضر الاجتماع بنجاح.');
    }

    public function edit(Meeting $meeting): View
    {
        return view('meetings.form', [
            'meeting' => $meeting,
            'meetingNumber' => $meeting->meeting_number,
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Meeting $meeting): RedirectResponse
    {
        $meeting->update($this->validateData($request));

        return redirect()->route('meetings.show', $meeting)->with('success', 'تم تحديث محضر الاجتماع.');
    }

    public function destroy(Meeting $meeting): RedirectResponse
    {
        $meeting->delete();

        return back()->with('success', 'تم حذف محضر الاجتماع.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'project_id' => ['nullable', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'meeting_date' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'attendees' => ['nullable', 'string'],
            'agenda' => ['nullable', 'string'],
            'decisions' => ['nullable', 'string'],
            'action_items' => ['nullable', 'string'],
            'next_meeting_date' => ['nullable', 'date'],
        ]);
    }
}
