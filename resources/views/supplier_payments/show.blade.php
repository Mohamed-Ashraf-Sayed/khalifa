@extends('layouts.app')

@section('title', 'بيانات الدفعة')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">دفعة مورد: {{ $payment->supplier?->name ?? '—' }}</h5>
        <div class="d-flex gap-2">
            @can('suppliers.edit')
                <a href="{{ route('supplier_payments.edit', $payment) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
            @endcan
            <a href="{{ route('supplier_payments.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">المورد</div><div class="fw-semibold">{{ $payment->supplier?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">المبلغ</div><div class="fw-bold text-danger">{{ number_format($payment->amount, 2) }} ج</div></div>
                <div class="col-md-4"><div class="text-muted small">التاريخ</div><div>{{ $payment->payment_date?->format('Y-m-d') ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">طريقة الدفع</div><div>{{ \App\Models\SupplierPayment::PAYMENT_METHODS[$payment->payment_method] ?? $payment->payment_method }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الحساب البنكي</div><div>{{ $payment->bankAccount?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">رقم المرجع</div><div dir="ltr" class="text-end">{{ $payment->reference_number ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">أضيفت بواسطة</div><div>{{ $payment->creator?->name ?? '—' }}</div></div>
                @if ($payment->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $payment->notes }}</div></div>@endif
            </div>
        </div>
    </div>

    @if ($payment->bankAccount)
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3">الحساب البنكي المرتبط</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>الحساب</th><th>اسم البنك</th><th>رقم الحساب</th></tr></thead>
                        <tbody>
                            <tr>
                                <td class="fw-semibold">{{ $payment->bankAccount->name }}</td>
                                <td>{{ $payment->bankAccount->bank_name ?: '—' }}</td>
                                <td dir="ltr" class="text-end">{{ $payment->bankAccount->account_number ?: '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
