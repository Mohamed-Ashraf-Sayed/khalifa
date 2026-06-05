@extends('layouts.app')

@section('title', 'أذون صرف المواد')

@section('content')
    <div class="row g-3 mb-3">
        @foreach ([
            ['عدد الأذون', number_format($stats['count']), 'fa-clipboard-check', 'text-primary'],
            ['بانتظار الاعتماد', number_format($stats['pending']), 'fa-hourglass-half', 'text-warning'],
            ['تم صرفها', number_format($stats['issued']), 'fa-truck-fast', 'text-success'],
        ] as [$l, $v, $icon, $color])
        <div class="col-md-4 col-6"><div class="card h-100"><div class="card-body py-3">
            <i class="fa-solid {{ $icon }} {{ $color }}"></i>
            <div class="fs-4 fw-bold">{{ $v }}</div>
            <div class="small text-muted">{{ $l }}</div>
        </div></div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2" method="GET">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بحث برقم الإذن">
                    <select name="status" class="form-select" style="min-width:160px" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\MaterialRequisition::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                @can('materials.create')
                    <a href="{{ route('material_requisitions.create') }}" class="btn" style="background:#8b7355;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> إذن صرف جديد
                    </a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>رقم الإذن</th>
                            <th>المشروع</th>
                            <th>تاريخ الطلب</th>
                            <th>عدد الأصناف</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requisitions as $requisition)
                            @php($badge = match($requisition->status) {
                                'approved' => 'primary', 'issued' => 'success',
                                'rejected' => 'danger', 'pending' => 'warning', default => 'secondary' })
                            <tr>
                                <td class="fw-semibold">{{ $requisition->requisition_number }}</td>
                                <td>{{ $requisition->project?->name ?? 'عام' }}</td>
                                <td>{{ $requisition->request_date?->format('Y-m-d') ?? '—' }}</td>
                                <td>{{ $requisition->items()->count() }}</td>
                                <td><span class="badge text-bg-{{ $badge }}">{{ \App\Models\MaterialRequisition::STATUSES[$requisition->status] ?? $requisition->status }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('material_requisitions.show', $requisition) }}" class="btn btn-sm btn-outline-secondary" title="عرض">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @can('materials.edit')
                                        @if ($requisition->status === 'pending')
                                            <a href="{{ route('material_requisitions.edit', $requisition) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                        @endif
                                    @endcan
                                    @can('materials.delete')
                                        <form method="POST" action="{{ route('material_requisitions.destroy', $requisition) }}" class="d-inline"
                                              onsubmit="return confirm('متأكد من حذف إذن الصرف؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">لا توجد أذون صرف بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $requisitions->links() }}
        </div>
    </div>
@endsection
