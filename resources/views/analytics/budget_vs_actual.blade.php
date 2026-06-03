@extends('layouts.app')

@section('title', 'الموازنة مقابل الفعلي')

@section('content')
    <style>
        @media print {
            .no-print { display: none !important; }
            .sidebar, .topbar, nav.navbar, aside { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            .an-grand { background: #8b7355 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .an-over { background: #fdecec !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        .an-grand { background: #8b7355; color: #fff; font-weight: 700; }
        .an-over { background: #fdecec; }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="m-0">الموازنة مقابل الفعلي</h5>
        <div class="d-flex gap-2">
            <a href="{{ route('analytics.budget_vs_actual', ['format' => 'xlsx']) }}" class="btn btn-sm btn-success"><i class="fa-solid fa-file-excel ms-1"></i> تصدير Excel</a>
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
                            <th class="text-start">الموازنة</th>
                            <th class="text-start">الفعلي</th>
                            <th class="text-start">الانحراف</th>
                            <th class="text-start">نسبة الاستهلاك %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $r)
                            <tr class="{{ $r['over'] ? 'an-over' : '' }}">
                                <td>{{ $r['name'] }}</td>
                                <td class="text-start">{{ number_format((float) $r['budget'], 2) }}</td>
                                <td class="text-start {{ $r['over'] ? 'text-danger fw-bold' : '' }}">{{ number_format((float) $r['actual'], 2) }}</td>
                                <td class="text-start fw-bold {{ (float) $r['variance'] < 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float) $r['variance'], 2) }}</td>
                                <td class="text-start {{ $r['over'] ? 'text-danger fw-bold' : '' }}">{{ number_format($r['used'], 2) }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted text-center py-3">لا توجد مشاريع.</td></tr>
                        @endforelse
                    </tbody>
                    @if (count($rows))
                        <tfoot>
                            <tr class="an-grand">
                                <td>الإجمالي</td>
                                <td class="text-start">{{ number_format((float) $totals['budget'], 2) }}</td>
                                <td class="text-start">{{ number_format((float) $totals['actual'], 2) }}</td>
                                <td class="text-start">{{ number_format((float) $totals['variance'], 2) }}</td>
                                <td class="text-start">{{ number_format($totals['used'], 2) }}%</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
