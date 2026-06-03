@php
    $deductionRows = '';
    foreach ($components as $c) {
        $deductionRows .= '<tr><td>' . e($c['label']) . '</td><td class="amount">' . number_format((float) $c['amount'], 2) . '</td></tr>';
    }
@endphp

@if ($pdf)
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: dejavusans, sans-serif; direction: rtl; color: #2c2417; }
        h2 { text-align: center; color: #5c4a32; margin: 0 0 4px; }
        .sub { text-align: center; color: #6b5d45; margin-bottom: 14px; font-size: 13px; }
        .box { border: 1px solid #cbb79a; padding: 8px 10px; margin-bottom: 10px; font-size: 12px; }
        .box h4 { margin: 0 0 6px; color: #5c4a32; font-size: 13px; }
        .row span { display: inline-block; min-width: 48%; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 10px; }
        th, td { border: 1px solid #cbb79a; padding: 6px 8px; }
        th { background: #8b7355; color: #fff; text-align: center; }
        td.amount { text-align: left; direction: ltr; }
        tfoot td { font-weight: bold; }
        .totals td { background: #faf7f2; }
    </style>
</head>
<body>
    <h2>شهادة خصم وإضافة (نموذج 41)</h2>
    <div class="sub">{{ $companyName }}</div>

    <div class="box">
        <h4>بيانات الجهة (الخاصم)</h4>
        <div class="row">
            <span>الاسم: {{ $companyName }}</span>
            @if ($companyTaxNumber)<span>الرقم الضريبي: {{ $companyTaxNumber }}</span>@endif
            @if ($companyAddress)<span>العنوان: {{ $companyAddress }}</span>@endif
            @if ($companyPhone)<span>الهاتف: {{ $companyPhone }}</span>@endif
        </div>
    </div>

    <div class="box">
        <h4>بيانات المورّد (المخصوم منه)</h4>
        <div class="row">
            <span>الاسم: {{ $payment->supplier?->name ?? '—' }}</span>
            <span>الرقم الضريبي: {{ $payment->supplier?->tax_number ?: '—' }}</span>
            <span>تاريخ الدفعة: {{ $payment->payment_date?->format('Y-m-d') ?? '—' }}</span>
            <span>رقم المرجع: {{ $payment->reference_number ?: '—' }}</span>
        </div>
    </div>

    <table>
        <thead><tr><th>بند الخصم</th><th>القيمة</th></tr></thead>
        <tbody>
            @if (count($components))
                {!! $deductionRows !!}
            @else
                <tr><td colspan="2" style="text-align:center">لا توجد استقطاعات</td></tr>
            @endif
        </tbody>
        <tfoot>
            <tr class="totals"><td>إجمالي المخصوم (الاستقطاعات)</td><td class="amount">{{ number_format((float) $payment->total_deductions, 2) }}</td></tr>
        </tfoot>
    </table>

    <table>
        <tbody>
            <tr><td>الإجمالي المستحق (قبل الخصم)</td><td class="amount">{{ number_format((float) $payment->amount, 2) }}</td></tr>
            <tr><td>إجمالي المخصوم</td><td class="amount">{{ number_format((float) $payment->total_deductions, 2) }}</td></tr>
            <tr><td><strong>صافي المدفوع نقداً</strong></td><td class="amount"><strong>{{ number_format((float) $payment->netCash(), 2) }}</strong></td></tr>
        </tbody>
    </table>
</body>
</html>
@else
@extends('layouts.app')

@section('title', 'شهادة خصم وإضافة (نموذج 41)')

@section('content')
    <style>
        @media print {
            .no-print { display: none !important; }
            .sidebar, .topbar, nav.navbar, aside { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            .wht-head { background: #8b7355 !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        .wht-head th { background: #8b7355; color: #fff; }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="m-0">شهادة خصم وإضافة (نموذج 41)</h5>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm" style="background:#8b7355;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
            <a href="{{ route('supplier_payments.certificate', ['supplier_payment' => $payment, 'format' => 'pdf']) }}" class="btn btn-sm btn-danger"><i class="fa-solid fa-file-pdf ms-1"></i> PDF</a>
            <a href="{{ route('supplier_payments.show', $payment) }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body text-center">
            <h4 class="m-0">شهادة خصم وإضافة (نموذج 41)</h4>
            <div class="text-muted">{{ $companyName }}</div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card h-100"><div class="card-body">
                <h6 class="mb-3"><i class="fa-solid fa-building ms-1"></i> بيانات الجهة (الخاصم)</h6>
                <div class="mb-2"><span class="text-muted small">الاسم:</span> <span class="fw-semibold">{{ $companyName }}</span></div>
                @if ($companyTaxNumber)<div class="mb-2"><span class="text-muted small">الرقم الضريبي:</span> <span dir="ltr">{{ $companyTaxNumber }}</span></div>@endif
                @if ($companyAddress)<div class="mb-2"><span class="text-muted small">العنوان:</span> {{ $companyAddress }}</div>@endif
                @if ($companyPhone)<div class="mb-2"><span class="text-muted small">الهاتف:</span> <span dir="ltr">{{ $companyPhone }}</span></div>@endif
            </div></div>
        </div>
        <div class="col-md-6">
            <div class="card h-100"><div class="card-body">
                <h6 class="mb-3"><i class="fa-solid fa-user ms-1"></i> بيانات المورّد (المخصوم منه)</h6>
                <div class="mb-2"><span class="text-muted small">الاسم:</span> <span class="fw-semibold">{{ $payment->supplier?->name ?? '—' }}</span></div>
                <div class="mb-2"><span class="text-muted small">الرقم الضريبي:</span> <span dir="ltr">{{ $payment->supplier?->tax_number ?: '—' }}</span></div>
                <div class="mb-2"><span class="text-muted small">تاريخ الدفعة:</span> {{ $payment->payment_date?->format('Y-m-d') ?? '—' }}</div>
                <div class="mb-2"><span class="text-muted small">رقم المرجع:</span> <span dir="ltr">{{ $payment->reference_number ?: '—' }}</span></div>
            </div></div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h6 class="mb-3"><i class="fa-solid fa-scissors ms-1"></i> مكوّنات الخصم</h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="wht-head"><tr><th>بند الخصم</th><th class="text-start">القيمة</th></tr></thead>
                    <tbody>
                        @forelse ($components as $c)
                            <tr>
                                <td>{{ $c['label'] }}</td>
                                <td class="text-start" dir="ltr">{{ number_format((float) $c['amount'], 2) }} ج</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted">لا توجد استقطاعات</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td class="fw-semibold">إجمالي المخصوم (الاستقطاعات)</td>
                            <td class="text-start fw-bold text-danger" dir="ltr">{{ number_format((float) $payment->total_deductions, 2) }} ج</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <tbody>
                        <tr><td>الإجمالي المستحق (قبل الخصم)</td><td class="text-start fw-semibold" dir="ltr">{{ number_format((float) $payment->amount, 2) }} ج</td></tr>
                        <tr><td>إجمالي المخصوم</td><td class="text-start fw-semibold text-danger" dir="ltr">{{ number_format((float) $payment->total_deductions, 2) }} ج</td></tr>
                        <tr><td class="fw-semibold">صافي المدفوع نقداً</td><td class="text-start fw-bold text-success" dir="ltr">{{ number_format((float) $payment->netCash(), 2) }} ج</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
@endif
