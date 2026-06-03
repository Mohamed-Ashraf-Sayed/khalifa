@extends('layouts.app')

@section('title', $material->exists ? 'تعديل مادة' : 'مادة جديدة')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $material->exists ? route('materials.update', $material) : route('materials.store') }}">
                @csrf
                @if ($material->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">اسم المادة <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $material->name) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">التصنيف</label>
                        <select name="category" class="form-select">
                            @foreach (\App\Models\Material::CATEGORIES as $key => $label)
                                <option value="{{ $key }}" @selected(old('category', $material->category) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الوحدة <span class="text-danger">*</span></label>
                        <input type="text" name="unit" value="{{ old('unit', $material->unit) }}" class="form-control" placeholder="طن / متر" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">سعر الوحدة <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="unit_price" value="{{ old('unit_price', $material->unit_price) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المخزون الحالي <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="current_stock" value="{{ old('current_stock', $material->current_stock) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الحد الأدنى للمخزون <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="min_stock" value="{{ old('min_stock', $material->min_stock) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المشروع</label>
                        <select name="project_id" class="form-select">
                            <option value="">— بدون —</option>
                            @foreach ($projects as $p)
                                <option value="{{ $p->id }}" @selected((int) old('project_id', $material->project_id) === $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المورّد</label>
                        <select name="supplier_id" class="form-select">
                            <option value="">— بدون —</option>
                            @foreach ($suppliers as $s)
                                <option value="{{ $s->id }}" @selected((int) old('supplier_id', $material->supplier_id) === $s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $material->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('materials.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
