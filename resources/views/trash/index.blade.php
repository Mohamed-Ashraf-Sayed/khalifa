@extends('layouts.app')

@section('title', 'سلة المحذوفات')

@php
    // عمود/دالة العرض المناسبة لكل نوع لإظهار اسم العنصر المحذوف
    function trashTitle($type, $r) {
        return match ($type) {
            'projects', 'clients', 'contractors', 'suppliers', 'employees', 'partners', 'materials' => $r->name,
            'invoices' => $r->invoice_number,
            'purchase_orders' => $r->order_number,
            'contractor_extracts' => $r->extract_number,
            'expenses' => $r->description,
            'revenues' => $r->description,
            default => '#'.$r->getKey(),
        };
    }
@endphp

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('trash.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small">نوع العناصر المحذوفة</label>
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        @foreach ($labels as $key => $label)
                            <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-magnifying-glass ms-1"></i> عرض</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white">
            <i class="fa-solid fa-trash-can-arrow-up ms-1" style="color:#8b7355"></i>
            <strong>{{ $labels[$type] ?? $type }}</strong> المحذوفة
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>العنصر</th>
                            <th>تاريخ الحذف</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $record)
                            <tr>
                                <td class="text-muted">#{{ $record->getKey() }}</td>
                                <td class="fw-semibold">{{ trashTitle($type, $record) ?: '—' }}</td>
                                <td class="small text-muted">{{ $record->deleted_at?->format('Y-m-d H:i') }}</td>
                                <td class="text-end">
                                    @can('users.delete')
                                        <form method="POST" action="{{ route('trash.restore', ['type' => $type, 'id' => $record->getKey()]) }}" class="d-inline" onsubmit="return confirm('استعادة هذا العنصر؟')">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success" title="استعادة"><i class="fa-solid fa-trash-arrow-up"></i> استعادة</button>
                                        </form>
                                        <form method="POST" action="{{ route('trash.force_delete', ['type' => $type, 'id' => $record->getKey()]) }}" class="d-inline" onsubmit="return confirm('حذف نهائي لا يمكن التراجع عنه. متأكد؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" title="حذف نهائي"><i class="fa-solid fa-xmark"></i> حذف نهائي</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">لا توجد عناصر محذوفة في هذا القسم.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $records->links() }}
        </div>
    </div>
@endsection
