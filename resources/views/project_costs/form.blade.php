@extends('layouts.app')

@section('title', $cost->exists ? 'تعديل تكلفة' : 'تكلفة جديدة')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $cost->exists ? route('project_costs.update', $cost) : route('project_costs.store') }}">
                @csrf
                @if ($cost->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">المشروع <span class="text-danger">*</span></label>
                        <select name="project_id" class="form-select" required>
                            <option value="">— اختر —</option>
                            @foreach ($projects as $p)
                                <option value="{{ $p->id }}" @selected((int) old('project_id', $cost->project_id) === $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">بند الأعمال <span class="text-danger">*</span></label>
                        <input type="text" name="work_item" value="{{ old('work_item', $cost->work_item) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">اسم المقاول/المورد</label>
                        <input type="text" name="contractor_supplier" value="{{ old('contractor_supplier', $cost->contractor_supplier) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الفئة</label>
                        <input type="text" name="category" value="{{ old('category', $cost->category) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">مركز التكلفة</label>
                        <select name="cost_center_id" class="form-select">
                            <option value="">— بدون —</option>
                            @foreach ($costCenters as $cc)
                                <option value="{{ $cc->id }}" @selected((int) old('cost_center_id', $cost->cost_center_id) === $cc->id)>{{ $cc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الوصف</label>
                        <input type="text" name="description" value="{{ old('description', $cost->description) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">التاريخ <span class="text-danger">*</span></label>
                        <input type="date" name="cost_date" value="{{ old('cost_date', $cost->cost_date?->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الوحدة</label>
                        <input type="text" name="unit" value="{{ old('unit', $cost->unit) }}" class="form-control" placeholder="م٢/طن..">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الكمية <span class="text-danger">*</span></label>
                        <input type="number" step="0.001" min="0.001" name="quantity" value="{{ old('quantity', $cost->quantity ?? 1) }}" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">سعر الوحدة <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="unit_price" value="{{ old('unit_price', $cost->unit_price) }}" class="form-control" required>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="small text-muted">الإجمالي يُحسب تلقائياً (الكمية × سعر الوحدة).</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $cost->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('project_costs.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
