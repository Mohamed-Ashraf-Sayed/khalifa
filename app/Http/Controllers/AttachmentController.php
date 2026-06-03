<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    /** الامتدادات المسموح بها فقط — يمنع رفع ملفات تنفيذية. */
    private const ALLOWED_EXT = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'docx', 'xlsx'];

    /**
     * الموديلات المسموح إرفاق ملفات عليها → صلاحية العرض/التعديل لكل منها.
     * المفتاح: اسم الكلاس الكامل، القيمة: صلاحية الـedit و الـview.
     */
    private const ALLOWED_MODELS = [
        \App\Models\Invoice::class => ['edit' => 'invoices.edit', 'view' => 'invoices.view'],
        \App\Models\Expense::class => ['edit' => 'expenses.edit', 'view' => 'expenses.view'],
        \App\Models\ContractorExtract::class => ['edit' => 'contractors.edit', 'view' => 'contractors.view'],
        \App\Models\PurchaseOrder::class => ['edit' => 'purchase_orders.edit', 'view' => 'purchase_orders.view'],
        \App\Models\Project::class => ['edit' => 'projects.edit', 'view' => 'projects.view'],
        \App\Models\Supplier::class => ['edit' => 'suppliers.edit', 'view' => 'suppliers.view'],
        \App\Models\Contractor::class => ['edit' => 'contractors.edit', 'view' => 'contractors.view'],
    ];

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'attachable_type' => ['required', 'string', Rule::in(array_keys(self::ALLOWED_MODELS))],
            'attachable_id' => ['required', 'integer'],
            'file' => ['required', 'file', 'max:8192', 'mimes:'.implode(',', self::ALLOWED_EXT)],
        ]);

        // تأكد أن السجل الأب موجود فعلاً + بوابة على صلاحية تعديل الكيان الأب
        $parent = $data['attachable_type']::findOrFail($data['attachable_id']);
        Gate::authorize(self::ALLOWED_MODELS[$data['attachable_type']]['edit']);

        $file = $request->file('file');

        // تخزين على قرص 'local' (storage/app/private) — بره جذر الويب، مايتنفّذش كـPHP
        $path = $file->store('attachments', 'local');

        Attachment::create([
            'attachable_type' => $data['attachable_type'],
            'attachable_id' => $parent->getKey(),
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'تم رفع المرفق بأمان.');
    }

    public function download(Attachment $attachment): StreamedResponse
    {
        // بوابة على صلاحية عرض الكيان الأب إن كان ضمن القائمة البيضاء
        if (isset(self::ALLOWED_MODELS[$attachment->attachable_type])) {
            Gate::authorize(self::ALLOWED_MODELS[$attachment->attachable_type]['view']);
        }

        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        // التنزيل عبر الـcontroller (محمي بالصلاحيات) — الملف نفسه غير قابل للوصول المباشر
        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    public function destroy(Attachment $attachment): RedirectResponse
    {
        if (isset(self::ALLOWED_MODELS[$attachment->attachable_type])) {
            Gate::authorize(self::ALLOWED_MODELS[$attachment->attachable_type]['edit']);
        }

        Storage::disk('local')->delete($attachment->path);
        $attachment->delete();

        return back()->with('success', 'تم حذف المرفق.');
    }
}
