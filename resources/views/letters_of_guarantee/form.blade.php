@extends('layouts.app')

@section('title', $guarantee->exists ? 'تعديل خطاب ضمان' : 'خطاب ضمان جديد')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $guarantee->exists ? route('guarantees.update', $guarantee) : route('guarantees.store') }}">
                @csrf
                @if ($guarantee->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">رقم الخطاب</label>
                        <input type="text" value="{{ $lgNumber }}" class="form-control" disabled>
                        <div class="form-text">يُولّد تلقائياً</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">النوع <span class="text-danger">*</span></label>
                        <select name="type" class="form-select">
                            @foreach (\App\Models\LetterOfGuarantee::TYPES as $key => $label)
                                <option value="{{ $key }}" @selected(old('type', $guarantee->type) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            @foreach (\App\Models\LetterOfGuarantee::STATUSES as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $guarantee->status) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">المستفيد <span class="text-danger">*</span></label>
                        <input type="text" name="beneficiary" value="{{ old('beneficiary', $guarantee->beneficiary) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">القيمة <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount', $guarantee->amount) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">اسم البنك</label>
                        <input type="text" name="bank_name" value="{{ old('bank_name', $guarantee->bank_name) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الحساب البنكي</label>
                        <select name="bank_account_id" class="form-select">
                            <option value="">— بدون —</option>
                            @foreach ($bankAccounts as $account)
                                <option value="{{ $account->id }}" @selected((string) old('bank_account_id', $guarantee->bank_account_id) === (string) $account->id)>{{ $account->name }} ({{ $account->bank_name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ الإصدار <span class="text-danger">*</span></label>
                        <input type="date" name="issue_date" value="{{ old('issue_date', $guarantee->issue_date?->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ الانتهاء <span class="text-danger">*</span></label>
                        <input type="date" name="expiry_date" value="{{ old('expiry_date', $guarantee->expiry_date?->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المشروع</label>
                        <select name="project_id" class="form-select">
                            <option value="">— بدون —</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected((string) old('project_id', $guarantee->project_id) === (string) $project->id)>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $guarantee->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('guarantees.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
