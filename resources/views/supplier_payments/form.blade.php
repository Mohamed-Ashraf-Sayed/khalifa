@extends('layouts.app')

@section('title', $supplierPayment->exists ? 'تعديل دفعة' : 'دفعة جديدة')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $supplierPayment->exists ? route('supplier_payments.update', $supplierPayment) : route('supplier_payments.store') }}">
                @csrf
                @if ($supplierPayment->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">المورد <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">— اختر مورد —</option>
                            @foreach ($suppliers as $s)
                                <option value="{{ $s->id }}" @selected((int) old('supplier_id', $supplierPayment->supplier_id) === $s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الإجمالي المستحق (قبل الاستقطاع) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $supplierPayment->amount) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">التاريخ <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" value="{{ old('payment_date', $supplierPayment->payment_date?->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">طريقة الدفع</label>
                        <select name="payment_method" class="form-select">
                            @foreach (\App\Models\SupplierPayment::PAYMENT_METHODS as $k => $label)
                                <option value="{{ $k }}" @selected(old('payment_method', $supplierPayment->payment_method) === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">رقم المرجع</label>
                        <input type="text" name="reference_number" value="{{ old('reference_number', $supplierPayment->reference_number) }}" class="form-control" maxlength="100">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">خصم من حساب بنكي <span class="text-muted small">(اختياري — يسجّل سحباً من الحساب)</span></label>
                        <select name="bank_account_id" class="form-select">
                            <option value="">— بدون —</option>
                            @foreach ($accounts as $a)
                                <option value="{{ $a->id }}" @selected((int) old('bank_account_id', $supplierPayment->bank_account_id) === $a->id)>{{ $a->name }} ({{ number_format($a->current_balance, 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    @php
                        $deductionLabels = [
                            'vat' => 'ضريبة القيمة المضافة',
                            'insurance_5_percent' => 'تأمين 5%',
                            'social_insurance' => 'تأمينات اجتماعية',
                            'commercial_profit_supply' => 'أرباح تجارية (توريدات)',
                            'commercial_profit_works' => 'أرباح تجارية (أعمال)',
                            'engineering_professions' => 'مهن هندسية',
                            'arts_specialists' => 'أخصائيو فنون',
                            'applied_professions' => 'مهن تطبيقية',
                            'bank_transfer_fee' => 'رسوم تحويل بنكي',
                            'other_deductions' => 'استقطاعات أخرى',
                        ];
                    @endphp
                    <div class="col-12">
                        <div class="card border">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center" role="button" data-bs-toggle="collapse" data-bs-target="#deductionsSection" aria-expanded="false" aria-controls="deductionsSection">
                                <span><i class="fa-solid fa-scissors ms-1"></i> الاستقطاعات (اختياري)</span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </div>
                            <div class="collapse" id="deductionsSection">
                                <div class="card-body">
                                    <div class="row g-3">
                                        @foreach ($deductionLabels as $field => $label)
                                            <div class="col-md-4">
                                                <label class="form-label">{{ $label }}</label>
                                                <input type="number" step="0.01" min="0" name="{{ $field }}" value="{{ old($field, $supplierPayment->$field ?? 0) }}" class="form-control">
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $supplierPayment->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('supplier_payments.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
