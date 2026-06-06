@extends('layouts.app')

@section('title', $inspectionRequest->exists ? 'تعديل طلب فحص' : 'طلب فحص جديد')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $inspectionRequest->exists ? route('inspection_requests.update', $inspectionRequest) : route('inspection_requests.store') }}">
                @csrf
                @if ($inspectionRequest->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">رقم الطلب</label>
                        <input type="text" value="{{ $irNumber }}" class="form-control" disabled>
                        <div class="form-text">يُولّد تلقائياً</div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">المشروع <span class="text-danger">*</span></label>
                        <select name="project_id" class="form-select" required>
                            <option value="">— اختر المشروع —</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected((string) old('project_id', $inspectionRequest->project_id) === (string) $project->id)>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">العنوان <span class="text-danger">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $inspectionRequest->title) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">النوع <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            @foreach (\App\Models\InspectionRequest::TYPES as $key => $label)
                                <option value="{{ $key }}" @selected(old('type', $inspectionRequest->type) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">الموقع / المكان</label>
                        <input type="text" name="location" value="{{ old('location', $inspectionRequest->location) }}" class="form-control" placeholder="الدور / المحور / المنطقة">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الموعد المجدول</label>
                        <input type="date" name="scheduled_date" value="{{ old('scheduled_date', $inspectionRequest->scheduled_date?->format('Y-m-d')) }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="3" class="form-control">{{ old('notes', $inspectionRequest->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('inspection_requests.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
