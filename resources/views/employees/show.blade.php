@extends('layouts.app')

@section('title', 'بيانات الموظف')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">{{ $employee->name }}</h5>
        <div class="d-flex gap-2">
            <a href="{{ route('employees.statement', $employee) }}" class="btn btn-sm" style="background:#8b7355;color:#fff"><i class="fa-solid fa-file-invoice ms-1"></i> كشف حساب</a>
            @can('employees.edit')
                <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
            @endcan
            <a href="{{ route('employees.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">الكود</div><div class="fw-semibold">{{ $employee->employee_code }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الاسم</div><div class="fw-semibold">{{ $employee->name }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الرقم القومي</div><div dir="ltr" class="text-end">{{ $employee->national_id ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">المسمى الوظيفي</div><div>{{ $employee->job_title }}</div></div>
                <div class="col-md-4"><div class="text-muted small">القسم</div><div>{{ $employee->department ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الراتب</div><div class="fw-semibold">{{ number_format($employee->salary, 2) }} ج</div></div>
                <div class="col-md-4"><div class="text-muted small">الحساب البنكي لصرف الراتب</div><div>{{ $employee->bankAccount?->name ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الهاتف</div><div dir="ltr" class="text-end">{{ $employee->phone ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">البريد</div><div dir="ltr" class="text-end">{{ $employee->email ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ التعيين</div><div>{{ $employee->hire_date ? $employee->hire_date->format('Y-m-d') : '—' }}</div></div>
                <div class="col-md-4">
                    <div class="text-muted small">الحالة</div>
                    <div>
                        @if ($employee->is_active)
                            <span class="badge text-bg-success">نشط</span>
                        @else
                            <span class="badge text-bg-secondary">غير نشط</span>
                        @endif
                    </div>
                </div>
                @if ($employee->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $employee->notes }}</div></div>@endif
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">رصيد السلف المستحقّ</div>
                    <div class="fw-semibold fs-5">{{ number_format($employee->advanceBalance(), 2) }} ج</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">رصيد العهدة في يد الموظف</div>
                    <div class="fw-semibold fs-5">{{ number_format($employee->custodyBalance(), 2) }} ج</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">حركات الموظف <span class="badge text-bg-light">{{ $employee->transactions->count() }}</span></h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>النوع</th><th>المبلغ</th><th>التاريخ</th><th>البيان</th></tr></thead>
                    <tbody>
                        @forelse ($employee->transactions as $t)
                            <tr>
                                <td><span class="badge text-bg-light">{{ \App\Models\EmployeeTransaction::TYPES[$t->type] ?? $t->type }}</span></td>
                                <td>{{ number_format($t->amount, 2) }}</td>
                                <td>{{ $t->transaction_date ? $t->transaction_date->format('Y-m-d') : '—' }}</td>
                                <td>{{ $t->description ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">لا توجد حركات لهذا الموظف.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
