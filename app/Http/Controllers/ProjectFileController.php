<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectFileController extends Controller implements HasMiddleware
{
    /** الامتدادات المسموح بها فقط — يمنع رفع ملفات تنفيذية. */
    private const ALLOWED = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'webp'];

    public static function middleware(): array
    {
        return [
            new Middleware('can:projects.view', only: ['index', 'download']),
            new Middleware('can:projects.edit', only: ['store', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $files = ProjectFile::query()
            ->with(['project', 'uploader'])
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->integer('project_id')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('project_files.index', [
            'files' => $files,
            'projects' => Project::orderBy('name')->get(),
            'selectedProject' => (int) $request->input('project_id', 0),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'file' => ['required', 'file', 'max:10240', 'mimes:'.implode(',', self::ALLOWED)],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $request->file('file');

        // تخزين على قرص 'local' (storage/app/private) — بره جذر الويب، مايتنفّذش كـPHP
        $path = $file->store('project_files', 'local');

        ProjectFile::create([
            'project_id' => $request->integer('project_id'),
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'description' => $request->input('description'),
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'تم رفع الملف بأمان.');
    }

    public function download(ProjectFile $project_file): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($project_file->stored_path), 404);

        // التنزيل عبر الـcontroller (محمي بالصلاحيات) — الملف نفسه غير قابل للوصول المباشر
        return Storage::disk('local')->download($project_file->stored_path, $project_file->original_name);
    }

    public function destroy(ProjectFile $project_file): RedirectResponse
    {
        Storage::disk('local')->delete($project_file->stored_path);
        $project_file->delete();

        return back()->with('success', 'تم حذف الملف.');
    }
}
