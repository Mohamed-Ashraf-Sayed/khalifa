@extends('layouts.app')

@section('title', $supplier->exists ? 'تعديل مورد' : 'مورد جديد')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}">
                @csrf
                @if ($supplier->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">الاسم <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $supplier->name) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">اسم الشركة</label>
                        <input type="text" name="company_name" value="{{ old('company_name', $supplier->company_name) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">النوع <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            @foreach (\App\Models\Supplier::TYPES as $value => $label)
                                <option value="{{ $value }}" @selected(old('type', $supplier->type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الهاتف <span class="text-danger">*</span></label>
                        <input type="text" name="phone" value="{{ old('phone', $supplier->phone) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">هاتف آخر</label>
                        <input type="text" name="phone2" value="{{ old('phone2', $supplier->phone2) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" name="email" value="{{ old('email', $supplier->email) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الرقم الضريبي</label>
                        <input type="text" name="tax_number" value="{{ old('tax_number', $supplier->tax_number) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">السجل التجاري</label>
                        <input type="text" name="commercial_register" value="{{ old('commercial_register', $supplier->commercial_register) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">رصيد افتتاحي</label>
                        <input type="number" step="0.01" name="opening_balance" value="{{ old('opening_balance', $supplier->opening_balance ?? 0) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الحد الائتماني</label>
                        <input type="number" step="0.01" min="0" name="credit_limit" value="{{ old('credit_limit', $supplier->credit_limit ?? 0) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">أيام السداد</label>
                        <input type="number" min="0" name="payment_terms" value="{{ old('payment_terms', $supplier->payment_terms) }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">العنوان</label>
                        <textarea name="address" rows="2" class="form-control">{{ old('address', $supplier->address) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $supplier->notes) }}</textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active"
                                   @checked(old('is_active', $supplier->exists ? $supplier->is_active : true))>
                            <label class="form-check-label" for="is_active">نشط</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('suppliers.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
