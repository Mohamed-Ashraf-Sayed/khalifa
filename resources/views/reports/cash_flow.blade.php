@extends('layouts.app')

@section('title', 'التدفّق النقدي')

@section('content')
    <style>
        @media print {
            .no-print { display: none !important; }
            .sidebar, .topbar, nav.navbar, aside { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            .cf-head { background: #f3efe9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        .cf-grand { background: #8b7355; color: #fff; font-weight: 700; }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="m-0">التدفّق النقدي</h5>
        <div class="d-flex gap-2">
            <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-file-pdf ms-1"></i> PDF</a>
            <a href="{{ request()->fullUrlWithQuery(['format' => 'xlsx']) }}" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-file-excel ms-1"></i> Excel</a>
            <button onclick="window.print()" class="btn btn-sm" style="background:#8b7355;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
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
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-filter ms-1"></i> عرض</button>
                    <a href="{{ route('reports.cash_flow') }}" class="btn btn-light">الكل</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <div class="text-muted small">إجمالي التدفّق الداخل</div>
                <div class="fs-4 fw-bold text-success">{{ number_format((float) $totalInflow, 2) }} ج</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <div class="text-muted small">إجمالي التدفّق الخارج</div>
                <div class="fs-4 fw-bold text-danger">{{ number_format((float) $totalOutflow, 2) }} ج</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <div class="text-muted small">صافي التدفّق النقدي</div>
                <div class="fs-4 fw-bold {{ (float) $totalNet >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $totalNet, 2) }} ج</div>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light cf-head">
                        <tr>
                            <th>الشهر</th>
                            <th class="text-start">تدفّق داخل</th>
                            <th class="text-start">تدفّق خارج</th>
                            <th class="text-start">الصافي</th>
                            <th class="text-start">الرصيد التراكمي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($months as $m)
                            <tr>
                                <td>{{ $m['month'] }}</td>
                                <td class="text-start text-success">{{ number_format((float) $m['inflow'], 2) }}</td>
                                <td class="text-start text-danger">{{ number_format((float) $m['outflow'], 2) }}</td>
                                <td class="text-start fw-semibold {{ (float) $m['net'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $m['net'], 2) }}</td>
                                <td class="text-start fw-bold">{{ number_format((float) $m['running'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted text-center py-3">لا توجد حركات نقدية في الفترة.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="cf-grand">
                            <td>الإجمالي</td>
                            <td class="text-start">{{ number_format((float) $totalInflow, 2) }}</td>
                            <td class="text-start">{{ number_format((float) $totalOutflow, 2) }}</td>
                            <td class="text-start">{{ number_format((float) $totalNet, 2) }}</td>
                            <td class="text-start">{{ number_format((float) $closingCash, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
