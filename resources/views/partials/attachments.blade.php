@php
    // خريطة الموديل → صلاحية التعديل (لإظهار رفع/حذف). متطابقة مع AttachmentController.
    $attachPermMap = [
        \App\Models\Invoice::class => 'invoices.edit',
        \App\Models\Expense::class => 'expenses.edit',
        \App\Models\ContractorExtract::class => 'contractors.edit',
        \App\Models\PurchaseOrder::class => 'purchase_orders.edit',
        \App\Models\Project::class => 'projects.edit',
        \App\Models\Supplier::class => 'suppliers.edit',
        \App\Models\Contractor::class => 'contractors.edit',
    ];

    $attachType = $model::class;
    $attachEditPerm = $attachPermMap[$attachType] ?? null;
    $canManageAttachments = $attachEditPerm ? auth()->user()->can($attachEditPerm) : false;

    // استعلام مباشر بدل إضافة علاقة لكل موديل
    $attachments = \App\Models\Attachment::where('attachable_type', $attachType)
        ->where('attachable_id', $model->getKey())
        ->latest()
        ->get();
@endphp

<div class="card mt-3" id="attachments">
    <div class="card-header bg-white fw-semibold"><i class="fa-solid fa-paperclip ms-1"></i> المرفقات</div>
    <div class="card-body">
        @if ($canManageAttachments)
            <form method="POST" action="{{ route('attachments.store') }}" enctype="multipart/form-data" class="row g-2 align-items-end mb-3 border-bottom pb-3">
                @csrf
                <input type="hidden" name="attachable_type" value="{{ $attachType }}">
                <input type="hidden" name="attachable_id" value="{{ $model->getKey() }}">
                <div class="col-md-9">
                    <label class="form-label small">ملف <span class="text-muted">(PDF / صورة / Word / Excel — حتى 8 ميجا)</span></label>
                    <input type="file" name="file" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <button class="btn w-100" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-upload ms-1"></i> رفع المرفق</button>
                </div>
            </form>
        @endif

        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>الاسم</th><th>الحجم</th><th>تاريخ الرفع</th><th class="text-end"></th></tr></thead>
                <tbody>
                    @forelse ($attachments as $attachment)
                        <tr>
                            <td><i class="fa-solid fa-file ms-1 text-muted"></i> {{ $attachment->original_name }}</td>
                            <td>{{ $attachment->size ? number_format($attachment->size / 1024, 1).' ك.ب' : '—' }}</td>
                            <td>{{ $attachment->created_at?->format('Y-m-d') ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('attachments.download', $attachment) }}" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-download"></i></a>
                                @if ($canManageAttachments)
                                    <form method="POST" action="{{ route('attachments.destroy', $attachment) }}" class="d-inline" data-confirm="حذف المرفق؟">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">لا توجد مرفقات.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
