@extends('layouts.app')

@section('title', 'الموردون')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2" method="GET">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بحث بالاسم أو الشركة أو الهاتف">
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                @can('suppliers.create')
                    <a href="{{ route('suppliers.create') }}" class="btn btn-brown" style="background:#8b7355;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> مورد جديد
                    </a>
                @endcan
            </div>

            @can('suppliers.delete')
                <form id="bulk-form" method="POST" action="{{ route('suppliers.bulk_destroy') }}" onsubmit="return confirm('حذف الموردين المحددين؟')">
                    @csrf
                    <div id="bulk-toolbar" class="d-none mb-3">
                        <button type="submit" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash ms-1"></i> حذف المحدد (<span id="bulk-count">0</span>)</button>
                    </div>
            @endcan

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            @can('suppliers.delete')<th style="width:1%"><input type="checkbox" id="bulk-select-all" class="form-check-input"></th>@endcan
                            <th>الاسم</th>
                            <th>الشركة</th>
                            <th>النوع</th>
                            <th>الهاتف</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($suppliers as $supplier)
                            <tr>
                                @can('suppliers.delete')<td><input type="checkbox" form="bulk-form" name="ids[]" value="{{ $supplier->id }}" class="form-check-input bulk-item"></td>@endcan
                                <td class="fw-semibold">{{ $supplier->name }}</td>
                                <td>{{ $supplier->company_name ?: '—' }}</td>
                                <td><span class="badge text-bg-light">{{ \App\Models\Supplier::TYPES[$supplier->type] ?? $supplier->type }}</span></td>
                                <td>{{ $supplier->phone }}</td>
                                <td>
                                    @if ($supplier->is_active)
                                        <span class="badge text-bg-success">نشط</span>
                                    @else
                                        <span class="badge text-bg-secondary">غير نشط</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-sm btn-outline-secondary" title="عرض">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @can('suppliers.edit')
                                        <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-primary" title="تعديل">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    @endcan
                                    @can('suppliers.delete')
                                        <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" class="d-inline"
                                              onsubmit="return confirm('متأكد من حذف المورد؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">لا يوجد موردون بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @can('suppliers.delete')</form>@endcan

            {{ $suppliers->links() }}
        </div>
    </div>

    @can('suppliers.delete')
        <script>
            (function () {
                const selectAll = document.getElementById('bulk-select-all');
                const items = Array.from(document.querySelectorAll('.bulk-item'));
                const toolbar = document.getElementById('bulk-toolbar');
                const counter = document.getElementById('bulk-count');

                function refresh() {
                    const checked = items.filter(i => i.checked).length;
                    counter.textContent = checked;
                    toolbar.classList.toggle('d-none', checked === 0);
                    if (selectAll) selectAll.checked = checked > 0 && checked === items.length;
                }

                if (selectAll) {
                    selectAll.addEventListener('change', () => {
                        items.forEach(i => { i.checked = selectAll.checked; });
                        refresh();
                    });
                }
                items.forEach(i => i.addEventListener('change', refresh));
            })();
        </script>
    @endcan
@endsection
