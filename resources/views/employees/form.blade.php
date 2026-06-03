@extends('layouts.app')

@section('title', $employee->exists ? 'تعديل موظف' : 'موظف جديد')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}">
                @csrf
                @if ($employee->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">كود الموظف <span class="text-danger">*</span></label>
                        <input type="text" name="employee_code" value="{{ old('employee_code', $employee->employee_code) }}" class="form-control" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">الاسم <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $employee->name) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الرقم القومي</label>
                        <input type="text" name="national_id" value="{{ old('national_id', $employee->national_id) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المسمى الوظيفي <span class="text-danger">*</span></label>
                        <input type="text" name="job_title" value="{{ old('job_title', $employee->job_title) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">القسم</label>
                        <input type="text" name="department" value="{{ old('department', $employee->department) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الراتب</label>
                        <input type="number" step="0.01" min="0" name="salary" value="{{ old('salary', $employee->salary) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الحساب البنكي لصرف الراتب</label>
                        <select name="bank_account_id" class="form-select">
                            <option value="">— بدون —</option>
                            @foreach ($accounts as $a)
                                <option value="{{ $a->id }}" @selected((int) old('bank_account_id', $employee->bank_account_id) === $a->id)>{{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الهاتف</label>
                        <input type="text" name="phone" value="{{ old('phone', $employee->phone) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" name="email" value="{{ old('email', $employee->email) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ التعيين <span class="text-danger">*</span></label>
                        <input type="date" name="hire_date" value="{{ old('hire_date', optional($employee->hire_date)->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-8 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active"
                                   {{ old('is_active', $employee->exists ? $employee->is_active : true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">نشط</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $employee->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('employees.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
