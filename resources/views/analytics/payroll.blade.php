@extends('layouts.app')

@section('title', 'كشف الرواتب')

@section('content')
    <style>
        @media print {
            .no-print { display: none !important; }
            .sidebar, .topbar, nav.navbar, aside { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            .an-grand { background: #2b4c80 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        .an-grand { background: #2b4c80; color: #fff; font-weight: 700; }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="m-0">كشف الرواتب — {{ $monthLabel }}</h5>
        <div class="d-flex gap-2">
            <a href="{{ route('analytics.payroll', ['format' => 'xlsx', 'month' => $monthLabel]) }}" class="btn btn-sm btn-success"><i class="fa-solid fa-file-excel ms-1"></i> تصدير Excel</a>
            <button onclick="window.print()" class="btn btn-sm" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
        </div>
    </div>

    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">الشهر</label>
                    <input type="month" name="month" value="{{ $monthLabel }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-filter ms-1"></i> عرض</button>
                    <a href="{{ route('analytics.payroll') }}" class="btn btn-light">الشهر الحالي</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>الموظف</th>
                            <th>الوظيفة</th>
                            <th class="text-start">الراتب</th>
                            <th class="text-start">رصيد السلف</th>
                            <th class="text-start">رصيد العهدة</th>
                            <th class="text-start">صافي المستحقّ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $r)
                            <tr>
                                <td>{{ $r['name'] }}</td>
                                <td>{{ $r['jobTitle'] }}</td>
                                <td class="text-start fw-semibold">{{ number_format((float) $r['salary'], 2) }}</td>
                                <td class="text-start {{ (float) $r['advance'] > 0 ? 'text-danger' : '' }}">{{ number_format((float) $r['advance'], 2) }}</td>
                                <td class="text-start">{{ number_format((float) $r['custody'], 2) }}</td>
                                <td class="text-start fw-bold">{{ number_format((float) $r['net'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted text-center py-3">لا يوجد موظفون نشطون.</td></tr>
                        @endforelse
                    </tbody>
                    @if (count($rows))
                        <tfoot>
                            <tr class="an-grand">
                                <td colspan="2">الإجمالي</td>
                                <td class="text-start">{{ number_format((float) $totals['salary'], 2) }}</td>
                                <td class="text-start">{{ number_format((float) $totals['advance'], 2) }}</td>
                                <td class="text-start">{{ number_format((float) $totals['custody'], 2) }}</td>
                                <td class="text-start">{{ number_format((float) $totals['net'], 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            <div class="small text-muted mt-2">صافي المستحقّ = الراتب − رصيد السلف (قيمة إرشادية).</div>
        </div>
    </div>
@endsection
