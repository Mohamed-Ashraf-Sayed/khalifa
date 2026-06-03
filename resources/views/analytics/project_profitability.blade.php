@extends('layouts.app')

@section('title', 'ربحية المشاريع')

@section('content')
    <style>
        @media print {
            .no-print { display: none !important; }
            .sidebar, .topbar, nav.navbar, aside { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            .an-grand { background: #8b7355 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        .an-grand { background: #8b7355; color: #fff; font-weight: 700; }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="m-0">ربحية المشاريع</h5>
        <div class="d-flex gap-2">
            <a href="{{ route('analytics.project_profitability', ['format' => 'xlsx']) }}" class="btn btn-sm btn-success"><i class="fa-solid fa-file-excel ms-1"></i> تصدير Excel</a>
            <button onclick="window.print()" class="btn btn-sm" style="background:#8b7355;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>المشروع</th>
                            <th class="text-start">قيمة العقد</th>
                            <th class="text-start">التكلفة الفعلية</th>
                            <th class="text-start">الإيراد</th>
                            <th class="text-start">المحصّل</th>
                            <th class="text-start">الربح</th>
                            <th class="text-start">هامش الربح %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $r)
                            <tr>
                                <td>{{ $r['name'] }}</td>
                                <td class="text-start">{{ number_format((float) $r['contract'], 2) }}</td>
                                <td class="text-start text-danger">{{ number_format((float) $r['cost'], 2) }}</td>
                                <td class="text-start text-success">{{ number_format((float) $r['revenue'], 2) }}</td>
                                <td class="text-start">{{ number_format((float) $r['collected'], 2) }}</td>
                                <td class="text-start fw-bold {{ (float) $r['profit'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $r['profit'], 2) }}</td>
                                <td class="text-start {{ $r['margin'] >= 0 ? '' : 'text-danger' }}">{{ number_format($r['margin'], 2) }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted text-center py-3">لا توجد مشاريع.</td></tr>
                        @endforelse
                    </tbody>
                    @if (count($rows))
                        <tfoot>
                            <tr class="an-grand">
                                <td>الإجمالي</td>
                                <td class="text-start">{{ number_format((float) $totals['contract'], 2) }}</td>
                                <td class="text-start">{{ number_format((float) $totals['cost'], 2) }}</td>
                                <td class="text-start">{{ number_format((float) $totals['revenue'], 2) }}</td>
                                <td class="text-start">{{ number_format((float) $totals['collected'], 2) }}</td>
                                <td class="text-start">{{ number_format((float) $totals['profit'], 2) }}</td>
                                <td class="text-start">{{ number_format($totals['margin'], 2) }}%</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
