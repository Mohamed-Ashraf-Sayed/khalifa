@extends('layouts.app')

@section('title', $purchaseOrder->exists ? 'تعديل أمر شراء' : 'أمر شراء جديد')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $purchaseOrder->exists ? route('purchase_orders.update', $purchaseOrder) : route('purchase_orders.store') }}">
                @csrf
                @if ($purchaseOrder->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">رقم الأمر <span class="text-danger">*</span></label>
                        <input type="text" name="order_number" value="{{ old('order_number', $purchaseOrder->order_number) }}" class="form-control" maxlength="50" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">المورّد <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">— اختر المورّد —</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((int) old('supplier_id', $purchaseOrder->supplier_id) === $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">المشروع</label>
                        <select name="project_id" class="form-select">
                            <option value="">— عام —</option>
                            @foreach ($projects as $p)
                                <option value="{{ $p->id }}" @selected((int) old('project_id', $purchaseOrder->project_id) === $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            @foreach (\App\Models\PurchaseOrder::STATUSES as $k => $label)
                                <option value="{{ $k }}" @selected(old('status', $purchaseOrder->status) === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ الأمر <span class="text-danger">*</span></label>
                        <input type="date" name="order_date" value="{{ old('order_date', $purchaseOrder->order_date?->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ التسليم المتوقع</label>
                        <input type="date" name="expected_delivery" value="{{ old('expected_delivery', $purchaseOrder->expected_delivery?->format('Y-m-d')) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ التسليم الفعلي</label>
                        <input type="date" name="actual_delivery" value="{{ old('actual_delivery', $purchaseOrder->actual_delivery?->format('Y-m-d')) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الخصم</label>
                        <input type="number" step="0.01" min="0" name="discount" value="{{ old('discount', $purchaseOrder->discount ?? 0) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الضريبة</label>
                        <input type="number" step="0.01" min="0" name="tax" value="{{ old('tax', $purchaseOrder->tax ?? 0) }}" class="form-control">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check">
                            <input type="hidden" name="add_to_inventory" value="0">
                            <input type="checkbox" name="add_to_inventory" value="1" class="form-check-input" id="ati" @checked(old('add_to_inventory', $purchaseOrder->add_to_inventory))>
                            <label class="form-check-label" for="ati">إضافة للمخزون عند الاستلام</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $purchaseOrder->notes) }}</textarea>
                    </div>
                </div>

                <div class="alert alert-light border mt-3 mb-0 small"><i class="fa-solid fa-circle-info ms-1"></i> الإجمالي يُحسب تلقائياً من الأصناف اللي هتضيفها في صفحة الأمر بعد الحفظ.</div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('purchase_orders.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
