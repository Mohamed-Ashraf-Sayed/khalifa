@extends('layouts.app')

@section('title', $asset->exists ? 'تعديل أصل' : 'أصل جديد')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $asset->exists ? route('assets.update', $asset) : route('assets.store') }}">
                @csrf
                @if ($asset->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">كود الأصل <span class="text-danger">*</span></label>
                        <input type="text" name="asset_code" value="{{ old('asset_code', $asset->asset_code) }}" class="form-control" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">اسم الأصل <span class="text-danger">*</span></label>
                        <input type="text" name="asset_name" value="{{ old('asset_name', $asset->asset_name) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">التصنيف</label>
                        <input type="text" name="category" value="{{ old('category', $asset->category) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            @foreach (\App\Models\Asset::STATUSES as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $asset->status) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ الشراء <span class="text-danger">*</span></label>
                        <input type="date" name="purchase_date" value="{{ old('purchase_date', $asset->purchase_date?->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">قيمة الشراء <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="purchase_value" value="{{ old('purchase_value', $asset->purchase_value) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">قيمة الخردة (المتبقّية)</label>
                        <input type="number" step="0.01" min="0" name="salvage_value" value="{{ old('salvage_value', $asset->salvage_value ?? 0) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">طريقة الإهلاك</label>
                        <select name="depreciation_method" class="form-select">
                            @foreach (\App\Models\Asset::METHODS as $key => $label)
                                <option value="{{ $key }}" @selected(old('depreciation_method', $asset->depreciation_method ?? 'straight_line') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">نسبة الإهلاك % <span class="text-muted small">(للقسط المتناقص)</span></label>
                        <input type="number" step="0.01" min="0" name="depreciation_rate" value="{{ old('depreciation_rate', $asset->depreciation_rate) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">العمر الإنتاجي (سنوات)</label>
                        <input type="number" min="1" name="useful_life_years" value="{{ old('useful_life_years', $asset->useful_life_years) }}" class="form-control">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">الموقع</label>
                        <input type="text" name="location" value="{{ old('location', $asset->location) }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $asset->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('assets.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
