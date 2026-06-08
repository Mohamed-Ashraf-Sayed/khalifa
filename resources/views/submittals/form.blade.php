@extends('layouts.app')

@section('title', $submittal->exists ? 'تعديل اعتماد فني' : 'اعتماد فني جديد')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $submittal->exists ? route('submittals.update', $submittal) : route('submittals.store') }}">
                @csrf
                @if ($submittal->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">رقم الاعتماد</label>
                        <input type="text" value="{{ $submittalNumber }}" class="form-control" disabled>
                        <div class="form-text">يُولّد تلقائياً</div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">المشروع <span class="text-danger">*</span></label>
                        <select name="project_id" class="form-select" required>
                            <option value="">— اختر المشروع —</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected((string) old('project_id', $submittal->project_id) === (string) $project->id)>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">العنوان <span class="text-danger">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $submittal->title) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">النوع <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            @foreach (\App\Models\Submittal::TYPES as $key => $label)
                                <option value="{{ $key }}" @selected(old('type', $submittal->type) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">بند المواصفات</label>
                        <input type="text" name="spec_section" value="{{ old('spec_section', $submittal->spec_section) }}" class="form-control" placeholder="مثال: 03 30 00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">موجَّه إلى</label>
                        <input type="text" name="submitted_to" value="{{ old('submitted_to', $submittal->submitted_to) }}" class="form-control" placeholder="الاستشاري / المهندس">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">موعد المراجعة المستهدف</label>
                        <input type="date" name="due_date" value="{{ old('due_date', $submittal->due_date?->format('Y-m-d')) }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" rows="4" class="form-control">{{ old('description', $submittal->description) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('submittals.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
