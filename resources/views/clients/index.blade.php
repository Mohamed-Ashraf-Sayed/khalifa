@extends('layouts.app')

@section('title', 'العملاء')

@section('content')
    <div class="row g-3 mb-3">
        @foreach ([
            ['إجمالي العملاء', number_format($stats['count']), 'fa-users', 'text-primary'],
            ['عملاء لديهم مشاريع', number_format($stats['with_projects']), 'fa-diagram-project', 'text-info'],
            ['إجمالي الرصيد المستحقّ', number_format((float) $stats['balance_due'], 2), 'fa-scale-balanced', 'text-warning'],
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
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بحث بالاسم أو الشركة أو الهاتف">
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                @can('clients.create')
                    <a href="{{ route('clients.create') }}" class="btn btn-brown" style="background:#2b4c80;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> عميل جديد
                    </a>
                @endcan
            </div>

            @can('clients.delete')
                <form id="bulk-form" method="POST" action="{{ route('clients.bulk_destroy') }}" data-confirm="حذف العملاء المحددين؟">
                    @csrf
                    <div id="bulk-toolbar" class="d-none mb-3">
                        <button type="submit" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash ms-1"></i> حذف المحدد (<span id="bulk-count">0</span>)</button>
                    </div>
                </form>
            @endcan

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            @can('clients.delete')<th style="width:1%"><input type="checkbox" id="bulk-select-all" class="form-check-input"></th>@endcan
                            <th>الاسم</th>
                            <th>الشركة</th>
                            <th>الهاتف</th>
                            <th>المدينة</th>
                            <th>المشاريع</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clients as $client)
                            <tr>
                                @can('clients.delete')<td><input type="checkbox" form="bulk-form" name="ids[]" value="{{ $client->id }}" class="form-check-input bulk-item"></td>@endcan
                                <td class="fw-semibold">{{ $client->name }}</td>
                                <td>{{ $client->company_name ?: '—' }}</td>
                                <td>{{ $client->phone }}</td>
                                <td>{{ $client->city ?: '—' }}</td>
                                <td><span class="badge text-bg-light">{{ $client->projects_count }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('clients.show', $client) }}" class="btn btn-sm btn-outline-secondary" title="عرض">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @can('clients.edit')
                                        <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-outline-primary" title="تعديل">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    @endcan
                                    @can('clients.delete')
                                        <form method="POST" action="{{ route('clients.destroy', $client) }}" class="d-inline"
                                              data-confirm="متأكد من حذف العميل؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">لا يوجد عملاء بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $clients->links() }}
        </div>
    </div>

    @can('clients.delete')
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
