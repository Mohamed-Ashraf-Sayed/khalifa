@extends('layouts.app')

@section('title', 'بيانات المصروف')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">{{ $expense->description }}</h5>
        <div class="d-flex gap-2">
            @can('expenses.edit')
                <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
            @endcan
            <a href="{{ route('expenses.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">البيان</div><div class="fw-semibold">{{ $expense->description }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الفئة</div><div><span class="badge text-bg-light">{{ \App\Models\Expense::CATEGORIES[$expense->category] ?? $expense->category }}</span></div></div>
                <div class="col-md-4"><div class="text-muted small">المبلغ</div><div class="fw-bold text-danger">{{ number_format($expense->amount, 2) }} ج</div></div>
                <div class="col-md-4"><div class="text-muted small">التاريخ</div><div>{{ $expense->expense_date->format('Y-m-d') }}</div></div>
                <div class="col-md-4"><div class="text-muted small">المشروع</div><div>{{ $expense->project?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">طريقة الدفع</div><div>{{ \App\Models\Expense::PAYMENT_METHODS[$expense->payment_method] ?? $expense->payment_method }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الحساب البنكي</div><div>{{ $expense->bankAccount?->name ?? '—' }}</div></div>
                @if ($expense->deliveredBy)<div class="col-md-4"><div class="text-muted small">مدفوع من عهدة</div><div>{{ $expense->deliveredBy->name }}</div></div>@endif
                @if ($expense->is_credit)
                    <div class="col-md-4"><div class="text-muted small">النوع</div><div><span class="badge text-bg-warning"><i class="fa-solid fa-clock ms-1"></i> مصروف آجل</span></div></div>
                @endif
                @if ($expense->due_date)<div class="col-md-4"><div class="text-muted small">تاريخ الاستحقاق</div><div>{{ $expense->due_date->format('Y-m-d') }}</div></div>@endif
                @if ($expense->creator)<div class="col-md-4"><div class="text-muted small">أضيف بواسطة</div><div>{{ $expense->creator->name }}</div></div>@endif
                @if ($expense->recipient)<div class="col-md-4"><div class="text-muted small">المستلِم</div><div>{{ $expense->recipient }}</div></div>@endif
                @if ($expense->reference_number)<div class="col-md-4"><div class="text-muted small">رقم المرجع</div><div>{{ $expense->reference_number }}</div></div>@endif
                @if ($expense->details)<div class="col-12"><div class="text-muted small">تفاصيل</div><div>{{ $expense->details }}</div></div>@endif
                @if ($expense->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $expense->notes }}</div></div>@endif
            </div>
        </div>
    </div>

    @php($pst = $expense->payment_status)
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 text-center align-items-center">
                <div class="col"><div class="text-muted small">المبلغ</div><div class="fs-5 fw-bold">{{ number_format($expense->amount, 2) }}</div></div>
                <div class="col"><div class="text-muted small">المدفوع</div><div class="fs-5 text-success">{{ number_format($expense->paid_amount, 2) }}</div></div>
                <div class="col"><div class="text-muted small">المتبقّي</div><div class="fs-5 fw-bold text-warning">{{ number_format($expense->remaining(), 2) }}</div></div>
                <div class="col">
                    <div class="text-muted small">حالة الدفع</div>
                    <div><span class="badge {{ $pst === 'paid' ? 'text-bg-success' : ($pst === 'partial' ? 'text-bg-warning' : 'text-bg-danger') }}">{{ \App\Models\Expense::PAYMENT_STATUSES[$pst] ?? $pst }}</span></div>
                </div>
            </div>
        </div>
    </div>

    @if ($expense->is_credit)
        @can('expenses.edit')
            <div class="card mb-3">
                <div class="card-header bg-white fw-semibold"><i class="fa-solid fa-plus ms-1"></i> إضافة قسط</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('expense_payments.store', $expense) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">المبلغ <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">التاريخ <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">طريقة الدفع</label>
                                <select name="payment_method" class="form-select">
                                    @foreach (\App\Models\Expense::PAYMENT_METHODS as $k => $label)
                                        <option value="{{ $k }}" @selected(old('payment_method') === $k)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">خصم من حساب بنكي <span class="text-muted small">(اختياري)</span></label>
                                <select name="bank_account_id" class="form-select">
                                    <option value="">— بدون —</option>
                                    @foreach ($accounts as $a)
                                        <option value="{{ $a->id }}" @selected((int) old('bank_account_id') === $a->id)>{{ $a->name }} ({{ number_format($a->current_balance, 2) }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">رقم المرجع</label>
                                <input type="text" name="reference_number" value="{{ old('reference_number') }}" class="form-control">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">ملاحظات</label>
                                <input type="text" name="notes" value="{{ old('notes') }}" class="form-control">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> إضافة القسط</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan

        <div class="card">
            <div class="card-header bg-white fw-semibold"><i class="fa-solid fa-list ms-1"></i> الأقساط المدفوعة</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>التاريخ</th>
                                <th>المبلغ</th>
                                <th>طريقة الدفع</th>
                                <th>الحساب البنكي</th>
                                <th>رقم المرجع</th>
                                <th>ملاحظات</th>
                                <th class="text-end">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($expense->payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                                    <td class="fw-bold text-success">{{ number_format($payment->amount, 2) }}</td>
                                    <td>{{ \App\Models\Expense::PAYMENT_METHODS[$payment->payment_method] ?? $payment->payment_method }}</td>
                                    <td>{{ $payment->bankAccount?->name ?? '—' }}</td>
                                    <td>{{ $payment->reference_number ?? '—' }}</td>
                                    <td>{{ $payment->notes ?? '—' }}</td>
                                    <td class="text-end">
                                        @can('expenses.edit')
                                            <form method="POST" action="{{ route('expense_payments.destroy', $payment) }}" class="d-inline" data-confirm="حذف القسط؟">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">لا توجد أقساط بعد.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @include('partials.attachments', ['model' => $expense])
@endsection
