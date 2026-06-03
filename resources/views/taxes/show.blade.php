@extends('layouts.app')

@section('title', 'بيانات الضريبة')

@section('content')
    @php($badge = match($tax->status) {
        'paid' => 'success', 'cancelled' => 'danger', default => 'secondary' })
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">{{ $tax->name }}</h5>
        <div class="d-flex gap-2">
            @can('taxes.edit')
                <a href="{{ route('taxes.edit', $tax) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
            @endcan
            <a href="{{ route('taxes.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">الاسم</div><div class="fw-semibold">{{ $tax->name }}</div></div>
                <div class="col-md-4"><div class="text-muted small">النوع</div><div><span class="badge text-bg-info">{{ \App\Models\Tax::TYPES[$tax->tax_type] ?? $tax->tax_type }}</span></div></div>
                <div class="col-md-4"><div class="text-muted small">الحالة</div><div><span class="badge text-bg-{{ $badge }}">{{ \App\Models\Tax::STATUSES[$tax->status] ?? $tax->status }}</span></div></div>
                <div class="col-md-4"><div class="text-muted small">المشروع</div><div>{{ $tax->project?->name ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">النسبة</div><div>{{ number_format($tax->rate, 2) }}%</div></div>
                <div class="col-md-4"><div class="text-muted small">المبلغ الأساسي</div><div>{{ number_format($tax->base_amount, 2) }} ج</div></div>
                <div class="col-md-4"><div class="text-muted small">قيمة الضريبة</div><div class="fw-semibold">{{ number_format($tax->amount, 2) }} ج</div></div>
                <div class="col-md-4"><div class="text-muted small">الفترة</div><div>{{ $tax->period ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ الاستحقاق</div><div>{{ $tax->due_date?->format('Y-m-d') ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">أُنشئت بواسطة</div><div>{{ $tax->creator?->name ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ الإنشاء</div><div>{{ $tax->created_at?->format('Y-m-d') ?? '—' }}</div></div>
                @if ($tax->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $tax->notes }}</div></div>@endif
            </div>
        </div>
    </div>
@endsection
