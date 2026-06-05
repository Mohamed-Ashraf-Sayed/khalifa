@extends('layouts.app')

@section('title', 'حضور العمالة اليومي')

@section('content')
    <div class="row g-3 mb-3">
        @foreach ([
            ['عدد الحاضرين', number_format($summary['present']), 'fa-user-check', 'text-success'],
            ['إجمالي الساعات', number_format($summary['hours'], 1), 'fa-clock', 'text-primary'],
            ['إجمالي الأجور', number_format($summary['wage'], 0), 'fa-sack-dollar', 'text-secondary'],
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
                <form class="d-flex gap-2 flex-wrap" method="GET">
                    <select name="project_id" class="form-select" style="min-width:180px" onchange="this.form.submit()">
                        <option value="">كل المشاريع</option>
                        @foreach ($projects as $p)
                            <option value="{{ $p->id }}" @selected($projectId == $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="attendance_date" value="{{ $date }}" class="form-control" style="min-width:150px" placeholder="يوم محدد">
                    <input type="date" name="from" value="{{ $from }}" class="form-control" style="min-width:150px" title="من تاريخ">
                    <input type="date" name="to" value="{{ $to }}" class="form-control" style="min-width:150px" title="إلى تاريخ">
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                <div class="d-flex gap-2">
                    @can('projects.create')
                        <a href="{{ route('labor_attendances.create') }}" class="btn" style="background:#8b7355;color:#fff">
                            <i class="fa-solid fa-plus ms-1"></i> كشف حضور جديد
                        </a>
                    @endcan
                </div>
            </div>

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
                                        <span class="badge text-bg-secondary">غائب</span>
                                    @endif
                                </td>
                                <td>{{ rtrim(rtrim(number_format($att->hours, 2), '0'), '.') }}</td>
                                <td>{{ $att->wage !== null ? number_format((float) $att->wage, 2) : '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('labor_attendances.show', $att) }}" class="btn btn-sm btn-outline-secondary" title="عرض">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @can('projects.delete')
                                        <form method="POST" action="{{ route('labor_attendances.destroy', $att) }}" class="d-inline"
                                              onsubmit="return confirm('متأكد من حذف سجل الحضور؟')">
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
