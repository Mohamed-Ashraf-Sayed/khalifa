<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class BackupController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:settings.view', only: ['index']),
            new Middleware('can:settings.edit', only: ['run', 'download', 'destroy']),
        ];
    }

    /** اسم الـ disk المستخدم لتخزين النسخ الاحتياطية. */
    private function disk(): string
    {
        return config('backup.backup.destination.disks')[0] ?? 'local';
    }

    /** اسم المجلد اللي بتتخزن فيه النسخ (اسم التطبيق في إعدادات الباك أب). */
    private function backupFolder(): string
    {
        return config('backup.backup.name', config('app.name', 'laravel-backup'));
    }

    public function index(): View
    {
        $disk = Storage::disk($this->disk());
        $folder = $this->backupFolder();

        $backups = collect($disk->files($folder))
            ->filter(fn (string $path): bool => str_ends_with(strtolower($path), '.zip'))
            ->map(function (string $path) use ($disk): array {
                return [
                    'name' => basename($path),
                    'path' => $path,
                    'size' => $disk->size($path),
                    'date' => $disk->lastModified($path),
                ];
            })
            ->sortByDesc('date')
            ->values();

        return view('backup.index', compact('backups'));
    }

    public function run(Request $request): RedirectResponse
    {
        // نسخة كاملة (ملفات + قاعدة) إذا طُلب ذلك، وإلا قاعدة البيانات فقط.
        $full = $request->boolean('full');

        try {
            Artisan::call('backup:run', $full ? [] : ['--only-db' => true]);
            $output = trim(Artisan::output());

            $msg = $full
                ? 'تم إنشاء نسخة احتياطية كاملة (ملفات + قاعدة) بنجاح.'
                : 'تم إنشاء نسخة احتياطية لقاعدة البيانات بنجاح.';

            return back()->with('success', $msg.($output !== '' ? ' '.$output : ''));
        } catch (\Throwable $e) {
            return back()->with('error', 'فشل إنشاء النسخة الاحتياطية: '.$e->getMessage());
        }
    }

    public function download(string $file): StreamedResponse
    {
        // basename فقط لمنع أي محاولة traversal
        $file = basename($file);

        abort_unless(str_ends_with(strtolower($file), '.zip'), 404);

        $disk = Storage::disk($this->disk());
        $path = $this->backupFolder().'/'.$file;

        abort_unless($disk->exists($path), 404);

        return $disk->download($path, $file);
    }

    public function destroy(string $file): RedirectResponse
    {
        // basename فقط لمنع أي محاولة traversal
        $file = basename($file);

        abort_unless(str_ends_with(strtolower($file), '.zip'), 404);

        $disk = Storage::disk($this->disk());
        $path = $this->backupFolder().'/'.$file;

        abort_unless($disk->exists($path), 404);

        $disk->delete($path);

        return back()->with('success', 'تم حذف النسخة الاحتياطية.');
    }
}
