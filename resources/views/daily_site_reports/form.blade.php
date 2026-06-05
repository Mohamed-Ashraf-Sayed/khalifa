@extends('layouts.app')

@section('title', $report->exists ? 'تعديل يومية الموقع' : 'يومية موقع جديدة')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $report->exists ? route('daily_site_reports.update', $report) : route('daily_site_reports.store') }}">
                @csrf
                @if ($report->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">المشروع <span class="text-danger">*</span></label>
                        <select name="project_id" class="form-select" required>
                            <option value="">— اختر المشروع —</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected((string) old('project_id', $report->project_id) === (string) $project->id)>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">التاريخ <span class="text-danger">*</span></label>
                        <input type="date" name="report_date" value="{{ old('report_date', $report->report_date?->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">عدد العمالة <span class="text-danger">*</span></label>
                        <input type="number" min="0" name="labor_count" value="{{ old('labor_count', $report->labor_count ?? 0) }}" class="form-control" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">الطقس</label>
                        <input type="text" name="weather" value="{{ old('weather', $report->weather) }}" class="form-control" placeholder="مثال: مشمس، حار، أمطار خفيفة">
                    </div>
                    <div class="col-12">
                        <label class="form-label">الأعمال المنفّذة</label>
                        <textarea name="work_done" rows="3" class="form-control">{{ old('work_done', $report->work_done) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">ملاحظات المعدات</label>
                        <textarea name="equipment_notes" rows="2" class="form-control">{{ old('equipment_notes', $report->equipment_notes) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">ملاحظات سير العمل / التقدّم</label>
                        <textarea name="progress_notes" rows="2" class="form-control">{{ old('progress_notes', $report->progress_notes) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">الحوادث / الملاحظات الطارئة</label>
                        <textarea name="incidents" rows="2" class="form-control">{{ old('incidents', $report->incidents) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('daily_site_reports.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
