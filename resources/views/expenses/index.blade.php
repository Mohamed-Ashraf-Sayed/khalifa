@extends('layouts.app')

@section('title', 'المصروفات')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <div class="text-muted small">إجمالي المصروفات{{ $category ? ' (' . (\App\Models\Expense::CATEGORIES[$category] ?? '') . ')' : '' }}</div>
                <div class="fs-4 fw-bold text-danger">{{ number_format($total, 2) }} ج</div>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form method="GET">
                    <select name="category" class="form-select" style="min-width:180px" onchange="this.form.submit()">
                        <option value="">كل الفئات</option>
                        @foreach (\App\Models\Expense::CATEGORIES as $k => $label)
                            <option value="{{ $k }}" @selected($category === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
                @can('expenses.create')
                    <a href="{{ route('expenses.create') }}" class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-plus ms-1"></i> مصروف جديد</a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
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
                                <td>{{ $expense->expense_date->format('Y-m-d') }}</td>
                                <td class="fw-semibold">{{ $expense->description }}</td>
                                <td><span class="badge text-bg-light">{{ \App\Models\Expense::CATEGORIES[$expense->category] ?? $expense->category }}</span></td>
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
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('expenses.show', $expense) }}" class="btn btn-sm btn-outline-secondary" title="عرض"><i class="fa-solid fa-eye"></i></a>
                                    @can('expenses.edit')
                                        <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('expenses.delete')
                                        <form method="POST" action="{{ route('expenses.destroy', $expense) }}" class="d-inline" onsubmit="return confirm('حذف المصروف؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">لا توجد مصروفات بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $expenses->links() }}
        </div>
    </div>
@endsection
