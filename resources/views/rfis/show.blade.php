@extends('layouts.app')

@section('title', 'طلب استفسار: ' . $rfi->rfi_number)

@section('content')
    @php($badge = match($rfi->status) { 'open'=>'warning','answered'=>'success','closed'=>'secondary', default=>'secondary' })

    <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
        @can('projects.edit')
            @if ($rfi->status !== 'closed')
                <form method="POST" action="{{ route('rfis.close', $rfi) }}" class="d-inline" data-confirm="تأكيد إغلاق طلب الاستفسار؟">
                    @csrf
                    <button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-lock ms-1"></i> إغلاق</button>
                </form>
            @endif
            <a href="{{ route('rfis.edit', $rfi) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
        @endcan
        <a href="{{ route('rfis.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
    </div>

    @if ($rfi->isOverdue())
        <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation ms-1"></i> طلب الاستفسار متأخر — موعد الرد المستهدف {{ $rfi->due_date->format('Y-m-d') }}.</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
                <span class="entity-avatar"><i class="fa-solid fa-circle-question"></i></span>
                <div class="flex-grow-1">
                    <div class="h5 mb-1">{{ $rfi->rfi_number }}</div>
                    <div class="text-muted small">{{ $rfi->subject }}</div>
                    <div class="mt-2">
                        <span class="badge text-bg-{{ $badge }}">{{ \App\Models\Rfi::STATUSES[$rfi->status] ?? $rfi->status }}</span>
                    </div>
                </div>
            </div></div>
        </div>
        <div class="col-lg-7">
            <div class="row g-3 h-100">
                <div class="col-6"><div class="stat-box"><div class="sl">المشروع</div><div class="sv">{{ $rfi->project?->name ?? '—' }}</div></div></div>
                <div class="col-6"><div class="stat-box"><div class="sl">موعد الرد</div><div class="sv {{ $rfi->isOverdue() ? 'text-danger' : '' }}">{{ $rfi->due_date?->format('Y-m-d') ?? '—' }}</div></div></div>
            </div>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <h6 class="mb-3"><i class="fa-solid fa-circle-info ms-1" style="color:#8b7355"></i> بيانات الطلب</h6>
        <div class="info-list">
            <div class="il"><span class="k">موجَّه إلى</span><span class="v">{{ $rfi->raised_to ?: '—' }}</span></div>
            <div class="il"><span class="k">الحالة</span><span class="v">{{ \App\Models\Rfi::STATUSES[$rfi->status] ?? $rfi->status }}</span></div>
            <div class="il"><span class="k">تاريخ الإجابة</span><span class="v">{{ $rfi->answered_at?->format('Y-m-d H:i') ?? '—' }}</span></div>
            <div class="il"><span class="k">أُنشئ بواسطة</span><span class="v">{{ $rfi->creator?->name ?? '—' }}</span></div>
        </div>
    </div></div>

    <div class="card mb-3"><div class="card-body">
        <h6 class="mb-2"><i class="fa-solid fa-question ms-1" style="color:#8b7355"></i> الاستفسار</h6>
        <p class="mb-0" style="white-space:pre-line">{{ $rfi->question }}</p>
    </div></div>

    @if ($rfi->answer)
        <div class="card mb-3 border-success"><div class="card-body">
            <h6 class="mb-2"><i class="fa-solid fa-circle-check ms-1 text-success"></i> الإجابة</h6>
            <p class="mb-0" style="white-space:pre-line">{{ $rfi->answer }}</p>
        </div></div>
    @endif

    @can('projects.edit')
        @if ($rfi->status === 'open')
            <div class="card mb-3"><div class="card-body">
                <h6 class="mb-3"><i class="fa-solid fa-reply ms-1" style="color:#8b7355"></i> تسجيل الإجابة</h6>
                <form method="POST" action="{{ route('rfis.answer', $rfi) }}">
                    @csrf
                    <textarea name="answer" rows="4" class="form-control mb-3" placeholder="اكتب الإجابة هنا..." required>{{ old('answer') }}</textarea>
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-paper-plane ms-1"></i> حفظ الإجابة</button>
                </form>
            </div></div>
        @endif
    @endcan
@endsection
