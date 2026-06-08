@extends('layouts.app')

@section('title', 'حركات المخزون')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form method="GET" class="d-flex gap-2 flex-wrap">
                    <select name="type" class="form-select" style="min-width:180px" onchange="this.form.submit()">
                        <option value="">كل الحركات</option>
                        @foreach (\App\Models\InventoryMovement::TYPES as $k => $label)
                            <option value="{{ $k }}" @selected($type === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="material_id" class="form-select" style="min-width:200px" onchange="this.form.submit()">
                        <option value="">كل المواد</option>
                        @foreach ($materials as $mat)
                            <option value="{{ $mat->id }}" @selected($materialId === (string) $mat->id)>{{ $mat->name }}</option>
                        @endforeach
                    </select>
                </form>
                @can('materials.edit')
                    <a href="{{ route('inventory_movements.create') }}" class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-plus ms-1"></i> حركة جديدة</a>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>التاريخ</th><th>المادة</th><th>النوع</th><th>الكمية</th><th>سعر الوحدة</th><th>القيمة</th><th>الرصيد بعد</th><th>المشروع</th><th>السبب</th><th class="text-end"></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($movements as $m)
                            @php($badge = match($m->type) {
                                'in' => 'success', 'out' => 'warning', 'transfer' => 'info', 'adjustment' => 'secondary', default => 'secondary' })
                            <tr>
                                <td>{{ $m->movement_date->format('Y-m-d') }}</td>
                                <td class="fw-semibold">{{ $m->material?->name ?? '—' }}</td>
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
                                <td>{{ $m->reason ?: '—' }}</td>
                                <td class="text-end">
                                    @can('materials.edit')
                                        <form method="POST" action="{{ route('inventory_movements.destroy', $m) }}" class="d-inline" data-confirm="حذف الحركة وعكس أثرها؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted py-4">لا توجد حركات بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $movements->links() }}
        </div>
    </div>
@endsection
