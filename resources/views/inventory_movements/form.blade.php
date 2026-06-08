@extends('layouts.app')

@section('title', 'حركة مخزون جديدة')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('inventory_movements.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">المادة <span class="text-danger">*</span></label>
                        <select name="material_id" class="form-select" required>
                            <option value="">— اختر —</option>
                            @foreach ($materials as $mat)
                                <option value="{{ $mat->id }}" @selected((int) old('material_id', request('material_id')) === $mat->id)>{{ $mat->name }} (متاح: {{ number_format($mat->current_stock, 2) }} {{ $mat->unit }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">النوع <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            @foreach (\App\Models\InventoryMovement::TYPES as $k => $label)
                                <option value="{{ $k }}" @selected(old('type') === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الكمية <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="quantity" value="{{ old('quantity') }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">سعر الوحدة</label>
                        <input type="number" step="0.01" min="0" name="unit_price" value="{{ old('unit_price') }}" class="form-control" placeholder="افتراضي: سعر المادة">
                        <small class="text-muted">يُستخدم لحساب قيمة الحركة. لو فارغ يُؤخذ سعر المادة.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">التاريخ <span class="text-danger">*</span></label>
                        <input type="date" name="movement_date" value="{{ old('movement_date', date('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">اتجاه التسوية</label>
                        <select name="adjustment_direction" class="form-select">
                            <option value="">— (مطلوب عند تسوية جرد) —</option>
                            <option value="increase" @selected(old('adjustment_direction') === 'increase')>زيادة</option>
                            <option value="decrease" @selected(old('adjustment_direction') === 'decrease')>نقص</option>
                        </select>
                        <small class="text-muted">يُستخدم فقط مع نوع "تسوية جرد".</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المشروع</label>
                        <select name="project_id" class="form-select">
                            <option value="">— بدون —</option>
                            @foreach ($projects as $p)
                                <option value="{{ $p->id }}" @selected((int) old('project_id') === $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">إلى مشروع (للتحويل)</label>
                        <select name="to_project_id" class="form-select">
                            <option value="">— بدون —</option>
                            @foreach ($projects as $p)
                                <option value="{{ $p->id }}" @selected((int) old('to_project_id') === $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">مطلوب عند نوع "تحويل بين المشاريع".</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المستلِم</label>
                        <select name="employee_id" class="form-select">
                            <option value="">— بدون —</option>
                            @foreach ($employees as $e)
                                <option value="{{ $e->id }}" @selected((int) old('employee_id') === $e->id)>{{ $e->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">موقع المخزن</label>
                        <input type="text" name="warehouse_location" value="{{ old('warehouse_location') }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">السبب</label>
                        <input type="text" name="reason" value="{{ old('reason') }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('inventory_movements.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
