@extends('layouts.app')

@section('title', 'تعديل حضور: ' . $attendance->displayName())

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
        <a href="{{ route('labor_attendances.show', $attendance) }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-4">
                <span class="entity-avatar"><i class="fa-solid fa-user-clock"></i></span>
                <div>
                    <div class="h5 mb-1">{{ $attendance->displayName() }}</div>
                    <div class="text-muted small">{{ $attendance->project?->name ?? '—' }} · {{ $attendance->attendance_date?->format('Y-m-d') }}</div>
                </div>
            </div>

            <form method="POST" action="{{ route('labor_attendances.update', $attendance) }}">
                @csrf @method('PUT')

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="present" value="1" id="presentSwitch"
                           @checked(old('present', $attendance->present)) onchange="document.getElementById('workFields').style.display=this.checked?'':'none'">
                    <label class="form-check-label" for="presentSwitch">حاضر</label>
                </div>

                <div id="workFields" class="row g-3" style="{{ old('present', $attendance->present) ? '' : 'display:none' }}">
                    <div class="col-md-6">
                        <label class="form-label small">عدد الساعات</label>
                        <input type="number" step="0.5" min="0" name="hours" value="{{ old('hours', rtrim(rtrim(number_format($attendance->hours, 2), '0'), '.')) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">الأجر اليومي</label>
                        <input type="number" step="0.01" min="0" name="wage" value="{{ old('wage', $attendance->wage) }}" class="form-control" placeholder="اختياري">
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label small">ملاحظات</label>
                    <textarea name="notes" rows="2" class="form-control" placeholder="اختياري">{{ old('notes', $attendance->notes) }}</textarea>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ التعديل</button>
                    <a href="{{ route('labor_attendances.show', $attendance) }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
