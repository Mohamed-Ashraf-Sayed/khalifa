@extends('layouts.app')

@php($isCash = old('account_type', $account->account_type) === 'cash')
@section('title', $account->exists ? ($isCash ? 'تعديل خزنة' : 'تعديل حساب بنكي') : ($isCash ? 'خزنة جديدة' : 'حساب بنكي جديد'))

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $account->exists ? route('bank_accounts.update', $account) : route('bank_accounts.store') }}">
                @csrf
                @if ($account->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">نوع الحساب</label>
                        <select name="account_type" id="acctType" class="form-select" onchange="toggleCashFields()">
                            <option value="">— حساب بنكي —</option>
                            @foreach (\App\Models\BankAccount::ACCOUNT_TYPES as $key => $label)
                                <option value="{{ $key }}" @selected(old('account_type', $account->account_type) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">اسم الحساب / الخزنة <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $account->name) }}" class="form-control" required placeholder="مثال: خزنة الشركة الرئيسية">
                    </div>

                    <div class="col-md-6 bank-only">
                        <label class="form-label">اسم البنك <span class="text-danger bank-req">*</span></label>
                        <input type="text" name="bank_name" id="bankName" value="{{ old('bank_name', $account->bank_name) }}" class="form-control">
                    </div>
                    <div class="col-md-3 bank-only">
                        <label class="form-label">رقم الحساب</label>
                        <input type="text" name="account_number" value="{{ old('account_number', $account->account_number) }}" class="form-control">
                    </div>
                    <div class="col-md-3 bank-only">
                        <label class="form-label">IBAN</label>
                        <input type="text" name="iban" value="{{ old('iban', $account->iban) }}" class="form-control">
                    </div>
                    <div class="col-md-4 bank-only">
                        <label class="form-label">الفرع</label>
                        <input type="text" name="branch" value="{{ old('branch', $account->branch) }}" class="form-control">
                    </div>
                    <div class="col-md-4 bank-only">
                        <label class="form-label">SWIFT / BIC</label>
                        <input type="text" name="swift_code" value="{{ old('swift_code', $account->swift_code) }}" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">الرصيد الافتتاحي <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="opening_balance" value="{{ old('opening_balance', $account->opening_balance ?? 0) }}" class="form-control" required @if($account->exists && $account->transactions()->exists()) title="تغييره يعيد احتساب الرصيد الحالي" @endif>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">العملة</label>
                        <input type="text" name="currency" value="{{ old('currency', $account->currency ?? 'EGP') }}" class="form-control">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input" @checked(old('is_active', $account->is_active ?? true))>
                            <label for="is_active" class="form-check-label">نشط</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $account->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ $isCash ? route('treasury') : route('bank_accounts.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleCashFields() {
            var isCash = document.getElementById('acctType').value === 'cash';
            document.querySelectorAll('.bank-only').forEach(function (el) { el.style.display = isCash ? 'none' : ''; });
            var bn = document.getElementById('bankName');
            if (bn) bn.required = !isCash;
            document.querySelectorAll('.bank-req').forEach(function (el) { el.style.display = isCash ? 'none' : ''; });
        }
        toggleCashFields();
    </script>
@endsection
