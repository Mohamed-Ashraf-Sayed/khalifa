@extends('layouts.app')

@section('title', 'محضر اجتماع: ' . $meeting->meeting_number)

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
        @can('projects.edit')
            <a href="{{ route('meetings.edit', $meeting) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
        @endcan
        <a href="{{ route('meetings.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
                <span class="entity-avatar"><i class="fa-solid fa-users-rectangle"></i></span>
                <div class="flex-grow-1">
                    <div class="h5 mb-1">{{ $meeting->meeting_number }}</div>
                    <div class="text-muted small">{{ $meeting->title }}</div>
                </div>
            </div></div>
        </div>
        <div class="col-lg-7">
            <div class="row g-3 h-100">
                <div class="col-6"><div class="stat-box"><div class="sl">تاريخ الاجتماع</div><div class="sv">{{ $meeting->meeting_date?->format('Y-m-d') ?? '—' }}</div></div></div>
                <div class="col-6"><div class="stat-box"><div class="sl">الاجتماع القادم</div><div class="sv">{{ $meeting->next_meeting_date?->format('Y-m-d') ?? '—' }}</div></div></div>
            </div>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <h6 class="mb-3"><i class="fa-solid fa-circle-info ms-1" style="color:#2b4c80"></i> بيانات المحضر</h6>
        <div class="info-list">
            <div class="il"><span class="k">المشروع</span><span class="v">{{ $meeting->project?->name ?? '—' }}</span></div>
            <div class="il"><span class="k">المكان</span><span class="v">{{ $meeting->location ?: '—' }}</span></div>
            <div class="il"><span class="k">أُنشئ بواسطة</span><span class="v">{{ $meeting->creator?->name ?? '—' }}</span></div>
            <div class="il"><span class="k">تاريخ الإنشاء</span><span class="v">{{ $meeting->created_at?->format('Y-m-d') ?? '—' }}</span></div>
        </div>
    </div></div>

    @if ($meeting->attendees)
        <div class="card mb-3"><div class="card-body">
            <h6 class="mb-2"><i class="fa-solid fa-user-group ms-1" style="color:#2b4c80"></i> الحضور</h6>
            <p class="mb-0">{!! nl2br(e($meeting->attendees)) !!}</p>
        </div></div>
    @endif

    @if ($meeting->agenda)
        <div class="card mb-3"><div class="card-body">
            <h6 class="mb-2"><i class="fa-solid fa-list-check ms-1" style="color:#2b4c80"></i> جدول الأعمال</h6>
            <p class="mb-0">{!! nl2br(e($meeting->agenda)) !!}</p>
        </div></div>
    @endif

    @if ($meeting->decisions)
        <div class="card mb-3 border-success"><div class="card-body">
            <h6 class="mb-2"><i class="fa-solid fa-gavel ms-1 text-success"></i> القرارات</h6>
            <p class="mb-0">{!! nl2br(e($meeting->decisions)) !!}</p>
        </div></div>
    @endif

    @if ($meeting->action_items)
        <div class="card mb-3"><div class="card-body">
            <h6 class="mb-2"><i class="fa-solid fa-clipboard-list ms-1" style="color:#2b4c80"></i> بنود المتابعة</h6>
            <p class="mb-0">{!! nl2br(e($meeting->action_items)) !!}</p>
        </div></div>
    @endif
@endsection
