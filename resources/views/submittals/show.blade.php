@extends('layouts.app')

@section('title', 'اعتماد فني: ' . $submittal->submittal_number)

@section('content')
    @php($badge = match($submittal->status) {
        'submitted'=>'primary','under_review'=>'warning','approved'=>'success',
        'approved_as_noted'=>'info','rejected'=>'danger', default=>'secondary' })

    <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
        @can('projects.edit')
            <a href="{{ route('submittals.edit', $submittal) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
        @endcan
        <a href="{{ route('submittals.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
    </div>

    @if ($submittal->isOverdue())
        <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation ms-1"></i> الاعتماد الفني متأخر — موعد المراجعة المستهدف {{ $submittal->due_date->format('Y-m-d') }}.</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
                <span class="entity-avatar"><i class="fa-solid fa-stamp"></i></span>
                <div class="flex-grow-1">
                    <div class="h5 mb-1">{{ $submittal->submittal_number }}</div>
                    <div class="text-muted small">{{ $submittal->title }}</div>
                    <div class="mt-2">
                        <span class="badge text-bg-{{ $badge }}">{{ \App\Models\Submittal::STATUSES[$submittal->status] ?? $submittal->status }}</span>
                    </div>
                </div>
            </div></div>
        </div>
        <div class="col-lg-7">
            <div class="row g-3 h-100">
                <div class="col-6"><div class="stat-box"><div class="sl">المشروع</div><div class="sv">{{ $submittal->project?->name ?? '—' }}</div></div></div>
                <div class="col-6"><div class="stat-box"><div class="sl">موعد المراجعة</div><div class="sv {{ $submittal->isOverdue() ? 'text-danger' : '' }}">{{ $submittal->due_date?->format('Y-m-d') ?? '—' }}</div></div></div>
            </div>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <h6 class="mb-3"><i class="fa-solid fa-circle-info ms-1" style="color:#2b4c80"></i> بيانات الاعتماد</h6>
        <div class="info-list">
            <div class="il"><span class="k">النوع</span><span class="v">{{ \App\Models\Submittal::TYPES[$submittal->type] ?? $submittal->type }}</span></div>
            <div class="il"><span class="k">بند المواصفات</span><span class="v">{{ $submittal->spec_section ?: '—' }}</span></div>
            <div class="il"><span class="k">موجَّه إلى</span><span class="v">{{ $submittal->submitted_to ?: '—' }}</span></div>
            <div class="il"><span class="k">الحالة</span><span class="v">{{ \App\Models\Submittal::STATUSES[$submittal->status] ?? $submittal->status }}</span></div>
            <div class="il"><span class="k">تاريخ المراجعة</span><span class="v">{{ $submittal->reviewed_at?->format('Y-m-d H:i') ?? '—' }}</span></div>
            <div class="il"><span class="k">أُنشئ بواسطة</span><span class="v">{{ $submittal->creator?->name ?? '—' }}</span></div>
        </div>
    </div></div>

    @if ($submittal->description)
        <div class="card mb-3"><div class="card-body">
            <h6 class="mb-2"><i class="fa-solid fa-align-left ms-1" style="color:#2b4c80"></i> الوصف</h6>
            <p class="mb-0" style="white-space:pre-line">{{ $submittal->description }}</p>
        </div></div>
    @endif

    @if ($submittal->review_notes)
        <div class="card mb-3 border-success"><div class="card-body">
            <h6 class="mb-2"><i class="fa-solid fa-clipboard-check ms-1 text-success"></i> ملاحظات المراجعة</h6>
            <p class="mb-0" style="white-space:pre-line">{{ $submittal->review_notes }}</p>
        </div></div>
    @endif

    @can('projects.edit')
        @if (in_array($submittal->status, ['submitted','under_review']))
            <div class="card mb-3"><div class="card-body">
                <h6 class="mb-3"><i class="fa-solid fa-stamp ms-1" style="color:#2b4c80"></i> تسجيل نتيجة المراجعة</h6>
                <form method="POST" action="{{ route('submittals.review', $submittal) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">الحالة الجديدة</label>
                            <select name="status" class="form-select" required>
                                @foreach (['under_review','approved','approved_as_noted','rejected'] as $key)
                                    <option value="{{ $key }}" @selected($submittal->status === $key)>{{ \App\Models\Submittal::STATUSES[$key] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">ملاحظات المراجعة</label>
                            <textarea name="review_notes" rows="3" class="form-control" placeholder="اكتب ملاحظات الاستشاري...">{{ old('review_notes', $submittal->review_notes) }}</textarea>
                        </div>
                    </div>
                    <button class="btn mt-3" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-paper-plane ms-1"></i> حفظ النتيجة</button>
                </form>
            </div></div>
        @else
            <div class="alert alert-secondary"><i class="fa-solid fa-lock ms-1"></i> صدر قرار نهائي على هذا الاعتماد الفني ({{ \App\Models\Submittal::STATUSES[$submittal->status] ?? $submittal->status }}){{ $submittal->reviewed_at ? ' بتاريخ ' . $submittal->reviewed_at->format('Y-m-d H:i') : '' }} — لا يمكن إعادة المراجعة.</div>
        @endif
    @endcan
@endsection
