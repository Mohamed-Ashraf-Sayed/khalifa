@extends('layouts.app')

@section('title', $contractorPayment->exists ? 'تعديل دفعة مقاول' : 'دفعة مقاول جديدة')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $contractorPayment->exists ? route('contractor_payments.update', $contractorPayment) : route('contractor_payments.store') }}">
                @csrf
                @if ($contractorPayment->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">المقاول <span class="text-danger">*</span></label>
                        <select name="contractor_id" class="form-select" required>
                            <option value="">— اختر —</option>
                            @foreach ($contractors as $c)
                                <option value="{{ $c->id }}" @selected((int) old('contractor_id', $contractorPayment->contractor_id) === $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المبلغ <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $contractorPayment->amount) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المستخلص <span class="text-muted small">(اختياري)</span></label>
                        <select name="extract_id" class="form-select">
                            <option value="">— بدون —</option>
                            @foreach ($extracts as $extract)
                                <option value="{{ $extract->id }}" @selected((int) old('extract_id', $contractorPayment->extract_id) === $extract->id)>{{ $extract->extract_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">التاريخ <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" value="{{ old('payment_date', $contractorPayment->payment_date?->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">طريقة الدفع</label>
                        <select name="payment_method" class="form-select">
                            @foreach (\App\Models\ContractorPayment::PAYMENT_METHODS as $k => $label)
                                <option value="{{ $k }}" @selected(old('payment_method', $contractorPayment->payment_method) === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">خصم من حساب بنكي <span class="text-muted small">(اختياري — يسجّل سحباً من الحساب)</span></label>
                        <select name="bank_account_id" class="form-select">
                            <option value="">— بدون —</option>
                            @foreach ($accounts as $a)
                                <option value="{{ $a->id }}" @selected((int) old('bank_account_id', $contractorPayment->bank_account_id) === $a->id)>{{ $a->name }} ({{ number_format($a->current_balance, 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">رقم المرجع</label>
                        <input type="text" name="reference_number" value="{{ old('reference_number', $contractorPayment->reference_number) }}" class="form-control" maxlength="100">
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $contractorPayment->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('contractor_payments.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
