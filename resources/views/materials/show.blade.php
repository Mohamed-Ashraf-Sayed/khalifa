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
                <div class="col-md-4"><div class="text-muted small">موقع المخزن</div><div>{{ $material->warehouse_location ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">القيمة الإجمالية</div><div>{{ number_format($material->unit_price * $material->current_stock, 2) }} ج</div></div>
                <div class="col-md-4"><div class="text-muted small">أضيف بواسطة</div><div>{{ $material->creator?->name ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ الإضافة</div><div>{{ $material->created_at?->format('Y-m-d') ?: '—' }}</div></div>
                @if ($material->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $material->notes }}</div></div>@endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="m-0">آخر حركات المخزون</h6>
                @can('materials.edit')
                    <a href="{{ route('inventory_movements.create', ['material_id' => $material->id]) }}" class="btn btn-sm" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-plus ms-1"></i> إضافة حركة</a>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>التاريخ</th><th>النوع</th><th>الكمية</th><th>الرصيد بعد</th><th>المشروع</th><th>السبب</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($material->movements as $m)
                            @php($badge = match($m->type) {
                                'in' => 'success', 'out' => 'warning', 'transfer' => 'info', 'adjustment' => 'secondary', default => 'secondary' })
                            <tr>
                                <td>{{ $m->movement_date?->format('Y-m-d') ?? '—' }}</td>
                                <td><span class="badge text-bg-{{ $badge }}">{{ \App\Models\InventoryMovement::TYPES[$m->type] ?? $m->type }}</span></td>
                                <td class="fw-bold">{{ number_format($m->quantity, 2) }}</td>
                                <td>{{ number_format($m->stock_after, 2) }}</td>
                                <td>
                                    {{ $m->project?->name ?? '—' }}
                                    @if ($m->type === 'transfer' && $m->toProject)
                                        <i class="fa-solid fa-arrow-left-long mx-1 text-muted"></i> {{ $m->toProject->name }}
                                    @endif
                                </td>
                                <td>{{ $m->reason ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">لا توجد حركات لهذه المادة بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
