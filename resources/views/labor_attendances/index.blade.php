@extends('layouts.app')

@section('title', 'حضور العمالة اليومي')

@section('content')
    <div class="row g-3 mb-3">
        @foreach ([
            ['عدد الحاضرين', number_format($summary['present']), 'fa-user-check', 'text-success'],
            ['عدد الغائبين', number_format($summary['absent']), 'fa-user-xmark', 'text-danger'],
            ['إجمالي الساعات', number_format($summary['hours'], 1), 'fa-clock', 'text-primary'],
            ['إجمالي الأجور', number_format($summary['wage'], 0).' ج', 'fa-sack-dollar', 'text-secondary'],
        ] as [$l, $v, $icon, $color])
        <div class="col-md-3 col-6"><div class="card h-100"><div class="card-body py-3">
            <i class="fa-solid {{ $icon }} {{ $color }}"></i>
            <div class="fs-4 fw-bold">{{ $v }}</div>
            <div class="small text-muted">{{ $l }}</div>
        </div></div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end mb-3">
                @can('projects.create')
                    <a href="{{ route('labor_attendances.create') }}" class="btn" style="background:#2b4c80;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> كشف حضور جديد
                    </a>
                @endcan
            </div>

            <form method="GET" class="filter-bar row g-2 align-items-end mb-3">
                <div class="col-6 col-md-3">
                    <label class="form-label">المشروع</label>
                    <select name="project_id" class="form-select" onchange="this.form.submit()">
                        <option value="">كل المشاريع</option>
                        @foreach ($projects as $p)
                            <option value="{{ $p->id }}" @selected($projectId == $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">يوم محدد</label>
                    <input type="date" name="attendance_date" value="{{ $date }}" class="form-control">
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

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>التاريخ</th>
                            <th>المشروع</th>
                            <th>العامل</th>
                            <th>الحضور</th>
                            <th>الساعات</th>
                            <th>الأجر</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($attendances as $att)
                            <tr>
                                <td class="fw-semibold">{{ $att->attendance_date?->format('Y-m-d') }}</td>
                                <td>{{ $att->project?->name ?? '—' }}</td>
                                <td>
                                    {{ $att->displayName() }}
                                    @if (! $att->employee_id)<span class="badge text-bg-light ms-1">يدوي</span>@endif
                                </td>
                                <td>
                                    @if ($att->present)
                                        <span class="badge text-bg-success">حاضر</span>
                                    @else
                                        <span class="badge text-bg-danger">غائب</span>
                                    @endif
                                </td>
                                <td>{{ $att->present ? rtrim(rtrim(number_format($att->hours, 2), '0'), '.') : '—' }}</td>
                                <td>{{ $att->present && $att->wage !== null ? number_format((float) $att->wage, 2) : '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('labor_attendances.show', $att) }}" class="btn btn-sm btn-outline-secondary" title="عرض">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @can('projects.edit')
                                        <a href="{{ route('labor_attendances.edit', $att) }}" class="btn btn-sm btn-outline-primary" title="تعديل">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    @endcan
                                    @can('projects.delete')
                                        <form method="POST" action="{{ route('labor_attendances.destroy', $att) }}" class="d-inline"
                                              data-confirm="متأكد من حذف سجل الحضور؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">لا توجد سجلات حضور.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $attendances->links() }}
        </div>
    </div>
@endsection
