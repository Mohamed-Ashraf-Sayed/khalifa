@extends('layouts.app')

@section('title', 'بيانات الدفعة')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">دفعة المقاول: {{ $payment->contractor?->name ?? '—' }}</h5>
        <div class="d-flex gap-2">
            @can('contractors.edit')
                <a href="{{ route('contractor_payments.edit', $payment) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
            @endcan
            <a href="{{ route('contractor_payments.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">المقاول</div><div class="fw-semibold">{{ $payment->contractor?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">المبلغ</div><div class="fw-bold text-danger">{{ number_format($payment->amount, 2) }} ج</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ الدفع</div><div>{{ $payment->payment_date?->format('Y-m-d') ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">طريقة الدفع</div><div>{{ \App\Models\ContractorPayment::PAYMENT_METHODS[$payment->payment_method] ?? $payment->payment_method }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الحساب البنكي</div><div>{{ $payment->bankAccount?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">رقم المرجع</div><div dir="ltr" class="text-end">{{ $payment->reference_number ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">المستخلص المرتبط</div>
                    <div>
                        @if ($payment->extract)
                            {{ $payment->extract->extract_number ?? ('#'.$payment->extract->id) }}
                        @else
                            —
                        @endif
                    </div>
                </div>
                <div class="col-md-4"><div class="text-muted small">سجّل بواسطة</div><div>{{ $payment->creator?->name ?? '—' }}</div></div>
                @if ($payment->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $payment->notes }}</div></div>@endif
            </div>
        </div>
    </div>

    @if ($payment->extract)
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3">المستخلص المرتبط</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>رقم المستخلص</th><th>التاريخ</th><th>صافي القيمة</th><th>الحالة</th></tr></thead>
                        <tbody>
                            <tr>
                                <td>{{ $payment->extract->extract_number ?? ('#'.$payment->extract->id) }}</td>
                                <td>{{ $payment->extract->extract_date?->format('Y-m-d') ?? '—' }}</td>
                                <td>{{ number_format($payment->extract->net_amount, 2) }}</td>
                                <td><span class="badge text-bg-light">{{ \App\Models\ContractorExtract::STATUSES[$payment->extract->status] ?? $payment->extract->status }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
