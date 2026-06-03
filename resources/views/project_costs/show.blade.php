@extends('layouts.app')

@section('title', 'تفاصيل تكلفة')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">تكلفة — {{ $cost->work_item }}</h5>
        <div class="d-flex gap-2">
            @can('projects.edit')
                <a href="{{ route('project_costs.edit', $cost) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
            @endcan
            <a href="{{ route('project_costs.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <div class="row g-3">
            <div class="col-md-4"><div class="text-muted small">المشروع</div><div class="fw-semibold">{{ $cost->project?->name ?? '—' }}</div></div>
            <div class="col-md-4"><div class="text-muted small">بند الأعمال</div><div class="fw-semibold">{{ $cost->work_item }}</div></div>
            <div class="col-md-4"><div class="text-muted small">الجهة (مقاول/مورد)</div><div>{{ $cost->contractor_supplier ?? '—' }}</div></div>
            <div class="col-md-4"><div class="text-muted small">الفئة</div><div>{{ $cost->category ?? '—' }}</div></div>
            <div class="col-md-4"><div class="text-muted small">الوصف</div><div>{{ $cost->description ?? '—' }}</div></div>
            <div class="col-md-4"><div class="text-muted small">التاريخ</div><div>{{ $cost->cost_date?->format('Y-m-d') }}</div></div>
        </div>
        <hr>
        <div class="row g-3 text-center">
            <div class="col"><div class="text-muted small">الكمية</div><div class="fs-5 fw-bold">{{ rtrim(rtrim(number_format($cost->quantity, 3), '0'), '.') }} {{ $cost->unit }}</div></div>
            <div class="col"><div class="text-muted small">سعر الوحدة</div><div class="fs-5">{{ number_format($cost->unit_price, 2) }}</div></div>
            <div class="col"><div class="text-muted small">الإجمالي</div><div class="fs-5 fw-bold text-success">{{ number_format($cost->amount, 2) }}</div></div>
        </div>
        @if ($cost->notes)<hr><div class="text-muted small">ملاحظات</div><div>{{ $cost->notes }}</div>@endif
    </div></div>
@endsection
