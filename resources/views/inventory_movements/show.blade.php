@extends('layouts.app')

@section('title', 'تفاصيل حركة المخزون')

@section('content')
    @php($badge = match($movement->type) {
        'in' => 'success', 'out' => 'warning', 'transfer' => 'info', 'adjustment' => 'secondary', default => 'secondary' })

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">
            تفاصيل الحركة
            <span class="badge text-bg-{{ $badge }}">{{ \App\Models\InventoryMovement::TYPES[$movement->type] ?? $movement->type }}</span>
        </h5>
        <div class="d-flex gap-2">
            @can('materials.view')
                @if ($movement->material)
                    <a href="{{ route('materials.show', $movement->material) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-box ms-1"></i> صفحة المادة</a>
                @endif
            @endcan
            <a href="{{ route('inventory_movements.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">المادة</div><div class="fw-semibold">{{ $movement->material?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">النوع</div><div><span class="badge text-bg-{{ $badge }}">{{ \App\Models\InventoryMovement::TYPES[$movement->type] ?? $movement->type }}</span></div></div>
                <div class="col-md-4"><div class="text-muted small">التاريخ</div><div>{{ $movement->movement_date?->format('Y-m-d') ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الكمية</div><div class="fw-bold">{{ number_format($movement->quantity, 2) }} {{ $movement->material?->unit }}</div></div>
                <div class="col-md-4"><div class="text-muted small">سعر الوحدة</div><div>{{ number_format($movement->unit_price, 2) }} ج</div></div>
                <div class="col-md-4"><div class="text-muted small">القيمة الإجمالية</div><div>{{ number_format($movement->total_value, 2) }} ج</div></div>
                <div class="col-md-4"><div class="text-muted small">الرصيد قبل</div><div>{{ number_format($movement->stock_before, 2) }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الرصيد بعد</div><div class="fw-semibold">{{ number_format($movement->stock_after, 2) }}</div></div>
                <div class="col-md-4"><div class="text-muted small">موقع المخزن</div><div>{{ $movement->warehouse_location ?: '—' }}</div></div>
                <div class="col-md-4">
                    <div class="text-muted small">المشروع</div>
                    <div>
                        {{ $movement->project?->name ?? '—' }}
                        @if ($movement->type === 'transfer' && $movement->toProject)
                            <i class="fa-solid fa-arrow-left-long mx-1 text-muted"></i> {{ $movement->toProject->name }}
                        @endif
                    </div>
                </div>
                <div class="col-md-4"><div class="text-muted small">المستلِم</div><div>{{ $movement->employee?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">السبب</div><div>{{ $movement->reason ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">سُجّلت بواسطة</div><div>{{ $movement->creator?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ التسجيل</div><div>{{ $movement->created_at?->format('Y-m-d H:i') ?? '—' }}</div></div>
                @if ($requisition)
                    <div class="col-md-4">
                        <div class="text-muted small">المستند المرجعي</div>
                        <div>
                            <a href="{{ route('material_requisitions.show', $requisition) }}">
                                إذن صرف {{ $requisition->requisition_number }}
                            </a>
                        </div>
                    </div>
                @endif
                @if ($movement->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $movement->notes }}</div></div>@endif
            </div>
        </div>
    </div>
@endsection
