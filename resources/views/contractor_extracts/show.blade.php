@extends('layouts.app')

@section('title', 'بيانات المستخلص')

@section('content')
    @php($badge = match($extract->status) {
        'paid' => 'success', 'approved' => 'primary',
        'partial' => 'info', 'cancelled' => 'danger', default => 'secondary' })

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">مستخلص رقم {{ $extract->extract_number }}</h5>
        <div class="d-flex gap-2">
            @can('contractors.edit')
                <a href="{{ route('contractor_extracts.edit', $extract) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
            @endcan
            <a href="{{ route('contractor_extracts.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">رقم المستخلص</div><div class="fw-semibold">{{ $extract->extract_number }}</div></div>
                <div class="col-md-4"><div class="text-muted small">المقاول</div><div>{{ $extract->contractor?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">المشروع</div><div>{{ $extract->project?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ المستخلص</div><div>{{ optional($extract->extract_date)->format('Y-m-d') ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الحالة</div><div><span class="badge text-bg-{{ $badge }}">{{ \App\Models\ContractorExtract::STATUSES[$extract->status] ?? $extract->status }}</span></div></div>
                <div class="col-md-4"><div class="text-muted small">الإجمالي</div><div class="fw-semibold">{{ number_format($extract->total_amount, 2) }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الخصومات</div><div class="text-danger">{{ number_format($extract->deductions, 2) }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الصافي</div><div class="fw-bold">{{ number_format($extract->net_amount, 2) }}</div></div>
                @if ($extract->description)<div class="col-12"><div class="text-muted small">الوصف</div><div>{{ $extract->description }}</div></div>@endif
                @if ($extract->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $extract->notes }}</div></div>@endif
            </div>
        </div>
    </div>
@endsection
