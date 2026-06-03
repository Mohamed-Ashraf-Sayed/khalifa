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
                @if ($expense->creator)<div class="col-md-4"><div class="text-muted small">أضيف بواسطة</div><div>{{ $expense->creator->name }}</div></div>@endif
                @if ($expense->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $expense->notes }}</div></div>@endif
            </div>
        </div>
    </div>
@endsection
