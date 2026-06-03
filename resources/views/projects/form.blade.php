@extends('layouts.app')

@section('title', $project->exists ? 'تعديل مشروع' : 'مشروع جديد')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $project->exists ? route('projects.update', $project) : route('projects.store') }}">
                @csrf
                @if ($project->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">اسم المشروع <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $project->name) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">العميل <span class="text-danger">*</span></label>
                        <select name="client_id" class="form-select" required>
                            <option value="">— اختر —</option>
                            @foreach ($clients as $c)
                                <option value="{{ $c->id }}" @selected((int) old('client_id', $project->client_id) === $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">النوع</label>
                        <select name="project_type" class="form-select">
                            @foreach (\App\Models\Project::TYPES as $key => $label)
                                <option value="{{ $key }}" @selected(old('project_type', $project->project_type) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            @foreach (\App\Models\Project::STATUSES as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $project->status) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">قيمة العقد <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="contract_value" value="{{ old('contract_value', $project->contract_value) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ البداية <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ النهاية</label>
                        <input type="date" name="end_date" value="{{ old('end_date', $project->end_date?->format('Y-m-d')) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المدير المسؤول</label>
                        <select name="manager_id" class="form-select">
                            <option value="">— بدون —</option>
                            @foreach ($managers as $m)
                                <option value="{{ $m->id }}" @selected((int) old('manager_id', $project->manager_id) === $m->id)>{{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">الموقع</label>
                        <input type="text" name="location" value="{{ old('location', $project->location) }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" rows="2" class="form-control">{{ old('description', $project->description) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $project->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('projects.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
