@extends('layouts.app')

@section('title', $costCenter->exists ? 'تعديل مركز تكلفة' : 'مركز تكلفة جديد')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $costCenter->exists ? route('cost_centers.update', $costCenter) : route('cost_centers.store') }}">
                @csrf
                @if ($costCenter->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">الاسم <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $costCenter->name) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الكود <span class="text-danger">*</span></label>
                        <input type="text" name="code" value="{{ old('code', $costCenter->code) }}" class="form-control" maxlength="40" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input" @checked(old('is_active', $costCenter->is_active ?? true))>
                            <label class="form-check-label" for="is_active">نشط</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="3" class="form-control">{{ old('notes', $costCenter->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('cost_centers.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
