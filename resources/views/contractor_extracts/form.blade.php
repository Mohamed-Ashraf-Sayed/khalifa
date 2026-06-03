@extends('layouts.app')

@section('title', $contractorExtract->exists ? 'تعديل مستخلص' : 'مستخلص جديد')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $contractorExtract->exists ? route('contractor_extracts.update', $contractorExtract) : route('contractor_extracts.store') }}">
                @csrf
                @if ($contractorExtract->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">رقم المستخلص <span class="text-danger">*</span></label>
                        <input type="text" name="extract_number" value="{{ old('extract_number', $contractorExtract->extract_number) }}" class="form-control" maxlength="50" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المقاول <span class="text-danger">*</span></label>
                        <select name="contractor_id" class="form-select" required>
                            <option value="">— اختر —</option>
                            @foreach ($contractors as $c)
                                <option value="{{ $c->id }}" @selected((int) old('contractor_id', $contractorExtract->contractor_id) === $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المشروع</label>
                        <select name="project_id" class="form-select">
                            <option value="">— عام —</option>
                            @foreach ($projects as $p)
                                <option value="{{ $p->id }}" @selected((int) old('project_id', $contractorExtract->project_id) === $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">التاريخ <span class="text-danger">*</span></label>
                        <input type="date" name="extract_date" value="{{ old('extract_date', $contractorExtract->extract_date?->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الإضافات</label>
                        <input type="number" step="0.01" min="0" name="additions" value="{{ old('additions', $contractorExtract->additions ?? 0) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الخصومات</label>
                        <input type="number" step="0.01" min="0" name="deductions" value="{{ old('deductions', $contractorExtract->deductions ?? 0) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">نسبة التنفيذ %</label>
                        <input type="number" step="0.01" min="0" max="100" name="execution_percent" value="{{ old('execution_percent', $contractorExtract->execution_percent ?? 0) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            @foreach (\App\Models\ContractorExtract::STATUSES as $k => $label)
                                <option value="{{ $k }}" @selected(old('status', $contractorExtract->status) === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" rows="2" class="form-control">{{ old('description', $contractorExtract->description) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $contractorExtract->notes) }}</textarea>
                    </div>
                </div>

                <div class="alert alert-light border mt-3 mb-0 small"><i class="fa-solid fa-circle-info ms-1"></i> إجمالي المستخلص يُحسب تلقائياً من بنود الأعمال اللي هتضيفها بعد الحفظ. الصافي = (الإجمالي + الإضافات) − الخصومات.</div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('contractor_extracts.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
