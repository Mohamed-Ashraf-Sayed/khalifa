@extends('layouts.app')

@section('title', $client->exists ? 'تعديل عميل' : 'عميل جديد')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $client->exists ? route('clients.update', $client) : route('clients.store') }}">
                @csrf
                @if ($client->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">الاسم <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $client->name) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">اسم الشركة</label>
                        <input type="text" name="company_name" value="{{ old('company_name', $client->company_name) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الهاتف <span class="text-danger">*</span></label>
                        <input type="text" name="phone" value="{{ old('phone', $client->phone) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">هاتف آخر</label>
                        <input type="text" name="phone2" value="{{ old('phone2', $client->phone2) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" name="email" value="{{ old('email', $client->email) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المدينة</label>
                        <input type="text" name="city" value="{{ old('city', $client->city) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الرقم الضريبي</label>
                        <input type="text" name="tax_number" value="{{ old('tax_number', $client->tax_number) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">السجل التجاري</label>
                        <input type="text" name="commercial_register" value="{{ old('commercial_register', $client->commercial_register) }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">العنوان</label>
                        <textarea name="address" rows="2" class="form-control">{{ old('address', $client->address) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $client->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('clients.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
