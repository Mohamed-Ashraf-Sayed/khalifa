@extends('layouts.app')

@section('title', 'بيانات الدفعة')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">دفعة مورد: {{ $payment->supplier?->name ?? '—' }}</h5>
        <div class="d-flex gap-2">
            <a href="{{ route('supplier_payments.certificate', $payment) }}" class="btn btn-sm" style="background:#8b7355;color:#fff"><i class="fa-solid fa-file-contract ms-1"></i> شهادة خصم</a>
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
                <div class="col-md-4"><div class="text-muted small">الإجمالي المستحق (قبل الاستقطاع)</div><div class="fw-bold text-danger">{{ number_format($payment->amount, 2) }} ج</div></div>
                <div class="col-md-4"><div class="text-muted small">التاريخ</div><div>{{ $payment->payment_date?->format('Y-m-d') ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">طريقة الدفع</div><div>{{ \App\Models\SupplierPayment::PAYMENT_METHODS[$payment->payment_method] ?? $payment->payment_method }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الحساب البنكي</div><div>{{ $payment->bankAccount?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">رقم المرجع</div><div dir="ltr" class="text-end">{{ $payment->reference_number ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">أضيفت بواسطة</div><div>{{ $payment->creator?->name ?? '—' }}</div></div>
                @if ($payment->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $payment->notes }}</div></div>@endif
            </div>
        </div>
    </div>

    @php
        $deductionLabels = [
            'vat' => 'ضريبة القيمة المضافة',
            'insurance_5_percent' => 'تأمين 5%',
            'social_insurance' => 'تأمينات اجتماعية',
            'commercial_profit_supply' => 'أرباح تجارية (توريدات)',
            'commercial_profit_works' => 'أرباح تجارية (أعمال)',
            'engineering_professions' => 'مهن هندسية',
            'arts_specialists' => 'أخصائيو فنون',
            'applied_professions' => 'مهن تطبيقية',
            'bank_transfer_fee' => 'رسوم تحويل بنكي',
            'other_deductions' => 'استقطاعات أخرى',
        ];
    @endphp

    <div class="card mb-3">
        <div class="card-body">
            <h6 class="mb-3"><i class="fa-solid fa-scissors ms-1"></i> الاستقطاعات والمستحقات</h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <tbody>
                        @foreach ($deductionLabels as $field => $label)
                            @if (bccomp((string) $payment->$field, '0', 2) > 0)
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td class="text-end" dir="ltr">{{ number_format($payment->$field, 2) }} ج</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td class="fw-semibold">إجمالي الاستقطاعات</td>
                            <td class="text-end fw-bold text-danger" dir="ltr">{{ number_format($payment->total_deductions, 2) }} ج</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">صافي المدفوع نقداً</td>
                            <td class="text-end fw-bold text-success" dir="ltr">{{ number_format($payment->netCash(), 2) }} ج</td>
                        </tr>
                    </tfoot>
                </table>
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
