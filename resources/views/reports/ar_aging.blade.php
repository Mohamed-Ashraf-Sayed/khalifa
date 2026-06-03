@extends('layouts.app')

@section('title', 'أعمار الذمم المدينة')

@section('content')
    <style>
        @media print {
            .no-print { display: none !important; }
            .sidebar, .topbar, nav.navbar, aside { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            .ar-head { background: #f3efe9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        .ar-grand { background: #8b7355; color: #fff; font-weight: 700; }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="m-0">أعمار الذمم المدينة</h5>
        <div class="d-flex gap-2">
            <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-file-pdf ms-1"></i> PDF</a>
            <a href="{{ request()->fullUrlWithQuery(['format' => 'xlsx']) }}" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-file-excel ms-1"></i> Excel</a>
            <button onclick="window.print()" class="btn btn-sm" style="background:#8b7355;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
            <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع للتقارير</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body text-center">
            <h4 class="m-0">أعمار الذمم المدينة</h4>
            <div class="text-muted">لقطة بتاريخ {{ $asOf }}</div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light ar-head">
                        <tr>
                            <th>العميل</th>
                            <th class="text-start">0-30 يوم</th>
                            <th class="text-start">31-60 يوم</th>
                            <th class="text-start">61-90 يوم</th>
                            <th class="text-start">90+ يوم</th>
                            <th class="text-start">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clients as $c)
                            <tr>
                                <td>{{ $c['name'] }}</td>
                                <td class="text-start">{{ number_format((float) $c['b0'], 2) }}</td>
                                <td class="text-start">{{ number_format((float) $c['b30'], 2) }}</td>
                                <td class="text-start">{{ number_format((float) $c['b60'], 2) }}</td>
                                <td class="text-start {{ (float) $c['b90'] > 0 ? 'text-danger fw-semibold' : '' }}">{{ number_format((float) $c['b90'], 2) }}</td>
                                <td class="text-start fw-bold">{{ number_format((float) $c['total'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted text-center py-3">لا توجد ذمم مدينة مستحقّة.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="ar-grand">
                            <td>الإجمالي</td>
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
