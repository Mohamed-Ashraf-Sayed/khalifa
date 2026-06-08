@extends('layouts.app')

@section('title', 'إيداع جديد')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('partner_deposits.store') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">الشريك <span class="text-danger">*</span></label>
                        <select name="partner_id" class="form-select" required>
                            <option value="">— اختر —</option>
                            @foreach ($partners as $partner)
                                <option value="{{ $partner->id }}" @selected((int) old('partner_id', $deposit->partner_id) === $partner->id)>{{ $partner->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">رأس المال <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $deposit->amount) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ الإيداع <span class="text-danger">*</span></label>
                        <input type="date" name="deposit_date" value="{{ old('deposit_date', $deposit->deposit_date?->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">نسبة الربح السنوية (%) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="profit_rate" value="{{ old('profit_rate', $deposit->profit_rate) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المدة (بالأشهر) <span class="text-danger">*</span></label>
                        <input type="number" min="1" step="1" name="duration_months" value="{{ old('duration_months', $deposit->duration_months) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">دورية صرف الأرباح <span class="text-danger">*</span></label>
                        <select name="payout_frequency" class="form-select" required>
                            @foreach (\App\Models\PartnerDeposit::PAYOUT_FREQUENCIES as $k => $label)
                                <option value="{{ $k }}" @selected(old('payout_frequency', $deposit->payout_frequency) === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">إيداع من حساب بنكي <span class="text-muted small">(اختياري — يسجّل إيداعاً بالحساب)</span></label>
                        <select name="bank_account_id" class="form-select">
                            <option value="">— بدون —</option>
                            @foreach ($accounts as $a)
                                <option value="{{ $a->id }}" @selected((int) old('bank_account_id', $deposit->bank_account_id) === $a->id)>{{ $a->name }} ({{ number_format($a->current_balance, 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $deposit->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('partner_deposits.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
