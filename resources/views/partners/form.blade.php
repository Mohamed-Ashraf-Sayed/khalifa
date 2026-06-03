@extends('layouts.app')

@section('title', $partner->exists ? 'تعديل شريك' : 'شريك جديد')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $partner->exists ? route('partners.update', $partner) : route('partners.store') }}">
                @csrf
                @if ($partner->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">الاسم <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $partner->name) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الهاتف</label>
                        <input type="text" name="phone" value="{{ old('phone', $partner->phone) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" name="email" value="{{ old('email', $partner->email) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الرقم القومي</label>
                        <input type="text" name="national_id" value="{{ old('national_id', $partner->national_id) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ الانضمام <span class="text-danger">*</span></label>
                        <input type="date" name="join_date" value="{{ old('join_date', optional($partner->join_date)->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الحالة <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            @foreach (\App\Models\Partner::STATUSES as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $partner->status ?: 'active') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">المشروع <span class="text-muted small">(اختياري)</span></label>
                        <select name="project_id" class="form-select">
                            <option value="">— بدون —</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected((int) old('project_id', $partner->project_id) === $project->id)>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">العنوان</label>
                        <textarea name="address" rows="2" class="form-control">{{ old('address', $partner->address) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $partner->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('partners.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
