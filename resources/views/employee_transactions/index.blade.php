@extends('layouts.app')

@section('title', 'معاملات الموظفين')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form method="GET" class="d-flex gap-2 flex-wrap">
                    <select name="type" class="form-select" style="min-width:180px" onchange="this.form.submit()">
                        <option value="">كل الأنواع</option>
                        @foreach (\App\Models\EmployeeTransaction::TYPES as $k => $label)
                            <option value="{{ $k }}" @selected($type === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" style="min-width:180px" placeholder="بحث بالبيان">
                    <button class="btn btn-light"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                @can('employees.create')
                    <a href="{{ route('employee_transactions.create') }}" class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-plus ms-1"></i> معاملة جديدة</a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>التاريخ</th>
                            <th>الموظف</th>
                            <th>النوع</th>
                            <th>المبلغ</th>
                            <th>المشروع</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->transaction_date->format('Y-m-d') }}</td>
                                <td class="fw-semibold">{{ $transaction->employee?->name ?? '—' }}</td>
                                <td><span class="badge text-bg-light">{{ \App\Models\EmployeeTransaction::TYPES[$transaction->type] ?? $transaction->type }}</span></td>
                                <td class="fw-bold">{{ number_format($transaction->amount, 2) }}</td>
                                <td>{{ $transaction->project?->name ?? '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('employee_transactions.show', $transaction) }}" class="btn btn-sm btn-outline-secondary" title="عرض"><i class="fa-solid fa-eye"></i></a>
                                    @can('employees.edit')
                                        <a href="{{ route('employee_transactions.edit', $transaction) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('employees.delete')
                                        <form method="POST" action="{{ route('employee_transactions.destroy', $transaction) }}" class="d-inline" data-confirm="حذف المعاملة؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">لا توجد معاملات بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $transactions->links() }}
        </div>
    </div>
@endsection
