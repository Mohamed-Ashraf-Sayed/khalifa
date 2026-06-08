@extends('layouts.app')

@section('title', $transaction->exists ? 'تعديل حركة' : 'حركة جديدة')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $transaction->exists ? route('partner_transactions.update', $transaction) : route('partner_transactions.store') }}">
                @csrf
                @if ($transaction->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">الشريك <span class="text-danger">*</span></label>
                        <select name="partner_id" class="form-select" required>
                            <option value="">— اختر —</option>
                            @foreach ($partners as $partner)
                                <option value="{{ $partner->id }}" @selected((int) old('partner_id', $transaction->partner_id) === $partner->id)>{{ $partner->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">النوع <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            @foreach (\App\Models\PartnerTransaction::TYPES as $k => $label)
                                <option value="{{ $k }}" @selected(old('type', $transaction->type) === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">المبلغ <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $transaction->amount) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">التاريخ <span class="text-danger">*</span></label>
                        <input type="date" name="transaction_date" value="{{ old('transaction_date', $transaction->transaction_date?->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">طريقة الدفع</label>
                        <select name="payment_method" class="form-select">
                            <option value="">— بدون —</option>
                            @foreach (['cash' => 'نقدي', 'bank' => 'تحويل بنكي', 'check' => 'شيك'] as $k => $label)
                                <option value="{{ $k }}" @selected(old('payment_method', $transaction->payment_method) === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">حساب بنكي <span class="text-muted small">(اختياري)</span></label>
                        <select name="bank_account_id" class="form-select">
                            <option value="">— بدون —</option>
                            @foreach ($accounts as $a)
                                <option value="{{ $a->id }}" @selected((int) old('bank_account_id', $transaction->bank_account_id) === $a->id)>{{ $a->name }} ({{ number_format($a->current_balance, 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">رقم الشيك</label>
                        <input type="text" name="check_number" value="{{ old('check_number', $transaction->check_number) }}" class="form-control" maxlength="50">
                    </div>
                    <div class="col-12">
                        <label class="form-label">البيان</label>
                        <input type="text" name="description" value="{{ old('description', $transaction->description) }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $transaction->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('partner_transactions.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
