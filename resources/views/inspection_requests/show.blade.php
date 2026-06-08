@extends('layouts.app')

@section('title', 'طلب فحص: ' . $inspectionRequest->ir_number)

@section('content')
    @php($badge = match($inspectionRequest->status) { 'pending'=>'warning','approved'=>'success','rejected'=>'danger','closed'=>'secondary', default=>'secondary' })

    <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
        @can('projects.edit')
            <a href="{{ route('inspection_requests.edit', $inspectionRequest) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
        @endcan
        <a href="{{ route('inspection_requests.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
    </div>

    @if ($inspectionRequest->isOverdue())
        <div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation ms-1"></i> طلب الفحص متأخر — الموعد المجدول {{ $inspectionRequest->scheduled_date->format('Y-m-d') }}.</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
                <span class="entity-avatar"><i class="fa-solid fa-clipboard-list"></i></span>
                <div class="flex-grow-1">
                    <div class="h5 mb-1">{{ $inspectionRequest->ir_number }}</div>
                    <div class="text-muted small">{{ $inspectionRequest->title }} · {{ \App\Models\InspectionRequest::TYPES[$inspectionRequest->type] ?? $inspectionRequest->type }}</div>
                    <div class="mt-2">
                        <span class="badge text-bg-{{ $badge }}">{{ \App\Models\InspectionRequest::STATUSES[$inspectionRequest->status] ?? $inspectionRequest->status }}</span>
                    </div>
                </div>
            </div></div>
        </div>
        <div class="col-lg-7">
            <div class="row g-3 h-100">
                <div class="col-6"><div class="stat-box"><div class="sl">المشروع</div><div class="sv">{{ $inspectionRequest->project?->name ?? '—' }}</div></div></div>
                <div class="col-6"><div class="stat-box"><div class="sl">الموعد المجدول</div><div class="sv {{ $inspectionRequest->isOverdue() ? 'text-danger' : '' }}">{{ $inspectionRequest->scheduled_date?->format('Y-m-d') ?? '—' }}</div></div></div>
            </div>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <h6 class="mb-3"><i class="fa-solid fa-circle-info ms-1" style="color:#2b4c80"></i> بيانات الطلب</h6>
        <div class="info-list">
            <div class="il"><span class="k">النوع</span><span class="v">{{ \App\Models\InspectionRequest::TYPES[$inspectionRequest->type] ?? $inspectionRequest->type }}</span></div>
            <div class="il"><span class="k">الموقع / المكان</span><span class="v">{{ $inspectionRequest->location ?: '—' }}</span></div>
            <div class="il"><span class="k">الحالة</span><span class="v">{{ \App\Models\InspectionRequest::STATUSES[$inspectionRequest->status] ?? $inspectionRequest->status }}</span></div>
            <div class="il"><span class="k">الفاحص</span><span class="v">{{ $inspectionRequest->inspector ?: '—' }}</span></div>
            <div class="il"><span class="k">تاريخ الفحص</span><span class="v">{{ $inspectionRequest->inspected_at?->format('Y-m-d H:i') ?? '—' }}</span></div>
            <div class="il"><span class="k">أُنشئ بواسطة</span><span class="v">{{ $inspectionRequest->creator?->name ?? '—' }}</span></div>
            @if ($inspectionRequest->notes)<div class="il" style="grid-column:1/-1"><span class="k">ملاحظات</span><span class="v">{{ $inspectionRequest->notes }}</span></div>@endif
        </div>
    </div></div>

    @if ($inspectionRequest->result)
        <div class="card mb-3 border-success"><div class="card-body">
            <h6 class="mb-2"><i class="fa-solid fa-clipboard-check ms-1 text-success"></i> نتيجة الفحص</h6>
            <p class="mb-0" style="white-space:pre-line">{{ $inspectionRequest->result }}</p>
        </div></div>
    @endif

    @can('projects.edit')
        @if ($inspectionRequest->status === 'pending')
            <div class="card mb-3"><div class="card-body">
                <h6 class="mb-3"><i class="fa-solid fa-clipboard-check ms-1" style="color:#2b4c80"></i> تسجيل نتيجة الفحص</h6>
                <form method="POST" action="{{ route('inspection_requests.inspect', $inspectionRequest) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">النتيجة <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="approved">مقبول</option>
                                <option value="rejected">مرفوض</option>
                                <option value="closed">مغلق</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">الفاحص</label>
                            <input type="text" name="inspector" value="{{ old('inspector') }}" class="form-control" placeholder="اسم الفاحص / المهندس">
                        </div>
                        <div class="col-12">
                            <label class="form-label">تفاصيل النتيجة</label>
                            <textarea name="result" rows="4" class="form-control" placeholder="اكتب ملاحظات الفحص هنا...">{{ old('result') }}</textarea>
                        </div>
                    </div>
                    <button class="btn mt-3" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-paper-plane ms-1"></i> حفظ النتيجة</button>
                </form>
            </div></div>
        @endif
    @endcan
@endsection
