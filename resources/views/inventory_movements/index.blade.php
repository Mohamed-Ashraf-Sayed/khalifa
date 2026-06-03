@extends('layouts.app')

@section('title', 'حركات المخزون')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form method="GET">
                    <select name="type" class="form-select" style="min-width:180px" onchange="this.form.submit()">
                        <option value="">كل الحركات</option>
                        @foreach (\App\Models\InventoryMovement::TYPES as $k => $label)
                            <option value="{{ $k }}" @selected($type === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
                @can('materials.edit')
                    <a href="{{ route('inventory_movements.create') }}" class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-plus ms-1"></i> حركة جديدة</a>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>التاريخ</th><th>المادة</th><th>النوع</th><th>الكمية</th><th>المشروع</th><th>السبب</th><th class="text-end"></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($movements as $m)
                            <tr>
                                <td>{{ $m->movement_date->format('Y-m-d') }}</td>
                                <td class="fw-semibold">{{ $m->material?->name ?? '—' }}</td>
                                <td><span class="badge text-bg-{{ $m->type === 'in' ? 'success' : 'warning' }}">{{ \App\Models\InventoryMovement::TYPES[$m->type] ?? $m->type }}</span></td>
                                <td class="fw-bold">{{ number_format($m->quantity, 2) }}</td>
                                <td>{{ $m->project?->name ?? '—' }}</td>
                                <td>{{ $m->reason ?: '—' }}</td>
                                <td class="text-end">
                                    @can('materials.edit')
                                        <form method="POST" action="{{ route('inventory_movements.destroy', $m) }}" class="d-inline" onsubmit="return confirm('حذف الحركة وعكس أثرها؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">لا توجد حركات بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $movements->links() }}
        </div>
    </div>
@endsection
