@extends('layouts.app')

@section('title', 'وثيقة تأمين: ' . $policy->policy_number)

@section('content')
    @php($badge = match($policy->status) { 'active'=>'success','expired'=>'danger','cancelled'=>'secondary', default=>'secondary' })

    <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
        @can('guarantees.edit')
            <a href="{{ route('insurance.edit', $policy) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
        @endcan
        <a href="{{ route('insurance.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
    </div>

    @if ($policy->isExpiringSoon(30))
        <div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation ms-1"></i> هذه الوثيقة قاربت على الانتهاء بتاريخ {{ $policy->expiry_date->format('Y-m-d') }}.</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
                <span class="entity-avatar"><i class="fa-solid fa-file-shield"></i></span>
                <div class="flex-grow-1">
                    <div class="h5 mb-1">{{ $policy->policy_number }}</div>
                    <div class="text-muted small">{{ \App\Models\InsurancePolicy::TYPES[$policy->type] ?? $policy->type }} · {{ $policy->provider }}</div>
                    <div class="mt-2">
                        <span class="badge text-bg-{{ $badge }}">{{ \App\Models\InsurancePolicy::STATUSES[$policy->status] ?? $policy->status }}</span>
                    </div>
                </div>
            </div></div>
        </div>
        <div class="col-lg-7">
            <div class="row g-3 h-100">
                <div class="col-6"><div class="stat-box accent"><div class="sl">مبلغ التغطية</div><div class="sv text-success">{{ number_format((float) $policy->coverage_amount, 2) }}</div></div></div>
                <div class="col-6"><div class="stat-box"><div class="sl">قسط التأمين</div><div class="sv">{{ $policy->premium !== null ? number_format((float) $policy->premium, 2) : '—' }}</div></div></div>
            </div>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <h6 class="mb-3"><i class="fa-solid fa-circle-info ms-1" style="color:#8b7355"></i> بيانات الوثيقة</h6>
        <div class="info-list">
            <div class="il"><span class="k">تاريخ البدء</span><span class="v">{{ $policy->start_date?->format('Y-m-d') ?? '—' }}</span></div>
            <div class="il"><span class="k">تاريخ الانتهاء</span><span class="v">{{ $policy->expiry_date?->format('Y-m-d') ?? '—' }}</span></div>
            <div class="il"><span class="k">المشروع المرتبط</span><span class="v">{{ $policy->project?->name ?: '—' }}</span></div>
            <div class="il"><span class="k">أُنشئت بواسطة</span><span class="v">{{ $policy->creator?->name ?: '—' }}</span></div>
            @if ($policy->notes)<div class="il" style="grid-column:1/-1"><span class="k">ملاحظات</span><span class="v">{{ $policy->notes }}</span></div>@endif
        </div>
    </div></div>
@endsection
