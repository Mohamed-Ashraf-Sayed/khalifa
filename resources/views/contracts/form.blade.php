@extends('layouts.app')

@section('title', $contract->exists ? 'تعديل عقد' : 'عقد جديد')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $contract->exists ? route('contracts.update', $contract) : route('contracts.store') }}">
                @csrf
                @if ($contract->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">عنوان العقد <span class="text-danger">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $contract->title) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المشروع <span class="text-danger">*</span></label>
                        <select name="project_id" class="form-select" required>
                            <option value="">— اختر —</option>
                            @foreach ($projects as $p)
                                <option value="{{ $p->id }}" @selected((int) old('project_id', $contract->project_id) === $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">رقم العقد <span class="text-danger">*</span></label>
                        <input type="text" name="contract_number" value="{{ old('contract_number', $contract->contract_number) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">النوع</label>
                        <select name="contract_type" class="form-select">
                            @foreach (\App\Models\ProjectContract::TYPES as $key => $label)
                                <option value="{{ $key }}" @selected(old('contract_type', $contract->contract_type) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            @foreach (\App\Models\ProjectContract::STATUSES as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $contract->status) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الطرف الأول <span class="text-danger">*</span></label>
                        <input type="text" name="first_party" value="{{ old('first_party', $contract->first_party) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الطرف الثاني <span class="text-danger">*</span></label>
                        <input type="text" name="second_party" value="{{ old('second_party', $contract->second_party) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">قيمة العقد <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="contract_value" value="{{ old('contract_value', $contract->contract_value) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ التوقيع <span class="text-danger">*</span></label>
                        <input type="date" name="signing_date" value="{{ old('signing_date', $contract->signing_date?->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ البداية</label>
                        <input type="date" name="start_date" value="{{ old('start_date', $contract->start_date?->format('Y-m-d')) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ النهاية</label>
                        <input type="date" name="end_date" value="{{ old('end_date', $contract->end_date?->format('Y-m-d')) }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" rows="2" class="form-control">{{ old('description', $contract->description) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $contract->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('contracts.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
