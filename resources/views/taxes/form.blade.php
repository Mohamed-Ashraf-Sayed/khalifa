@extends('layouts.app')

@section('title', $tax->exists ? 'تعديل ضريبة' : 'ضريبة جديدة')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $tax->exists ? route('taxes.update', $tax) : route('taxes.store') }}">
                @csrf
                @if ($tax->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">اسم الضريبة <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $tax->name) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">النوع</label>
                        <select name="tax_type" class="form-select">
                            @foreach (\App\Models\Tax::TYPES as $key => $label)
                                <option value="{{ $key }}" @selected(old('tax_type', $tax->tax_type) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المشروع</label>
                        <select name="project_id" class="form-select">
                            <option value="">— بدون —</option>
                            @foreach ($projects as $p)
                                <option value="{{ $p->id }}" @selected((int) old('project_id', $tax->project_id) === $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            @foreach (\App\Models\Tax::STATUSES as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $tax->status) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">النسبة (%) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="rate" value="{{ old('rate', $tax->rate) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المبلغ الأساسي <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="base_amount" value="{{ old('base_amount', $tax->base_amount) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المبلغ <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount', $tax->amount) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الفترة</label>
                        <input type="text" name="period" value="{{ old('period', $tax->period) }}" class="form-control" placeholder="مثال: 2026-Q1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ الاستحقاق</label>
                        <input type="date" name="due_date" value="{{ old('due_date', $tax->due_date?->format('Y-m-d')) }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $tax->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('taxes.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
