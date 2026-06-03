@extends('layouts.app')

@section('title', $cheque->exists ? 'تعديل شيك' : 'شيك جديد')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $cheque->exists ? route('cheques.update', $cheque) : route('cheques.store') }}">
                @csrf
                @if ($cheque->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">رقم الشيك <span class="text-danger">*</span></label>
                        <input type="text" name="cheque_number" value="{{ old('cheque_number', $cheque->cheque_number) }}" class="form-control" dir="ltr" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">النوع</label>
                        <select name="direction" class="form-select">
                            @foreach (\App\Models\Cheque::DIRECTIONS as $key => $label)
                                <option value="{{ $key }}" @selected(old('direction', $cheque->direction) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            @foreach (\App\Models\Cheque::STATUSES as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $cheque->status) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">اسم الطرف <span class="text-danger">*</span></label>
                        <input type="text" name="party_name" value="{{ old('party_name', $cheque->party_name) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الحساب البنكي</label>
                        <select name="bank_account_id" class="form-select">
                            <option value="">— بدون —</option>
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->id }}" @selected((int) old('bank_account_id', $cheque->bank_account_id) === $acc->id)>{{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المبلغ <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount', $cheque->amount) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ الإصدار <span class="text-danger">*</span></label>
                        <input type="date" name="issue_date" value="{{ old('issue_date', $cheque->issue_date?->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ الاستحقاق</label>
                        <input type="date" name="due_date" value="{{ old('due_date', $cheque->due_date?->format('Y-m-d')) }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $cheque->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('cheques.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
