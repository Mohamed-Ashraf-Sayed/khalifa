@extends('layouts.app')

@section('title', 'طباعة وثيقة تأمين ' . $policy->policy_number)

@section('content')
    <style>
        @media print {
            .no-print { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            nav, .navbar, .sidebar, footer { display: none !important; }
            body { background: #fff !important; }
        }
        @page { size: A4; margin: 12mm; }
    </style>

    @php($badge = match($policy->status) { 'active'=>'success','expired'=>'danger','cancelled'=>'secondary', default=>'secondary' })

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="m-0">طباعة وثيقة تأمين #{{ $policy->policy_number }}</h5>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
            <a href="{{ route('insurance.show', $policy) }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
                <div>
                    <h3 class="m-0" style="color:#2b4c80">{{ \App\Models\Setting::get('company_name', 'القروانة') }}</h3>
                    @if ($addr = \App\Models\Setting::get('company_address'))<div class="text-muted small">{{ $addr }}</div>@endif
                    @if ($phone = \App\Models\Setting::get('company_phone'))<div class="text-muted small">هاتف: {{ $phone }}</div>@endif
                </div>
                <div class="text-start">
                    <h4 class="m-0">وثيقة تأمين</h4>
                    <div class="fw-bold">#{{ $policy->policy_number }}</div>
                    <span class="badge bg-{{ $badge }}">{{ \App\Models\InsurancePolicy::STATUSES[$policy->status] ?? $policy->status }}</span>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div class="text-muted small">جهة التأمين</div>
                    <div class="fw-bold">{{ $policy->provider }}</div>
                    @if ($policy->project)<div class="text-muted small">المشروع: {{ $policy->project->name }}</div>@endif
                </div>
                <div class="col-6 text-start">
                    <div>النوع: <strong>{{ \App\Models\InsurancePolicy::TYPES[$policy->type] ?? $policy->type }}</strong></div>
                    <div>تاريخ البدء: <strong>{{ $policy->start_date?->format('Y-m-d') ?? '—' }}</strong></div>
                    <div>تاريخ الانتهاء: <strong>{{ $policy->expiry_date?->format('Y-m-d') ?? '—' }}</strong></div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div class="text-muted small">مبلغ التغطية</div>
                    <div class="fw-bold fs-4 text-success">{{ number_format((float) $policy->coverage_amount, 2) }}</div>
                </div>
                <div class="col-6 text-start">
                    <div class="text-muted small">قسط التأمين</div>
                    <div class="fw-bold fs-5">{{ $policy->premium !== null ? number_format((float) $policy->premium, 2) : '—' }}</div>
                </div>
            </div>

            @if ($policy->notes)
                <div class="border-top pt-3 mt-2">
                    <div class="text-muted small">ملاحظات</div>
                    <div>{{ $policy->notes }}</div>
                </div>
            @endif

            <div class="row mt-5 pt-4">
                <div class="col-6 text-center">
                    <div class="border-top pt-2 d-inline-block px-4">المسؤول المختص</div>
                </div>
                <div class="col-6 text-center">
                    <div class="border-top pt-2 d-inline-block px-4">الختم والتوقيع</div>
                </div>
            </div>
        </div>
    </div>
@endsection
