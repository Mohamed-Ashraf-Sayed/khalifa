@extends('layouts.app')

@section('title', 'يومية الموقع: ' . ($report->project?->name ?? '') . ' — ' . $report->report_date?->format('Y-m-d'))

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
        @can('projects.edit')
            <a href="{{ route('daily_site_reports.edit', $report) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
        @endcan
        <a href="{{ route('daily_site_reports.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
                <span class="entity-avatar"><i class="fa-solid fa-clipboard-list"></i></span>
                <div class="flex-grow-1">
                    <div class="h5 mb-1">{{ $report->project?->name ?? '—' }}</div>
                    <div class="text-muted small">يومية موقع · {{ $report->report_date?->format('Y-m-d') }}</div>
                    <div class="mt-2">
                        <span class="badge text-bg-info">العمالة: {{ $report->labor_count }}</span>
                        @if ($report->weather)<span class="badge text-bg-light ms-1">{{ $report->weather }}</span>@endif
                    </div>
                </div>
            </div></div>
        </div>
        <div class="col-lg-7">
            <div class="row g-3 h-100">
                <div class="col-6"><div class="stat-box"><div class="sl">عدد العمالة</div><div class="sv">{{ $report->labor_count }}</div></div></div>
                <div class="col-6"><div class="stat-box accent"><div class="sl">التاريخ</div><div class="sv">{{ $report->report_date?->format('Y-m-d') }}</div></div></div>
            </div>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <h6 class="mb-3"><i class="fa-solid fa-circle-info ms-1" style="color:#2b4c80"></i> تفاصيل اليومية</h6>
        <div class="info-list">
            <div class="il"><span class="k">المشروع</span><span class="v">{{ $report->project?->name ?? '—' }}</span></div>
            <div class="il"><span class="k">التاريخ</span><span class="v">{{ $report->report_date?->format('Y-m-d') }}</span></div>
            <div class="il"><span class="k">الطقس</span><span class="v">{{ $report->weather ?: '—' }}</span></div>
            <div class="il"><span class="k">عدد العمالة</span><span class="v">{{ $report->labor_count }}</span></div>
            <div class="il"><span class="k">سجّلها</span><span class="v">{{ $report->creator?->name ?? '—' }}</span></div>
            @if ($report->work_done)<div class="il" style="grid-column:1/-1"><span class="k">الأعمال المنفّذة</span><span class="v">{{ $report->work_done }}</span></div>@endif
            @if ($report->equipment_notes)<div class="il" style="grid-column:1/-1"><span class="k">ملاحظات المعدات</span><span class="v">{{ $report->equipment_notes }}</span></div>@endif
            @if ($report->progress_notes)<div class="il" style="grid-column:1/-1"><span class="k">ملاحظات التقدّم</span><span class="v">{{ $report->progress_notes }}</span></div>@endif
            @if ($report->incidents)<div class="il" style="grid-column:1/-1"><span class="k">الحوادث / الطوارئ</span><span class="v text-danger">{{ $report->incidents }}</span></div>@endif
        </div>
    </div></div>

    {{-- صور وملفات الموقع --}}
    <div class="card mb-3"><div class="card-body">
        <h6 class="mb-3"><i class="fa-solid fa-images ms-1" style="color:#2b4c80"></i> صور وملفات الموقع <span class="badge text-bg-secondary">{{ $report->attachments->count() }}</span></h6>
        <div class="row g-2 mb-2">
            @forelse ($report->attachments as $att)
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="border rounded p-1 h-100 d-flex flex-column" style="background:var(--bg)">
                        @if (str_starts_with($att->mime, 'image/'))
                            <a href="{{ route('attachments.download', $att) }}" target="_blank" title="{{ $att->original_name }}">
                                <img src="{{ route('attachments.download', $att) }}" alt="{{ $att->original_name }}" style="width:100%;height:90px;object-fit:cover;border-radius:6px">
                            </a>
                        @else
                            <a href="{{ route('attachments.download', $att) }}" class="d-flex align-items-center justify-content-center text-decoration-none" style="height:90px"><i class="fa-solid fa-file fa-2x" style="color:#2b4c80"></i></a>
                        @endif
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <span class="text-truncate small" style="max-width:70%" title="{{ $att->original_name }}">{{ $att->original_name }}</span>
                            @can('projects.edit')
                                <form method="POST" action="{{ route('attachments.destroy', $att) }}" data-confirm="حذف الملف؟">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm p-0 text-danger border-0 bg-transparent"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12"><div class="text-muted small">لا توجد صور أو ملفات مرفقة بعد.</div></div>
            @endforelse
        </div>
        @can('projects.edit')
            <form method="POST" action="{{ route('attachments.store') }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-center mt-2">
                @csrf
                <input type="hidden" name="attachable_type" value="App\Models\DailySiteReport">
                <input type="hidden" name="attachable_id" value="{{ $report->id }}">
                <input type="file" name="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.docx,.xlsx" class="form-control form-control-sm" style="max-width:320px" required>
                <button class="btn btn-sm" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-upload ms-1"></i> رفع</button>
                <span class="text-muted small">الحد الأقصى 8MB · صور/PDF/مستندات</span>
            </form>
        @endcan
    </div></div>
@endsection
