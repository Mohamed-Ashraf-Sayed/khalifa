@extends('layouts.app')

@section('title', 'إذن صرف ' . $requisition->requisition_number)

@section('content')
    @php($badge = match($requisition->status) {
        'approved' => 'primary', 'issued' => 'success',
        'rejected' => 'danger', 'pending' => 'warning', default => 'secondary' })

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">إذن صرف مواد رقم {{ $requisition->requisition_number }}</h5>
        <div class="d-flex gap-2">
            @can('materials.edit')
                @if ($requisition->status === 'pending')
                    <form method="POST" action="{{ route('material_requisitions.approve', $requisition) }}" class="d-inline" data-confirm="اعتماد إذن الصرف؟">
                        @csrf
                        <button class="btn btn-sm btn-success"><i class="fa-solid fa-check ms-1"></i> اعتماد</button>
                    </form>
                    <form method="POST" action="{{ route('material_requisitions.reject', $requisition) }}" class="d-inline" data-confirm="رفض إذن الصرف؟">
                        @csrf
                        <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-xmark ms-1"></i> رفض</button>
                    </form>
                @endif
                @if ($requisition->status === 'approved')
                    <form method="POST" action="{{ route('material_requisitions.issue', $requisition) }}" class="d-inline" data-confirm="صرف الأصناف وخصمها من المخزون؟">
                        @csrf
                        <button class="btn btn-sm" style="background:#0f7a4f;color:#fff"><i class="fa-solid fa-truck-ramp-box ms-1"></i> صرف من المخزون</button>
                    </form>
                @endif
                @if ($requisition->status === 'pending')
                    <a href="{{ route('material_requisitions.edit', $requisition) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
                @endif
            @endcan
            @can('materials.view')
                <a href="{{ route('material_requisitions.print', $requisition) }}" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="fa-solid fa-print ms-1"></i> طباعة</a>
            @endcan
            <a href="{{ route('material_requisitions.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <div class="row g-3">
            <div class="col-md-3"><div class="text-muted small">الحالة</div><div><span class="badge text-bg-{{ $badge }}">{{ \App\Models\MaterialRequisition::STATUSES[$requisition->status] ?? $requisition->status }}</span></div></div>
            <div class="col-md-3"><div class="text-muted small">المشروع</div><div>{{ $requisition->project?->name ?? 'عام' }}</div></div>
            <div class="col-md-3"><div class="text-muted small">تاريخ الطلب</div><div>{{ $requisition->request_date?->format('Y-m-d') ?? '—' }}</div></div>
            <div class="col-md-3"><div class="text-muted small">أنشأه</div><div>{{ $requisition->creator?->name ?? '—' }}</div></div>
            @if ($requisition->approver)
                <div class="col-md-3"><div class="text-muted small">اعتمده/راجعه</div><div>{{ $requisition->approver->name }} · {{ $requisition->approved_at?->format('Y-m-d') }}</div></div>
            @endif
            @if ($requisition->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $requisition->notes }}</div></div>@endif
        </div>
    </div></div>

    @can('materials.edit')
        @if ($requisition->status === 'pending')
            <div class="card mb-3"><div class="card-body">
                <form method="POST" action="{{ route('material_requisition_items.store', $requisition) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-5"><label class="form-label small">الصنف</label>
                        <select name="material_id" class="form-select" required>
                            <option value="">— اختر صنفاً —</option>
                            @foreach ($materials as $material)
                                <option value="{{ $material->id }}">{{ $material->name }} ({{ rtrim(rtrim(number_format($material->current_stock, 2), '0'), '.') }} {{ $material->unit }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><label class="form-label small">الكمية</label><input type="number" step="0.01" min="0.01" name="quantity" value="1" class="form-control" required></div>
                    <div class="col-md-2"><label class="form-label small">ملاحظات</label><input type="text" name="notes" class="form-control"></div>
                    <div class="col-md-2"><button class="btn w-100" style="background:#2b4c80;color:#fff">إضافة صنف</button></div>
                </form>
            </div></div>
        @endif
    @endcan

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">أصناف إذن الصرف</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light"><tr><th>الصنف</th><th>الوحدة</th><th>الكمية المطلوبة</th><th>الكمية المصروفة</th><th>الرصيد الحالي</th><th>ملاحظات</th><th class="text-end"></th></tr></thead>
                    <tbody>
                        @forelse ($requisition->items as $item)
                            <tr>
                                <td>{{ $item->material?->name ?? '—' }}</td>
                                <td>{{ $item->material?->unit ?? '—' }}</td>
                                <td>{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
                                <td class="fw-semibold">{{ rtrim(rtrim(number_format($item->issued_quantity, 2), '0'), '.') }}</td>
                                <td>{{ rtrim(rtrim(number_format($item->material?->current_stock ?? 0, 2), '0'), '.') }}</td>
                                <td>{{ $item->notes ?? '—' }}</td>
                                <td class="text-end">
                                    @can('materials.edit')
                                        @if ($requisition->status === 'pending')
                                            <form method="POST" action="{{ route('material_requisition_items.destroy', $item) }}" class="d-inline" data-confirm="حذف الصنف؟">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">لا توجد أصناف. أضِف صنفاً من الأعلى.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
