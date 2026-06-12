@extends('layouts.app')

@section('title', 'حركات المخزون')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="statcard sc-primary h-100"><span class="sc-ic"><i class="fa-solid fa-right-left"></i></span><span><span class="sc-v d-block">{{ number_format($stats['count']) }}</span><span class="sc-l d-block">عدد الحركات (ضمن الفلترة)</span></span></div>
        </div>
        <div class="col-md-4">
            <div class="statcard sc-success h-100"><span class="sc-ic"><i class="fa-solid fa-arrow-trend-up"></i></span><span><span class="sc-v d-block">{{ number_format((float) $stats['in_value'], 2) }} ج</span><span class="sc-l d-block">إجمالي قيمة الإضافات</span></span></div>
        </div>
        <div class="col-md-4">
            <div class="statcard sc-warning h-100"><span class="sc-ic"><i class="fa-solid fa-arrow-trend-down"></i></span><span><span class="sc-v d-block">{{ number_format((float) $stats['out_value'], 2) }} ج</span><span class="sc-l d-block">إجمالي قيمة الصرف</span></span></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end gap-2 mb-3">
                @can('materials.edit')
                    <a href="{{ route('inventory_movements.create') }}" class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-plus ms-1"></i> حركة جديدة</a>
                @endcan
            </div>

            <form method="GET" class="filter-bar row g-2 align-items-end mb-3">
                <div class="col-6 col-md-3">
                    <label class="form-label">النوع</label>
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="">كل الحركات</option>
                        @foreach (\App\Models\InventoryMovement::TYPES as $k => $label)
                            <option value="{{ $k }}" @selected($type === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">المادة</label>
                    <select name="material_id" class="form-select" onchange="this.form.submit()">
                        <option value="">كل المواد</option>
                        @foreach ($materials as $mat)
                            <option value="{{ $mat->id }}" @selected($materialId === (string) $mat->id)>{{ $mat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-auto">
                    <div class="filter-actions">
                        <button class="btn btn-primary"><i class="fa-solid fa-magnifying-glass ms-1"></i> بحث</button>
                        @if (request()->query())
                            <a href="{{ url()->current() }}" class="btn btn-light">مسح</a>
                        @endif
                    </div>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>التاريخ</th><th>المادة</th><th>النوع</th><th>الكمية</th><th>سعر الوحدة</th><th>القيمة</th><th>الرصيد بعد</th><th>المشروع</th><th>المستلِم</th><th>السبب</th><th class="text-end"></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($movements as $m)
                            @php($badge = match($m->type) {
                                'in' => 'success', 'out' => 'warning', 'transfer' => 'info', 'adjustment' => 'secondary', default => 'secondary' })
                            <tr>
                                <td>{{ $m->movement_date->format('Y-m-d') }}</td>
                                <td class="fw-semibold">
                                    {{ $m->material?->name ?? '—' }}
                                    @if ($m->warehouse_location)
                                        <i class="fa-solid fa-location-dot text-muted ms-1" title="موقع المخزن: {{ $m->warehouse_location }}"></i>
                                    @endif
                                </td>
                                <td><span class="badge text-bg-{{ $badge }}">{{ \App\Models\InventoryMovement::TYPES[$m->type] ?? $m->type }}</span></td>
                                <td class="fw-bold">{{ number_format($m->quantity, 2) }}</td>
                                <td>{{ number_format($m->unit_price, 2) }} ج</td>
                                <td>{{ number_format($m->total_value, 2) }} ج</td>
                                <td>{{ number_format($m->stock_after, 2) }}</td>
                                <td>
                                    {{ $m->project?->name ?? '—' }}
                                    @if ($m->type === 'transfer' && $m->toProject)
                                        <i class="fa-solid fa-arrow-left-long mx-1 text-muted"></i> {{ $m->toProject->name }}
                                    @endif
                                </td>
                                <td>{{ $m->employee?->name ?? '—' }}</td>
                                <td>{{ $m->reason ?: '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('inventory_movements.show', $m) }}" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-eye"></i></a>
                                    @can('materials.edit')
                                        <form method="POST" action="{{ route('inventory_movements.destroy', $m) }}" class="d-inline" data-confirm="حذف الحركة وعكس أثرها؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="text-center text-muted py-4">لا توجد حركات بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $movements->links() }}
        </div>
    </div>
@endsection
