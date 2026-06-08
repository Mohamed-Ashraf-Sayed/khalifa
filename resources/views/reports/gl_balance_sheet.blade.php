@extends('layouts.app')

@section('title', 'المركز المالي (محاسبي)')

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
        <h5 class="m-0">المركز المالي (محاسبي)</h5>
        <div class="d-flex gap-2">
            <a href="{{ request()->fullUrlWithQuery(['export' => 'xlsx']) }}" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-file-excel ms-1"></i> Excel</a>
            <button onclick="window.print()" class="btn btn-sm" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
        </div>
    </div>

    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">كما في تاريخ</label>
                    <input type="date" name="to" value="{{ $to }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-filter ms-1"></i> عرض</button>
                    <a href="{{ route('accounting.balance_sheet') }}" class="btn btn-light">اليوم</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body text-center">
            <h4 class="m-0">المركز المالي (محاسبي)</h4>
            <div class="text-muted">@if ($to) كما في {{ $to }} @else بكل الحركات المرحّلة @endif</div>
            <div class="mt-2">
                @if ($balanced)
                    <span class="badge text-bg-success fs-6">متوازن ✓</span>
                @else
                    <span class="badge text-bg-danger fs-6">غير متوازن — فرق {{ number_format((float) $difference, 2) }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <table class="table table-sm align-middle mb-0">
                        <tbody>
                            <tr class="is-head"><td colspan="2">الأصول</td></tr>
                            @forelse ($assets as $r)
                                <tr><td>{{ $r['name'] }}</td><td class="text-start">{{ number_format((float) $r['value'], 2) }}</td></tr>
                            @empty
                                <tr><td class="text-muted">لا توجد أصول</td><td class="text-start">0.00</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="is-grand"><td>إجمالي الأصول</td><td class="text-start">{{ number_format((float) $totalAssets, 2) }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <table class="table table-sm align-middle mb-0">
                        <tbody>
                            <tr class="is-head"><td colspan="2">الخصوم</td></tr>
                            @forelse ($liabilities as $r)
                                <tr><td>{{ $r['name'] }}</td><td class="text-start">{{ number_format((float) $r['value'], 2) }}</td></tr>
                            @empty
                                <tr><td class="text-muted">لا توجد خصوم</td><td class="text-start">0.00</td></tr>
                            @endforelse
                            <tr class="is-subtotal"><td>إجمالي الخصوم</td><td class="text-start">{{ number_format((float) $totalLiabilities, 2) }}</td></tr>

                            <tr class="is-head"><td colspan="2">حقوق الملكية</td></tr>
                            @forelse ($equity as $r)
                                <tr><td>{{ $r['name'] }}</td><td class="text-start">{{ number_format((float) $r['value'], 2) }}</td></tr>
                            @empty
                                <tr><td class="text-muted">لا توجد حقوق ملكية</td><td class="text-start">0.00</td></tr>
                            @endforelse
                            <tr><td>صافي ربح الفترة</td><td class="text-start {{ bccomp($netProfit, '0', 2) >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $netProfit, 2) }}</td></tr>
                            <tr class="is-subtotal"><td>إجمالي حقوق الملكية</td><td class="text-start">{{ number_format((float) $totalEquityWithProfit, 2) }}</td></tr>
                        </tbody>
                        <tfoot>
                            <tr class="is-grand"><td>إجمالي الخصوم وحقوق الملكية</td><td class="text-start">{{ number_format((float) $totalLiabilitiesEquity, 2) }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
