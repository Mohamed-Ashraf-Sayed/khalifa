@extends('layouts.app')

@section('title', 'كشف حضور العمالة')

@section('content')
    {{-- اختيار المشروع والتاريخ (يُعيد تحميل قائمة العمال المرتبطين) --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('labor_attendances.create') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small">المشروع <span class="text-danger">*</span></label>
                    <select name="project_id" class="form-select" onchange="this.form.submit()">
                        @foreach ($projects as $p)
                            <option value="{{ $p->id }}" @selected($projectId == $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">التاريخ <span class="text-danger">*</span></label>
                    <input type="date" name="attendance_date" value="{{ $date }}" class="form-control" onchange="this.form.submit()">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-light w-100"><i class="fa-solid fa-rotate ms-1"></i> تحديث الكشف</button>
                </div>
            </form>
        </div>
    </div>

    <form method="POST" action="{{ route('labor_attendances.store') }}">
        @csrf
        <input type="hidden" name="project_id" value="{{ $projectId }}">
        <input type="hidden" name="attendance_date" value="{{ $date }}">

        {{-- العمالة المرتبطة بالمشروع --}}
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="mb-3"><i class="fa-solid fa-users ms-1" style="color:#2b4c80"></i> العمالة المخصّصة للمشروع</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:80px">حاضر</th>
                                <th>الموظف</th>
                                <th style="width:160px">الساعات</th>
                                <th style="width:180px">الأجر اليومي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($employees as $i => $emp)
                                <tr>
                                    <td>
                                        <input type="hidden" name="rows[{{ $i }}][employee_id]" value="{{ $emp->id }}">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="rows[{{ $i }}][present]" value="1" checked>
                                        </div>
                                    </td>
                                    <td class="fw-semibold">
                                        {{ $emp->name }}
                                        @if ($emp->job_title)<span class="text-muted small">· {{ $emp->job_title }}</span>@endif
                                    </td>
                                    <td><input type="number" step="0.5" min="0" name="rows[{{ $i }}][hours]" value="8" class="form-control form-control-sm"></td>
                                    <td><input type="number" step="0.01" min="0" name="rows[{{ $i }}][wage]" class="form-control form-control-sm" placeholder="اختياري"></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">لا يوجد عمال مخصّصون لهذا المشروع. أضف عاملاً يدوياً بالأسفل.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- عامل يدوي إضافي (ad-hoc) --}}
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="mb-3"><i class="fa-solid fa-user-plus ms-1" style="color:#2b4c80"></i> إضافة عامل يدوي (اختياري)</h6>
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small">اسم العامل</label>
                        <input type="text" name="laborer_name" value="{{ old('laborer_name') }}" class="form-control" placeholder="اسم العامل غير المسجّل">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">الساعات</label>
                        <input type="number" step="0.5" min="0" name="laborer_hours" value="{{ old('laborer_hours', 8) }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">الأجر اليومي</label>
                        <input type="number" step="0.01" min="0" name="laborer_wage" value="{{ old('laborer_wage') }}" class="form-control" placeholder="اختياري">
                    </div>
                    <div class="col-md-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="laborer_present" value="1" checked id="laborerPresent">
                            <label class="form-check-label small" for="laborerPresent">حاضر</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ الكشف</button>
            <a href="{{ route('labor_attendances.index') }}" class="btn btn-light">إلغاء</a>
        </div>
    </form>
@endsection
