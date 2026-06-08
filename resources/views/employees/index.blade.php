@extends('layouts.app')

@section('title', 'الموظفون')

@section('content')
    <div class="row g-3 mb-3">
        @foreach ([
            ['عدد الموظفين', number_format($stats['count']), 'fa-id-badge', 'text-primary'],
            ['الموظفون النشطون', number_format($stats['active']), 'fa-user-check', 'text-success'],
            ['إجمالي الرواتب الشهرية', number_format((float) $stats['salaries'], 2).' ج', 'fa-money-check-dollar', 'text-info'],
            ['السلف المستحقة', number_format((float) $stats['advances'], 2).' ج', 'fa-hand-holding-dollar', 'text-warning'],
        ] as [$label, $val, $icon, $color])
            <div class="col-md-3 col-6">
                <div class="card h-100"><div class="card-body py-3">
                    <i class="fa-solid {{ $icon }} {{ $color }}"></i>
                    <div class="fs-4 fw-bold">{{ $val }}</div>
                    <div class="small text-muted">{{ $label }}</div>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2" method="GET">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بحث بالاسم أو الكود أو المسمى الوظيفي">
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                @can('employees.create')
                    <a href="{{ route('employees.create') }}" class="btn btn-brown" style="background:#2b4c80;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> موظف جديد
                    </a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>الكود</th>
                            <th>الاسم</th>
                            <th>المسمى الوظيفي</th>
                            <th>القسم</th>
                            <th>الراتب</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employees as $employee)
                            <tr>
                                <td class="fw-semibold">{{ $employee->employee_code }}</td>
                                <td>{{ $employee->name }}</td>
                                <td>{{ $employee->job_title }}</td>
                                <td>{{ $employee->department ?: '—' }}</td>
                                <td>{{ number_format($employee->salary, 2) }} ج</td>
                                <td>
                                    @if ($employee->is_active)
                                        <span class="badge text-bg-success">نشط</span>
                                    @else
                                        <span class="badge text-bg-secondary">غير نشط</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-outline-secondary" title="عرض">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @can('employees.edit')
                                        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    @endcan
                                    @can('employees.delete')
                                        <form method="POST" action="{{ route('employees.destroy', $employee) }}" class="d-inline"
                                              data-confirm="متأكد من حذف الموظف؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">لا يوجد موظفون بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $employees->links() }}
        </div>
    </div>
@endsection
