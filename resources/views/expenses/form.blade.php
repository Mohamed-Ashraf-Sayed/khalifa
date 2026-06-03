@extends('layouts.app')

@section('title', $expense->exists ? 'تعديل مصروف' : 'مصروف جديد')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $expense->exists ? route('expenses.update', $expense) : route('expenses.store') }}">
                @csrf
                @if ($expense->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">البيان <span class="text-danger">*</span></label>
                        <input type="text" name="description" value="{{ old('description', $expense->description) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المبلغ <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $expense->amount) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الفئة</label>
                        <select name="category" class="form-select">
                            @foreach ($categories as $k => $label)
                                <option value="{{ $k }}" @selected(old('category', $expense->category) === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المشروع</label>
                        <select name="project_id" class="form-select">
                            <option value="">— عام —</option>
                            @foreach ($projects as $p)
                                <option value="{{ $p->id }}" @selected((int) old('project_id', $expense->project_id) === $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">التاريخ <span class="text-danger">*</span></label>
                        <input type="date" name="expense_date" value="{{ old('expense_date', $expense->expense_date?->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">طريقة الدفع</label>
                        <select name="payment_method" class="form-select">
                            @foreach (\App\Models\Expense::PAYMENT_METHODS as $k => $label)
                                <option value="{{ $k }}" @selected(old('payment_method', $expense->payment_method) === $k)>{{ $label }}</option>
                            @endforeach
                            @foreach (\App\Models\CustomPaymentMethod::where('is_active', true)->get() as $m)
                                <option value="{{ $m->code }}" @selected(old('payment_method', $expense->payment_method) === $m->code)>{{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">خصم من حساب بنكي <span class="text-muted small">(اختياري — يسجّل سحباً من الحساب)</span></label>
                        <select name="bank_account_id" class="form-select">
                            <option value="">— بدون —</option>
                            @foreach ($accounts as $a)
                                <option value="{{ $a->id }}" @selected((int) old('bank_account_id', $expense->bank_account_id) === $a->id)>{{ $a->name }} ({{ number_format($a->current_balance, 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">مدفوع من عهدة الموظف <span class="text-muted small">(اختياري)</span></label>
                        <select name="delivered_by_employee_id" class="form-select">
                            <option value="">— لا —</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}" @selected((int) old('delivered_by_employee_id', $expense->delivered_by_employee_id) === $emp->id)>{{ $emp->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">لو اخترت موظف، يُخصم من عهدته ولا يُسحب من البنك.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ الاستحقاق <span class="text-muted small">(للمصروفات الآجلة)</span></label>
                        <input type="date" name="due_date" value="{{ old('due_date', $expense->due_date?->format('Y-m-d')) }}" class="form-control">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check">
                            <input type="hidden" name="is_credit" value="0">
                            <input type="checkbox" name="is_credit" value="1" id="is_credit" class="form-check-input" @checked(old('is_credit', $expense->is_credit))>
                            <label class="form-check-label" for="is_credit">مصروف آجل (بالتقسيط)</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-light border small mb-0">
                            <i class="fa-solid fa-circle-info ms-1"></i>
                            المصروف الآجل يُسدّد لاحقاً على دفعات (أقساط) من صفحة المصروف — اترك حقل الحساب البنكي فارغاً.
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">المستلِم</label>
                        <input type="text" name="recipient" value="{{ old('recipient', $expense->recipient) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">رقم المرجع</label>
                        <input type="text" name="reference_number" value="{{ old('reference_number', $expense->reference_number) }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">تفاصيل</label>
                        <textarea name="details" rows="2" class="form-control">{{ old('details', $expense->details) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $expense->notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('expenses.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
