@extends('layouts.app')

@section('title', 'ملاحظة: ' . $snag->title)

@section('content')
    @php($statusBadge = match($snag->status) { 'open'=>'warning','in_progress'=>'info','closed'=>'success', default=>'secondary' })
    @php($priorityBadge = match($snag->priority) { 'high'=>'danger','medium'=>'warning','low'=>'secondary', default=>'secondary' })

    <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
        @can('projects.edit')
            @if ($snag->status !== 'closed')
                <form method="POST" action="{{ route('snags.close', $snag) }}" class="d-inline" onsubmit="return confirm('تأكيد إغلاق الملاحظة؟')">
                    @csrf
                    <button class="btn btn-sm btn-outline-success"><i class="fa-solid fa-circle-check ms-1"></i> إغلاق</button>
                </form>
            @endif
            <a href="{{ route('snags.edit', $snag) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
        @endcan
        <a href="{{ route('snags.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
    </div>

    @if ($snag->isOverdue())
        <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation ms-1"></i> هذه الملاحظة متأخرة — تاريخ الاستحقاق {{ $snag->due_date->format('Y-m-d') }}.</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
                <span class="entity-avatar"><i class="fa-solid fa-clipboard-check"></i></span>
                <div class="flex-grow-1">
                    <div class="h5 mb-1">{{ $snag->title }}</div>
                    <div class="text-muted small">{{ $snag->project?->name ?? '—' }}{{ $snag->location ? ' · '.$snag->location : '' }}</div>
                    <div class="mt-2 d-flex gap-2">
                        <span class="badge text-bg-{{ $statusBadge }}">{{ \App\Models\Snag::STATUSES[$snag->status] ?? $snag->status }}</span>
                        <span class="badge text-bg-{{ $priorityBadge }}">{{ \App\Models\Snag::PRIORITIES[$snag->priority] ?? $snag->priority }}</span>
                    </div>
                </div>
            </div></div>
        </div>
        <div class="col-lg-7">
            <div class="row g-3 h-100">
                <div class="col-6"><div class="stat-box accent"><div class="sl">تاريخ الاستحقاق</div><div class="sv {{ $snag->isOverdue() ? 'text-danger' : '' }}">{{ $snag->due_date?->format('Y-m-d') ?? '—' }}</div></div></div>
                <div class="col-6"><div class="stat-box"><div class="sl">تاريخ الإغلاق</div><div class="sv text-success">{{ $snag->closed_at?->format('Y-m-d') ?? '—' }}</div></div></div>
            </div>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <h6 class="mb-3"><i class="fa-solid fa-circle-info ms-1" style="color:#8b7355"></i> بيانات الملاحظة</h6>
        <div class="info-list">
            <div class="il"><span class="k">المشروع</span><span class="v">{{ $snag->project?->name ?? '—' }}</span></div>
            <div class="il"><span class="k">المكان</span><span class="v">{{ $snag->location ?: '—' }}</span></div>
            <div class="il"><span class="k">الأولوية</span><span class="v">{{ \App\Models\Snag::PRIORITIES[$snag->priority] ?? $snag->priority }}</span></div>
            <div class="il"><span class="k">الحالة</span><span class="v">{{ \App\Models\Snag::STATUSES[$snag->status] ?? $snag->status }}</span></div>
            <div class="il"><span class="k">الموظف المسؤول</span><span class="v">{{ $snag->assignedEmployee?->name ?? '—' }}</span></div>
            <div class="il"><span class="k">جهة المسؤولية</span><span class="v">{{ $snag->responsible ?: '—' }}</span></div>
            <div class="il"><span class="k">أُنشئ بواسطة</span><span class="v">{{ $snag->creator?->name ?? '—' }}</span></div>
            <div class="il"><span class="k">تاريخ الإنشاء</span><span class="v">{{ $snag->created_at?->format('Y-m-d') ?? '—' }}</span></div>
            @if ($snag->description)<div class="il" style="grid-column:1/-1"><span class="k">الوصف</span><span class="v">{{ $snag->description }}</span></div>@endif
        </div>
    </div></div>
@endsection
