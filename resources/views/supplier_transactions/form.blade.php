@extends('layouts.app')

@section('title', $transaction->exists ? 'تعديل عملية شراء' : 'عملية شراء جديدة')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $transaction->exists ? route('supplier_transactions.update', $transaction) : route('supplier_transactions.store') }}">
                @csrf
                @if ($transaction->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">المورّد <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">— اختر —</option>
                            @foreach ($suppliers as $s)
                                <option value="{{ $s->id }}" @selected((int) old('supplier_id', $transaction->supplier_id) === $s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المشروع</label>
                        <select name="project_id" class="form-select">
                            <option value="">— عام —</option>
                            @foreach ($projects as $p)
                                <option value="{{ $p->id }}" @selected((int) old('project_id', $transaction->project_id) === $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">التاريخ <span class="text-danger">*</span></label>
                        <input type="date" name="transaction_date" value="{{ old('transaction_date', $transaction->transaction_date?->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">البيان <span class="text-danger">*</span></label>
                        <input type="text" name="item_description" value="{{ old('item_description', $transaction->item_description) }}" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الفئة</label>
                        <select name="category" class="form-select">
                            <option value="">—</option>
                            @foreach (\App\Models\SupplierTransaction::CATEGORIES as $k => $label)
                                <option value="{{ $k }}" @selected(old('category', $transaction->category) === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الوحدة</label>
                        <input type="text" name="unit" value="{{ old('unit', $transaction->unit) }}" class="form-control" placeholder="قطعة/طن..">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الكمية <span class="text-danger">*</span></label>
                        <input type="number" step="0.001" min="0.001" name="quantity" value="{{ old('quantity', $transaction->quantity ?? 1) }}" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">سعر الوحدة <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="unit_price" value="{{ old('unit_price', $transaction->unit_price) }}" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">نسبة الخصم %</label>
                        <input type="number" step="0.01" min="0" max="100" name="discount_percentage" value="{{ old('discount_percentage', $transaction->discount_percentage ?? 0) }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">المدفوع عند الشراء</label>
                        <input type="number" step="0.01" min="0" name="paid_amount" value="{{ old('paid_amount', $transaction->paid_amount ?? 0) }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">طريقة الدفع</label>
                        <select name="payment_method" class="form-select">
                            @foreach (\App\Models\SupplierTransaction::PAYMENT_METHODS as $k => $label)
                                <option value="{{ $k }}" @selected(old('payment_method', $transaction->payment_method) === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">رقم الشيك</label>
                        <input type="text" name="check_number" value="{{ old('check_number', $transaction->check_number) }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $transaction->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('supplier_transactions.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
