@extends('layouts.app')

@section('title', 'المناقصة: ' . $tender->title)

@section('content')
    @php($badge = match($tender->status) { 'won'=>'success','submitted'=>'info','lost'=>'danger','cancelled'=>'secondary', default=>'warning' })

    <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
        @can('tenders.edit')
            @if ($tender->status === 'won' && ! $tender->project_id)
                <form method="POST" action="{{ route('tenders.convert', $tender) }}" onsubmit="return confirm('تحويل المناقصة إلى مشروع جديد؟')">
                    @csrf
                    <button class="btn btn-sm" style="background:#8b7355;color:#fff"><i class="fa-solid fa-diagram-project ms-1"></i> تحويل إلى مشروع</button>
                </form>
            @endif
            <a href="{{ route('tenders.edit', $tender) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
        @endcan
        <a href="{{ route('tenders.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
                <span class="entity-avatar"><i class="fa-solid fa-gavel"></i></span>
                <div class="flex-grow-1">
                    <div class="h5 mb-1">{{ $tender->title }}</div>
                    <div class="text-muted small">{{ $tender->tender_number }}</div>
                    <div class="mt-2">
                        <span class="badge text-bg-{{ $badge }}">{{ \App\Models\Tender::STATUSES[$tender->status] ?? $tender->status }}</span>
                        @if ($tender->project)
                            <a href="{{ route('projects.show', $tender->project) }}" class="badge text-bg-light ms-1 text-decoration-none"><i class="fa-solid fa-diagram-project"></i> {{ $tender->project->name }}</a>
                        @endif
                    </div>
                </div>
            </div></div>
        </div>
        <div class="col-lg-7">
            <div class="row g-3 h-100">
                <div class="col-6"><div class="stat-box"><div class="sl">القيمة التقديرية</div><div class="sv">{{ $tender->estimated_value !== null ? number_format((float) $tender->estimated_value, 2) : '—' }}</div></div></div>
                <div class="col-6"><div class="stat-box accent"><div class="sl">قيمة العطاء المقدّم</div><div class="sv text-success">{{ $tender->bid_value !== null ? number_format((float) $tender->bid_value, 2) : '—' }}</div></div></div>
                <div class="col-6"><div class="stat-box"><div class="sl">تاريخ التقديم</div><div class="sv">{{ $tender->submission_date?->format('Y-m-d') ?? '—' }}</div></div></div>
                <div class="col-6"><div class="stat-box"><div class="sl">العميل / جهة الطرح</div><div class="sv" style="font-size:1rem">{{ $tender->client?->name ?? '—' }}</div></div></div>
            </div>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <h6 class="mb-3"><i class="fa-solid fa-circle-info ms-1" style="color:#8b7355"></i> بيانات المناقصة</h6>
        <div class="info-list">
            <div class="il"><span class="k">رقم المناقصة</span><span class="v">{{ $tender->tender_number }}</span></div>
            <div class="il"><span class="k">الحالة</span><span class="v"><span class="badge text-bg-{{ $badge }}">{{ \App\Models\Tender::STATUSES[$tender->status] ?? $tender->status }}</span></span></div>
            <div class="il"><span class="k">خطاب الضمان (عطاء)</span><span class="v">{{ $tender->guarantee?->guarantee_number ?? ($tender->guarantee_id ? 'خطاب #' . $tender->guarantee_id : '—') }}</span></div>
            <div class="il"><span class="k">المشروع المرتبط</span><span class="v">{{ $tender->project?->name ?? '—' }}</span></div>
            <div class="il"><span class="k">أُنشئت بواسطة</span><span class="v">{{ $tender->creator?->name ?? '—' }}</span></div>
            <div class="il"><span class="k">تاريخ الإنشاء</span><span class="v">{{ $tender->created_at?->format('Y-m-d') }}</span></div>
            @if ($tender->notes)<div class="il" style="grid-column:1/-1"><span class="k">ملاحظات</span><span class="v">{{ $tender->notes }}</span></div>@endif
        </div>
    </div></div>
@endsection
