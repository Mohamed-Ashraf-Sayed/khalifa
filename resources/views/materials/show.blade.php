@extends('layouts.app')

@section('title', 'بيانات المادة')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">{{ $material->name }}</h5>
        <div class="d-flex gap-2">
            @can('materials.edit')
                <a href="{{ route('materials.edit', $material) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
            @endcan
            <a href="{{ route('materials.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">المادة</div><div class="fw-semibold">{{ $material->name }}</div></div>
                <div class="col-md-4"><div class="text-muted small">التصنيف</div><div><span class="badge text-bg-secondary">{{ \App\Models\Material::CATEGORIES[$material->category] ?? $material->category }}</span></div></div>
                <div class="col-md-4"><div class="text-muted small">الوحدة</div><div>{{ $material->unit }}</div></div>
                <div class="col-md-4"><div class="text-muted small">سعر الوحدة</div><div>{{ number_format($material->unit_price, 2) }} ج</div></div>
                <div class="col-md-4">
                    <div class="text-muted small">المخزون الحالي</div>
                    <div>
                        {{ number_format($material->current_stock, 2) }}
                        @if ($material->current_stock <= $material->min_stock)
                            <span class="badge text-bg-danger ms-1">مخزون منخفض</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-4"><div class="text-muted small">الحد الأدنى للمخزون</div><div>{{ number_format($material->min_stock, 2) }}</div></div>
                <div class="col-md-4"><div class="text-muted small">المشروع</div><div>{{ $material->project?->name ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">المورد</div><div>{{ $material->supplier?->name ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">القيمة الإجمالية</div><div>{{ number_format($material->unit_price * $material->current_stock, 2) }} ج</div></div>
                <div class="col-md-4"><div class="text-muted small">أضيف بواسطة</div><div>{{ $material->creator?->name ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ الإضافة</div><div>{{ $material->created_at?->format('Y-m-d') ?: '—' }}</div></div>
                @if ($material->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $material->notes }}</div></div>@endif
            </div>
        </div>
    </div>
@endsection
