@extends('layouts.app')

@section('title', 'المصروفات')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="statcard sc-danger h-100"><span class="sc-ic"><i class="fa-solid fa-money-bill-trend-up"></i></span><span><span class="sc-v d-block">{{ number_format($total, 2) }} ج</span><span class="sc-l d-block">إجمالي المصروفات{{ $category ? ' (' . ($categories[$category] ?? $category) . ')' : '' }}</span></span></div>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <form method="GET" class="filter-bar row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label">بحث (البيان / المستلِم)</label>
                <input type="text" name="search" value="{{ $search }}" class="form-control">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">الفئة</label>
                <select name="category" class="form-select">
                    <option value="">كل الفئات</option>
                    @foreach ($categories as $k => $label)
                        <option value="{{ $k }}" @selected($category === (string) $k)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
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
    </div></div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end gap-2 mb-3">
                @can('expenses.create')
                    <a href="{{ route('expenses.create') }}" class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-plus ms-1"></i> مصروف جديد</a>
                @endcan
            </div>

            @can('expenses.delete')
                <form id="bulk-form" method="POST" action="{{ route('expenses.bulk_destroy') }}" data-confirm="حذف المصروفات المحددة؟">
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
                            @can('expenses.delete')<th style="width:1%"><input type="checkbox" id="bulk-select-all" class="form-check-input"></th>@endcan
                            <th>التاريخ</th>
                            <th>البيان</th>
                            <th>الفئة</th>
                            <th>المشروع</th>
                            <th>المبلغ</th>
                            <th>الدفع</th>
                            <th>حالة الدفع</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($expenses as $expense)
                            <tr>
                                @can('expenses.delete')<td><input type="checkbox" form="bulk-form" name="ids[]" value="{{ $expense->id }}" class="form-check-input bulk-item"></td>@endcan
                                <td>{{ $expense->expense_date->format('Y-m-d') }}</td>
                                <td class="fw-semibold">{{ $expense->description }}</td>
                                <td><span class="badge text-bg-light">{{ $categories[$expense->category] ?? $expense->category }}</span></td>
                                <td>{{ $expense->project?->name ?? '—' }}</td>
                                <td class="fw-bold text-danger">{{ number_format($expense->amount, 2) }}</td>
                                <td>
                                    {{ \App\Models\Expense::PAYMENT_METHODS[$expense->payment_method] ?? $expense->payment_method }}
                                    @if ($expense->bankAccount)<i class="fa-solid fa-building-columns text-muted small" title="{{ $expense->bankAccount->name }}"></i>@endif
                                    @if ($expense->is_credit)<i class="fa-solid fa-clock text-warning small" title="مصروف آجل"></i>@endif
                                </td>
                                <td>
                                    @php($pst = $expense->payment_status)
                                    <span class="badge {{ $pst === 'paid' ? 'text-bg-success' : ($pst === 'partial' ? 'text-bg-warning' : 'text-bg-danger') }}">
                                        {{ \App\Models\Expense::PAYMENT_STATUSES[$pst] ?? $pst }}
                                    </span>
                                    @if ($expense->is_credit && $pst !== 'paid' && $expense->due_date && $expense->due_date->isPast())
                                        <span class="badge text-bg-danger"><i class="fa-solid fa-triangle-exclamation ms-1"></i> متأخر</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('expenses.show', $expense) }}" class="btn btn-sm btn-outline-secondary" title="عرض"><i class="fa-solid fa-eye"></i></a>
                                    @can('expenses.edit')
                                        <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('expenses.delete')
                                        <form method="POST" action="{{ route('expenses.destroy', $expense) }}" class="d-inline" data-confirm="حذف المصروف؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">لا توجد مصروفات بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $expenses->links() }}
        </div>
    </div>

    @can('expenses.delete')
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
