@extends('layouts.app')

@section('title', $contractor->exists ? 'تعديل مقاول' : 'مقاول جديد')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $contractor->exists ? route('contractors.update', $contractor) : route('contractors.store') }}">
                @csrf
                @if ($contractor->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">الكود <span class="text-danger">*</span></label>
                        <input type="text" name="contractor_code" value="{{ old('contractor_code', $contractor->contractor_code) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الاسم <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $contractor->name) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">اسم الشركة</label>
                        <input type="text" name="company_name" value="{{ old('company_name', $contractor->company_name) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الهاتف</label>
                        <input type="text" name="phone" value="{{ old('phone', $contractor->phone) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">هاتف آخر</label>
                        <input type="text" name="phone2" value="{{ old('phone2', $contractor->phone2) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" name="email" value="{{ old('email', $contractor->email) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">التخصص</label>
                        <input type="text" name="specialty" value="{{ old('specialty', $contractor->specialty) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الرقم القومي</label>
                        <input type="text" name="national_id" value="{{ old('national_id', $contractor->national_id) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الرقم الضريبي</label>
                        <input type="text" name="tax_number" value="{{ old('tax_number', $contractor->tax_number) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">رصيد افتتاحي</label>
                        <input type="number" step="0.01" name="opening_balance" value="{{ old('opening_balance', $contractor->opening_balance ?? 0) }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $contractor->notes) }}</textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input"
                                   {{ old('is_active', $contractor->exists ? $contractor->is_active : true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">نشط</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('contractors.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
