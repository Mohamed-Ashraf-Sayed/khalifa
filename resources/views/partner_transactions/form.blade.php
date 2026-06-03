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
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('partner_transactions.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
