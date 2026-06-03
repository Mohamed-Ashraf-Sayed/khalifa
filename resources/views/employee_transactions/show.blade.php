@extends('layouts.app')

@section('title', 'بيانات المعاملة')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">معاملة - {{ $transaction->employee?->name ?? '—' }}</h5>
        <div class="d-flex gap-2">
            @can('employees.edit')
                <a href="{{ route('employee_transactions.edit', $transaction) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
            @endcan
            <a href="{{ route('employee_transactions.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">الموظف</div><div class="fw-semibold">{{ $transaction->employee?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">النوع</div><div><span class="badge text-bg-light">{{ \App\Models\EmployeeTransaction::TYPES[$transaction->type] ?? $transaction->type }}</span></div></div>
                <div class="col-md-4"><div class="text-muted small">المبلغ</div><div class="fw-bold">{{ number_format($transaction->amount, 2) }}</div></div>
                <div class="col-md-4"><div class="text-muted small">التاريخ</div><div>{{ $transaction->transaction_date?->format('Y-m-d') ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">المشروع</div><div>{{ $transaction->project?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">أضيفت بواسطة</div><div>{{ $transaction->creator?->name ?? '—' }}</div></div>
                <div class="col-md-12"><div class="text-muted small">البيان</div><div>{{ $transaction->description ?: '—' }}</div></div>
                @if ($transaction->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $transaction->notes }}</div></div>@endif
            </div>
        </div>
    </div>

    @if ($transaction->employee)
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3">بيانات الموظف</h6>
                <div class="row g-3">
                    <div class="col-md-4"><div class="text-muted small">الاسم</div><div class="fw-semibold">{{ $transaction->employee->name }}</div></div>
                    <div class="col-md-4"><div class="text-muted small">الوظيفة</div><div>{{ $transaction->employee->job_title ?: '—' }}</div></div>
                    <div class="col-md-4"><div class="text-muted small">الهاتف</div><div dir="ltr" class="text-end">{{ $transaction->employee->phone ?: '—' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">رصيد السلف المستحقّ</div><div class="fw-bold">{{ number_format((float) $transaction->employee->advanceBalance(), 2) }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">رصيد العهدة</div><div class="fw-bold">{{ number_format((float) $transaction->employee->custodyBalance(), 2) }}</div></div>
                </div>
            </div>
        </div>
    @endif
@endsection
