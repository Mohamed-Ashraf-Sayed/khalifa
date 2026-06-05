@extends('layouts.app')

@section('title', $quotation->exists ? 'تعديل عرض سعر' : 'عرض سعر جديد')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $quotation->exists ? route('quotations.update', $quotation) : route('quotations.store') }}">
                @csrf
                @if ($quotation->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">رقم العرض <span class="text-danger">*</span></label>
                        <input type="text" name="quotation_number" value="{{ old('quotation_number', $quotation->quotation_number) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">العميل <span class="text-danger">*</span></label>
                        <select name="client_id" class="form-select" required>
                            <option value="">— اختر —</option>
                            @foreach ($clients as $c)
                                <option value="{{ $c->id }}" @selected((int) old('client_id', $quotation->client_id) === $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المشروع</label>
                        <select name="project_id" class="form-select">
                            <option value="">— بدون —</option>
                            @foreach ($projects as $p)
                                <option value="{{ $p->id }}" @selected((int) old('project_id', $quotation->project_id) === $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">تاريخ الإصدار <span class="text-danger">*</span></label>
                        <input type="date" name="issue_date" value="{{ old('issue_date', $quotation->issue_date?->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">صالح حتى</label>
                        <input type="date" name="valid_until" value="{{ old('valid_until', $quotation->valid_until?->format('Y-m-d')) }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">نسبة الضريبة %</label>
                        <input type="number" step="0.01" min="0" max="100" name="tax_rate" value="{{ old('tax_rate', $quotation->tax_rate ?? 0) }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            @foreach (\App\Models\Quotation::STATUSES as $k => $label)
                                <option value="{{ $k }}" @selected(old('status', $quotation->status) === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $quotation->notes) }}</textarea>
                    </div>
                </div>

                <div class="alert alert-light border mt-3 mb-0"><i class="fa-solid fa-circle-info ms-1"></i> البنود والإجماليات تُضاف من صفحة العرض بعد الحفظ.</div>

                <div class="mt-3 d-flex gap-2">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('quotations.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
