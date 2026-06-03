@extends('layouts.app')

@section('title', 'أعمار الذمم الدائنة')

@section('content')
    <style>
        @media print {
            .no-print { display: none !important; }
            .sidebar, .topbar, nav.navbar, aside { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            .ap-head { background: #f3efe9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        .ap-grand { background: #8b7355; color: #fff; font-weight: 700; }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="m-0">أعمار الذمم الدائنة</h5>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm" style="background:#8b7355;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
            <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع للتقارير</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body text-center">
            <h4 class="m-0">أعمار الذمم الدائنة</h4>
            <div class="text-muted">لقطة بتاريخ {{ $asOf }}</div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light ap-head">
                        <tr>
                            <th>الجهة</th>
                            <th>النوع</th>
                            <th class="text-start">0-30 يوم</th>
                            <th class="text-start">31-60 يوم</th>
                            <th class="text-start">61-90 يوم</th>
                            <th class="text-start">90+ يوم</th>
                            <th class="text-start">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $r)
                            <tr>
                                <td>{{ $r['name'] }}</td>
                                <td><span class="badge text-bg-light">{{ $r['kind'] }}</span></td>
                                <td class="text-start">{{ number_format((float) $r['b0'], 2) }}</td>
                                <td class="text-start">{{ number_format((float) $r['b30'], 2) }}</td>
                                <td class="text-start">{{ number_format((float) $r['b60'], 2) }}</td>
                                <td class="text-start {{ (float) $r['b90'] > 0 ? 'text-danger fw-semibold' : '' }}">{{ number_format((float) $r['b90'], 2) }}</td>
                                <td class="text-start fw-bold">{{ number_format((float) $r['total'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted text-center py-3">لا توجد ذمم دائنة مستحقّة.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="ap-grand">
                            <td colspan="2">الإجمالي</td>
                            <td class="text-start">{{ number_format((float) $totals['b0'], 2) }}</td>
                            <td class="text-start">{{ number_format((float) $totals['b30'], 2) }}</td>
                            <td class="text-start">{{ number_format((float) $totals['b60'], 2) }}</td>
                            <td class="text-start">{{ number_format((float) $totals['b90'], 2) }}</td>
                            <td class="text-start">{{ number_format((float) $totals['total'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
