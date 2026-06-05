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
        <h6 class="mb-3"><i class="fa-solid fa-circle-info ms-1" style="color:#8b7355"></i> تفاصيل اليومية</h6>
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
@endsection
