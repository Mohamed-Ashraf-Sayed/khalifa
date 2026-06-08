@extends('layouts.app')

@section('title', 'سجل حضور: ' . $attendance->displayName())

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
        @can('projects.edit')
            <a href="{{ route('labor_attendances.edit', $attendance) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
        @endcan
        @can('projects.delete')
            <form method="POST" action="{{ route('labor_attendances.destroy', $attendance) }}" class="d-inline" data-confirm="متأكد من حذف سجل الحضور؟">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash ms-1"></i> حذف</button>
            </form>
        @endcan
        <a href="{{ route('labor_attendances.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
                <span class="entity-avatar"><i class="fa-solid fa-user-clock"></i></span>
                <div class="flex-grow-1">
                    <div class="h5 mb-1">{{ $attendance->displayName() }}</div>
                    <div class="text-muted small">{{ $attendance->project?->name ?? '—' }} · {{ $attendance->attendance_date?->format('Y-m-d') }}</div>
                    <div class="mt-2">
                        @if ($attendance->present)
                            <span class="badge text-bg-success">حاضر</span>
                        @else
                            <span class="badge text-bg-danger">غائب</span>
                        @endif
                        @if (! $attendance->employee_id)<span class="badge text-bg-light ms-1">عامل يدوي</span>@endif
                    </div>
                </div>
            </div></div>
        </div>
        <div class="col-lg-7">
            <div class="row g-3 h-100">
                <div class="col-6"><div class="stat-box"><div class="sl">عدد الساعات</div><div class="sv">{{ $attendance->present ? rtrim(rtrim(number_format($attendance->hours, 2), '0'), '.') : '—' }}</div></div></div>
                <div class="col-6"><div class="stat-box accent"><div class="sl">الأجر اليومي</div><div class="sv">{{ $attendance->present && $attendance->wage !== null ? number_format((float) $attendance->wage, 2) : '—' }}</div></div></div>
            </div>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <h6 class="mb-3"><i class="fa-solid fa-circle-info ms-1" style="color:#2b4c80"></i> بيانات السجل</h6>
        <div class="info-list">
            <div class="il"><span class="k">المشروع</span><span class="v">{{ $attendance->project?->name ?? '—' }}</span></div>
            <div class="il"><span class="k">التاريخ</span><span class="v">{{ $attendance->attendance_date?->format('Y-m-d') }}</span></div>
            <div class="il"><span class="k">الموظف</span><span class="v">{{ $attendance->employee?->name ?? '—' }}</span></div>
            <div class="il"><span class="k">سجّله</span><span class="v">{{ $attendance->creator?->name ?? '—' }}</span></div>
            @if ($attendance->notes)<div class="il" style="grid-column:1/-1"><span class="k">ملاحظات</span><span class="v">{{ $attendance->notes }}</span></div>@endif
        </div>
    </div></div>
@endsection
