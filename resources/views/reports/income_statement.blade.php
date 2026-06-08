@extends('layouts.app')

@section('title', 'قائمة الدخل')

@section('content')
    <style>
        @media print {
            .no-print { display: none !important; }
            .sidebar, .topbar, nav.navbar, aside { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            .is-head { background: #f3efe9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        .is-head { background: #f3efe9; color: #5c4a32; font-weight: 700; }
        .is-subtotal { background: #faf7f2; font-weight: 700; }
        .is-grand { background: #2b4c80; color: #fff; font-weight: 700; }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="m-0">قائمة الدخل</h5>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
            <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع للتقارير</a>
        </div>
    </div>

    {{-- فلتر الفترة --}}
    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">من تاريخ</label>
                    <input type="date" name="from" value="{{ $from }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">إلى تاريخ</label>
                    <input type="date" name="to" value="{{ $to }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-filter ms-1"></i> عرض</button>
                    <a href="{{ route('reports.income_statement') }}" class="btn btn-light">الكل</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body text-center">
            <h4 class="m-0">قائمة الدخل</h4>
            <div class="text-muted">
                @if ($from || $to)
                    عن الفترة {{ $from ?: '...' }} — {{ $to ?: '...' }}
                @else
                    كل الفترات
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-sm align-middle mb-0">
                <tbody>
                    <tr class="is-head"><td colspan="2">الإيرادات</td></tr>
                    <tr><td>إجمالي الإيرادات</td><td class="text-start fw-semibold text-success">{{ number_format((float) $revenue, 2) }}</td></tr>

                    <tr class="is-head"><td colspan="2">تكلفة المبيعات</td></tr>
                    <tr><td>مستخلصات المقاولين المعتمدة</td><td class="text-start">{{ number_format((float) $extractsCogs, 2) }}</td></tr>
                    <tr><td>توريدات المورّدين</td><td class="text-start">{{ number_format((float) $supplierCogs, 2) }}</td></tr>
                    <tr><td>مصروفات مباشرة (مواد/عمالة/معدات/نقل)</td><td class="text-start">{{ number_format((float) $directExpenseCogs, 2) }}</td></tr>
                    <tr class="is-subtotal"><td>إجمالي تكلفة المبيعات</td><td class="text-start text-danger">{{ number_format((float) $cogs, 2) }}</td></tr>

                    <tr class="is-subtotal">
                        <td>مجمل الربح</td>
                        <td class="text-start {{ (float) $grossProfit < 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format((float) $grossProfit, 2) }}
                            <span class="badge text-bg-light ms-1">هامش {{ number_format($grossMargin, 1) }}%</span>
                        </td>
                    </tr>

                    <tr class="is-head"><td colspan="2">المصروفات التشغيلية</td></tr>
                    <tr><td>مصروفات تشغيلية (مرافق/إدارية/أخرى)</td><td class="text-start text-danger">{{ number_format((float) $operatingExpenses, 2) }}</td></tr>
                </tbody>
                <tfoot>
                    <tr class="is-grand">
                        <td>صافي الربح / الخسارة</td>
                        <td class="text-start">
                            {{ number_format((float) $netProfit, 2) }} ج
                            <span class="badge text-bg-light text-dark ms-1">هامش {{ number_format($netMargin, 1) }}%</span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
