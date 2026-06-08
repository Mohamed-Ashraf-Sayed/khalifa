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

    @if ($existingByEmployee->isNotEmpty() || $existingLaborers->isNotEmpty())
        <div class="alert alert-info d-flex align-items-center gap-2">
            <i class="fa-solid fa-circle-info"></i>
            <span>يوجد كشف محفوظ لهذا المشروع في هذا اليوم — البيانات معبّأة مسبقاً، أي تعديل وحفظ سيُحدّث الكشف الحالي بدل تكراره.</span>
        </div>
    @endif

    <form method="POST" action="{{ route('labor_attendances.store') }}">
        @csrf
        <input type="hidden" name="project_id" value="{{ $projectId }}">
        <input type="hidden" name="attendance_date" value="{{ $date }}">

        {{-- العمالة المرتبطة بالمشروع --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h6 class="mb-0"><i class="fa-solid fa-users ms-1" style="color:#2b4c80"></i> العمالة المخصّصة للمشروع</h6>
                    @if ($employees->isNotEmpty())
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="toggleAll" checked>
                            <label class="form-check-label small" for="toggleAll">تحديد/إلغاء الكل (حاضر)</label>
                        </div>
                    @endif
                </div>
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
                                @php($rec = $existingByEmployee[$emp->id] ?? null)
                                <tr>
                                    <td>
                                        <input type="hidden" name="rows[{{ $i }}][employee_id]" value="{{ $emp->id }}">
                                        <div class="form-check">
                                            <input class="form-check-input present-check" type="checkbox" name="rows[{{ $i }}][present]" value="1" @checked($rec ? $rec->present : true)>
                                        </div>
                                    </td>
                                    <td class="fw-semibold">
                                        {{ $emp->name }}
                                        @if ($emp->job_title)<span class="text-muted small">· {{ $emp->job_title }}</span>@endif
                                    </td>
                                    <td><input type="number" step="0.5" min="0" name="rows[{{ $i }}][hours]" value="{{ $rec && $rec->present ? rtrim(rtrim(number_format($rec->hours, 2), '0'), '.') : 8 }}" class="form-control form-control-sm"></td>
                                    <td><input type="number" step="0.01" min="0" name="rows[{{ $i }}][wage]" value="{{ $rec && $rec->wage !== null ? $rec->wage : '' }}" class="form-control form-control-sm" placeholder="اختياري"></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">لا يوجد عمال مخصّصون لهذا المشروع. أضف عاملاً يدوياً بالأسفل.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="small text-muted mt-2">
                    <i class="fa-solid fa-lightbulb ms-1"></i> ألغِ علامة «حاضر» لتسجيل العامل <strong>غائباً</strong> — الغياب يُحفظ ويظهر في الكشف.
                </div>
            </div>
        </div>

        {{-- العمال اليدويون المسجّلون مسبقاً لهذا اليوم --}}
        @if ($existingLaborers->isNotEmpty())
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fa-solid fa-user-clock ms-1" style="color:#2b4c80"></i> عمّال يدويون مسجّلون لهذا اليوم</h6>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($existingLaborers as $lab)
                            <span class="badge text-bg-light border p-2">
                                {{ $lab->laborer_name }} —
                                @if ($lab->present)<span class="text-success">حاضر</span> · {{ rtrim(rtrim(number_format($lab->hours, 2), '0'), '.') }} س@else<span class="text-danger">غائب</span>@endif
                                <a href="{{ route('labor_attendances.edit', $lab) }}" class="ms-1"><i class="fa-solid fa-pen small"></i></a>
                            </span>
                        @endforeach
                    </div>
                    <div class="small text-muted mt-2">لتعديل عامل يدوي مسجّل اضغط على القلم، أو أضِف عاملاً جديداً بالأسفل.</div>
                </div>
            </div>
        @endif

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

    <script>
        (function () {
            const toggle = document.getElementById('toggleAll');
            if (!toggle) return;
            const checks = Array.from(document.querySelectorAll('.present-check'));
            toggle.addEventListener('change', () => checks.forEach(c => { c.checked = toggle.checked; }));
            checks.forEach(c => c.addEventListener('change', () => {
                toggle.checked = checks.length > 0 && checks.every(x => x.checked);
            }));
        })();
    </script>
@endsection
