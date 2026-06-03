@extends('layouts.app')

@section('title', 'تحويل بنكي جديد')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('bank_transfers.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">من حساب <span class="text-danger">*</span></label>
                        <select name="from_account_id" class="form-select" required>
                            <option value="">— اختر —</option>
                            @foreach ($accounts as $a)
                                <option value="{{ $a->id }}" @selected((int) old('from_account_id') === $a->id)>{{ $a->name }} ({{ number_format($a->current_balance, 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">إلى حساب <span class="text-danger">*</span></label>
                        <select name="to_account_id" class="form-select" required>
                            <option value="">— اختر —</option>
                            @foreach ($accounts as $a)
                                <option value="{{ $a->id }}" @selected((int) old('to_account_id') === $a->id)>{{ $a->name }} ({{ number_format($a->current_balance, 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المبلغ <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الرسوم</label>
                        <input type="number" step="0.01" min="0" name="fees" value="{{ old('fees', 0) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">التاريخ <span class="text-danger">*</span></label>
                        <input type="date" name="transfer_date" value="{{ old('transfer_date', date('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">البيان</label>
                        <input type="text" name="description" value="{{ old('description') }}" class="form-control">
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-right-left ms-1"></i> تنفيذ التحويل</button>
                    <a href="{{ route('bank_transfers.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
