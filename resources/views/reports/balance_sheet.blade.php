@extends('layouts.app')

@section('title', 'الميزانية العمومية')

@section('content')
    <style>
        @media print {
            .no-print { display: none !important; }
            .sidebar, .topbar, nav.navbar, aside { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            .bs-section-head { background: #f3efe9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        .bs-section-head { background: #f3efe9; color: #5c4a32; font-weight: 700; }
        .bs-grand { background: #8b7355; color: #fff; font-weight: 700; }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print flex-wrap gap-2">
        <h5 class="m-0">الميزانية العمومية</h5>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <form method="GET" action="{{ route('reports.balance_sheet') }}" class="d-flex gap-2 align-items-center">
                <label class="text-muted small m-0">كما في تاريخ</label>
                <input type="date" name="as_of" value="{{ request('as_of') }}" class="form-control form-control-sm" style="width:auto">
                <button type="submit" class="btn btn-sm" style="background:#8b7355;color:#fff"><i class="fa-solid fa-filter ms-1"></i> عرض</button>
                @if (request('as_of'))
                    <a href="{{ route('reports.balance_sheet') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-xmark"></i></a>
                @endif
            </form>
            <button onclick="window.print()" class="btn btn-sm" style="background:#8b7355;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
            <a href="{{ route('reports.balance_sheet', array_filter(['format' => 'pdf', 'as_of' => request('as_of')])) }}" class="btn btn-sm btn-danger"><i class="fa-solid fa-file-pdf ms-1"></i> PDF</a>
            <a href="{{ route('reports.balance_sheet', array_filter(['format' => 'xlsx', 'as_of' => request('as_of')])) }}" class="btn btn-sm btn-success"><i class="fa-solid fa-file-excel ms-1"></i> Excel</a>
            <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع للتقارير</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body text-center">
            <h4 class="m-0">الميزانية العمومية</h4>
            <div class="text-muted">{{ request('as_of') ? 'كما في تاريخ' : 'لقطة بتاريخ' }} {{ $asOf }}</div>
        </div>
    </div>

    <div class="row g-3">
        {{-- الأصول --}}
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-body">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr class="bs-section-head"><th colspan="2">الأصول</th></tr></thead>
                    <tbody>
                        <tr><td>النقدية والحسابات البنكية</td><td class="text-start fw-semibold">{{ number_format((float) $cash, 2) }}</td></tr>
                        <tr><td>المخزون (المواد)</td><td class="text-start fw-semibold">{{ number_format((float) $inventory, 2) }}</td></tr>
                        <tr><td>الأصول الثابتة (صافي القيمة الدفترية)</td><td class="text-start fw-semibold">{{ number_format((float) $fixedAssets, 2) }}</td></tr>
                        <tr>
                            <td>الذمم المدينة
                                <div class="small text-muted">فواتير: {{ number_format((float) $invoiceReceivables, 2) }} — إيرادات: {{ number_format((float) $revenueReceivables, 2) }}</div>
                            </td>
                            <td class="text-start fw-semibold">{{ number_format((float) $receivables, 2) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bs-grand"><td>إجمالي الأصول</td><td class="text-start">{{ number_format((float) $totalAssets, 2) }} ج</td></tr>
                    </tfoot>
                </table>
            </div></div>
        </div>

        {{-- الخصوم وحقوق الملكية --}}
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-body">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr class="bs-section-head"><th colspan="2">الخصوم</th></tr></thead>
                    <tbody>
                        <tr><td>مستحقّات المورّدين</td><td class="text-start fw-semibold">{{ number_format((float) $supplierPayables, 2) }}</td></tr>
                        <tr><td>مستحقّات المقاولين</td><td class="text-start fw-semibold">{{ number_format((float) $contractorPayables, 2) }}</td></tr>
                        <tr class="table-light"><td class="fw-semibold">إجمالي الخصوم</td><td class="text-start fw-semibold">{{ number_format((float) $payables, 2) }}</td></tr>
                    </tbody>
                    <thead><tr class="bs-section-head"><th colspan="2">حقوق الملكية</th></tr></thead>
                    <tbody>
                        <tr><td>رأس مال الشركاء</td><td class="text-start fw-semibold">{{ number_format((float) $partnerCapital, 2) }}</td></tr>
                        <tr>
                            <td>الأرباح المحتجزة</td>
                            <td class="text-start fw-semibold {{ (float) $retainedEarnings < 0 ? 'text-danger' : '' }}">{{ number_format((float) $retainedEarnings, 2) }}</td>
                        </tr>
                        <tr class="table-light"><td class="fw-semibold">إجمالي حقوق الملكية</td><td class="text-start fw-semibold">{{ number_format((float) $totalEquity, 2) }}</td></tr>
                    </tbody>
                    <tfoot>
                        <tr class="bs-grand"><td>إجمالي الخصوم وحقوق الملكية</td><td class="text-start">{{ number_format((float) $totalLiabilitiesPlusEquity, 2) }} ج</td></tr>
                    </tfoot>
                </table>
            </div></div>
        </div>
    </div>

    @if (abs((float) $settlementDifference) >= 0.01)
        <div class="alert mt-3" style="background:#fff7e6;border:1px solid #e0c98f;color:#7a5d18">
            <i class="fa-solid fa-scale-unbalanced ms-1"></i>
            <strong>فرق التسوية:</strong> {{ number_format((float) $settlementDifference, 2) }} ج
            <div class="small mt-1">هذا النموذج المبسّط قد لا يتوازن تماماً، ويُعرض الفرق هنا للعلم.</div>
        </div>
    @else
        <div class="alert alert-success mt-3"><i class="fa-solid fa-scale-balanced ms-1"></i> الميزانية متوازنة.</div>
    @endif
@endsection
