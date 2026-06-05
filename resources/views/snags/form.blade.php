@extends('layouts.app')

@section('title', $snag->exists ? 'تعديل ملاحظة' : 'ملاحظة جديدة')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $snag->exists ? route('snags.update', $snag) : route('snags.store') }}">
                @csrf
                @if ($snag->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">المشروع <span class="text-danger">*</span></label>
                        <select name="project_id" class="form-select" required>
                            <option value="">— اختر المشروع —</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected((string) old('project_id', $snag->project_id) === (string) $project->id)>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">العنوان <span class="text-danger">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $snag->title) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الأولوية <span class="text-danger">*</span></label>
                        <select name="priority" class="form-select">
                            @foreach (\App\Models\Snag::PRIORITIES as $key => $label)
                                <option value="{{ $key }}" @selected(old('priority', $snag->priority) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الحالة <span class="text-danger">*</span></label>
                        <select name="status" class="form-select">
                            @foreach (\App\Models\Snag::STATUSES as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $snag->status) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المكان</label>
                        <input type="text" name="location" value="{{ old('location', $snag->location) }}" class="form-control" placeholder="مثال: الدور الثاني — شقة 5">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الموظف المسؤول</label>
                        <select name="assigned_employee_id" class="form-select">
                            <option value="">— بدون —</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected((string) old('assigned_employee_id', $snag->assigned_employee_id) === (string) $employee->id)>{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">جهة المسؤولية</label>
                        <input type="text" name="responsible" value="{{ old('responsible', $snag->responsible) }}" class="form-control" placeholder="مقاول / مورد">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">تاريخ الاستحقاق</label>
                        <input type="date" name="due_date" value="{{ old('due_date', $snag->due_date?->format('Y-m-d')) }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" rows="3" class="form-control">{{ old('description', $snag->description) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('snags.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
