@extends('layouts.app')

@section('title', 'بيانات المشروع')

@section('content')
    @php($badge = match($project->status) {
        'completed' => 'success', 'in_progress' => 'primary',
        'on_hold' => 'warning', 'cancelled' => 'danger', default => 'secondary' })

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">{{ $project->name }}</h5>
        <div class="d-flex gap-2">
            @can('projects.edit')
                <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
            @endcan
            <a href="{{ route('projects.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">اسم المشروع</div><div class="fw-semibold">{{ $project->name }}</div></div>
                <div class="col-md-4"><div class="text-muted small">العميل</div><div>{{ $project->client?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">النوع</div><div>{{ \App\Models\Project::TYPES[$project->project_type] ?? $project->project_type }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الحالة</div><div><span class="badge text-bg-{{ $badge }}">{{ \App\Models\Project::STATUSES[$project->status] ?? $project->status }}</span></div></div>
                <div class="col-md-4"><div class="text-muted small">قيمة العقد</div><div class="fw-semibold">{{ number_format($project->contract_value, 2) }} ج</div></div>
                <div class="col-md-4"><div class="text-muted small">مدير المشروع</div><div>{{ $project->manager?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الموقع</div><div>{{ $project->location ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ البداية</div><div>{{ $project->start_date?->format('Y-m-d') ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ النهاية</div><div>{{ $project->end_date?->format('Y-m-d') ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ النهاية الفعلي</div><div>{{ $project->actual_end_date?->format('Y-m-d') ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">أنشئ بواسطة</div><div>{{ $project->creator?->name ?? '—' }}</div></div>
                @if ($project->description)<div class="col-12"><div class="text-muted small">الوصف</div><div>{{ $project->description }}</div></div>@endif
                @if ($project->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $project->notes }}</div></div>@endif
            </div>
        </div>
    </div>

    @if ($project->client)
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3">بيانات العميل</h6>
                <div class="row g-3">
                    <div class="col-md-4"><div class="text-muted small">الاسم</div><div class="fw-semibold">{{ $project->client->name }}</div></div>
                    <div class="col-md-4"><div class="text-muted small">الشركة</div><div>{{ $project->client->company_name ?: '—' }}</div></div>
                    <div class="col-md-4"><div class="text-muted small">الهاتف</div><div dir="ltr" class="text-end">{{ $project->client->phone ?: '—' }}</div></div>
                </div>
            </div>
        </div>
    @endif
@endsection
