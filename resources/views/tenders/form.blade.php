@extends('layouts.app')

@section('title', $tender->exists ? 'تعديل مناقصة' : 'مناقصة جديدة')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $tender->exists ? route('tenders.update', $tender) : route('tenders.store') }}">
                @csrf
                @if ($tender->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">رقم المناقصة <span class="text-danger">*</span></label>
                        <input type="text" name="tender_number" value="{{ old('tender_number', $tender->tender_number) }}" class="form-control" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">عنوان المناقصة <span class="text-danger">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $tender->title) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">العميل / جهة الطرح</label>
                        <select name="client_id" class="form-select">
                            <option value="">— بدون —</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}" @selected((string) old('client_id', $tender->client_id) === (string) $client->id)>{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            @foreach (\App\Models\Tender::STATUSES as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $tender->status) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">القيمة التقديرية</label>
                        <input type="number" step="0.01" min="0" name="estimated_value" value="{{ old('estimated_value', $tender->estimated_value) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">قيمة العطاء المقدّم</label>
                        <input type="number" step="0.01" min="0" name="bid_value" value="{{ old('bid_value', $tender->bid_value) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ التقديم</label>
                        <input type="date" name="submission_date" value="{{ old('submission_date', $tender->submission_date?->format('Y-m-d')) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">خطاب ضمان ابتدائي (عطاء)</label>
                        <select name="guarantee_id" class="form-select">
                            <option value="">— بدون —</option>
                            @foreach ($guarantees as $guarantee)
                                <option value="{{ $guarantee->id }}" @selected((string) old('guarantee_id', $tender->guarantee_id) === (string) $guarantee->id)>
                                    {{ $guarantee->guarantee_number ?? $guarantee->number ?? ('خطاب #' . $guarantee->id) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $tender->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('tenders.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
