@extends('layouts.app')

@section('title', $policy->exists ? 'تعديل وثيقة تأمين' : 'وثيقة تأمين جديدة')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $policy->exists ? route('insurance.update', $policy) : route('insurance.store') }}">
                @csrf
                @if ($policy->exists) @method('PUT') @endif

                <div class="row g-3">
                    @if ($policy->exists)
                        <div class="col-md-4">
                            <label class="form-label">رقم الوثيقة</label>
                            <input type="text" value="{{ $policy->policy_number }}" class="form-control" disabled>
                        </div>
                    @endif
                    <div class="col-md-4">
                        <label class="form-label">النوع <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            @foreach (\App\Models\InsurancePolicy::TYPES as $key => $label)
                                <option value="{{ $key }}" @selected(old('type', $policy->type) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            @foreach (\App\Models\InsurancePolicy::STATUSES as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $policy->status) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">جهة التأمين <span class="text-danger">*</span></label>
                        <input type="text" name="provider" value="{{ old('provider', $policy->provider) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">المشروع المرتبط</label>
                        <select name="project_id" class="form-select">
                            <option value="">— بدون —</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected((string) old('project_id', $policy->project_id) === (string) $project->id)>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">مبلغ التغطية <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="coverage_amount" value="{{ old('coverage_amount', $policy->coverage_amount) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">قسط التأمين</label>
                        <input type="number" step="0.01" min="0" name="premium" value="{{ old('premium', $policy->premium) }}" class="form-control">
                    </div>
                    <div class="col-md-4"></div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ البدء <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" value="{{ old('start_date', $policy->start_date?->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ الانتهاء <span class="text-danger">*</span></label>
                        <input type="date" name="expiry_date" value="{{ old('expiry_date', $policy->expiry_date?->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $policy->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('insurance.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
