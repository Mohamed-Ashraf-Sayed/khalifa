@extends('layouts.app')

@section('title', 'الموظفون')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2" method="GET">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بحث بالاسم أو الكود أو المسمى الوظيفي">
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                @can('employees.create')
                    <a href="{{ route('employees.create') }}" class="btn btn-brown" style="background:#8b7355;color:#fff">
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
                                              onsubmit="return confirm('متأكد من حذف الموظف؟')">
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
