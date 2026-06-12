@extends('layouts.app')

@section('title', 'الإيرادات')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="statcard sc-success h-100"><span class="sc-ic"><i class="fa-solid fa-sack-dollar"></i></span><span><span class="sc-v d-block">{{ number_format((float) $stats['total'], 2) }} ج</span><span class="sc-l d-block">إجمالي الإيرادات</span></span></div>
        </div>
        <div class="col-md-3">
            <div class="statcard sc-success h-100"><span class="sc-ic"><i class="fa-solid fa-circle-check"></i></span><span><span class="sc-v d-block">{{ number_format((float) $stats['collected'], 2) }} ج</span><span class="sc-l d-block">إجمالي المحصّل</span></span></div>
        </div>
        <div class="col-md-3">
            <div class="statcard sc-warning h-100"><span class="sc-ic"><i class="fa-solid fa-hourglass-half"></i></span><span><span class="sc-v d-block">{{ number_format((float) $stats['remaining'], 2) }} ج</span><span class="sc-l d-block">المتبقّي</span></span></div>
        </div>
        <div class="col-md-3">
            <div class="statcard sc-primary h-100"><span class="sc-ic"><i class="fa-solid fa-sack-dollar"></i></span><span><span class="sc-v d-block">{{ $stats['count'] }}</span><span class="sc-l d-block">عدد الإيرادات</span></span></div>
        </div>
    </div>

    <form method="GET" class="filter-bar row g-2 align-items-end mb-3">
        <div class="col-6 col-md-3">
            <label class="form-label">المشروع</label>
            <select name="project_id" class="form-select">
                <option value="">كل المشاريع</option>
                @foreach ($projects as $p)
                    <option value="{{ $p->id }}" @selected($projectId === (string) $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label">حالة التحصيل</label>
            <select name="payment_status" class="form-select">
                <option value="">كل الحالات</option>
                @foreach (\App\Models\Revenue::PAYMENT_STATUSES as $k => $label)
                    <option value="{{ $k }}" @selected($paymentStatus === $k)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label">من تاريخ</label>
            <input type="date" name="from" value="{{ $from }}" class="form-control">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label">إلى تاريخ</label>
            <input type="date" name="to" value="{{ $to }}" class="form-control">
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

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end gap-2 mb-3">
                <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-success"><i class="fa-solid fa-file-csv ms-1"></i> تصدير CSV</a>
                @can('revenues.create')
                    <a href="{{ route('revenues.create') }}" class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-plus ms-1"></i> إيراد جديد</a>
                @endcan
            </div>

            @can('revenues.delete')
                <form id="bulk-form" method="POST" action="{{ route('revenues.bulk_destroy') }}" data-confirm="حذف الإيرادات المحددة؟">
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
                            @can('revenues.delete')<th style="width:1%"><input type="checkbox" id="bulk-select-all" class="form-check-input"></th>@endcan
                            <th>التاريخ</th>
                            <th>البيان</th>
                            <th>المشروع</th>
                            <th>المبلغ</th>
                            <th>المحصّل</th>
                            <th>حالة التحصيل</th>
                            <th>الاستلام</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($revenues as $revenue)
                            <tr>
                                @can('revenues.delete')<td><input type="checkbox" form="bulk-form" name="ids[]" value="{{ $revenue->id }}" class="form-check-input bulk-item"></td>@endcan
                                <td>{{ $revenue->revenue_date->format('Y-m-d') }}</td>
                                <td class="fw-semibold">{{ $revenue->description }}</td>
                                <td>{{ $revenue->project?->name ?? '—' }}</td>
                                <td class="fw-bold text-success">{{ number_format($revenue->amount, 2) }}</td>
                                <td>{{ number_format($revenue->paid_amount, 2) }}</td>
                                <td>
                                    @php $sc = ['pending' => 'secondary', 'partial' => 'warning', 'collected' => 'success']; @endphp
                                    <span class="badge bg-{{ $sc[$revenue->payment_status] ?? 'secondary' }}">{{ \App\Models\Revenue::PAYMENT_STATUSES[$revenue->payment_status] ?? $revenue->payment_status }}</span>
                                    @if ($revenue->is_confirmed)
                                        <span class="badge bg-success">مؤكد</span>
                                    @else
                                        <span class="badge bg-secondary">قيد التأكيد</span>
                                    @endif
                                </td>
                                <td>
                                    {{ \App\Models\Revenue::PAYMENT_METHODS[$revenue->payment_method] ?? $revenue->payment_method }}
                                    @if ($revenue->bankAccount)<i class="fa-solid fa-building-columns text-muted small" title="{{ $revenue->bankAccount->name }}"></i>@endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('revenues.show', $revenue) }}" class="btn btn-sm btn-outline-secondary" title="عرض"><i class="fa-solid fa-eye"></i></a>
                                    @can('revenues.edit')
                                        <a href="{{ route('revenues.edit', $revenue) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('revenues.delete')
                                        <form method="POST" action="{{ route('revenues.destroy', $revenue) }}" class="d-inline" data-confirm="حذف الإيراد؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">لا توجد إيرادات بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $revenues->links() }}
        </div>
    </div>

    @can('revenues.delete')
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
