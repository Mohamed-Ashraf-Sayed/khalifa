@extends('layouts.app')

@section('title', 'أداء المورّدين')

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
        <h5 class="m-0">أداء المورّدين</h5>
        <div class="d-flex gap-2">
            <a href="{{ route('analytics.supplier_performance', ['format' => 'xlsx']) }}" class="btn btn-sm btn-success"><i class="fa-solid fa-file-excel ms-1"></i> تصدير Excel</a>
            <button onclick="window.print()" class="btn btn-sm" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>المورّد</th>
                            <th class="text-start">المشتريات</th>
                            <th class="text-start">المسدّد</th>
                            <th class="text-start">الرصيد المستحقّ</th>
                            <th class="text-start">عدد الأوامر</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $r)
                            <tr>
                                <td>
                                    @can('suppliers.view')
                                        <a href="{{ route('suppliers.show', $r['id']) }}" class="text-reset fw-semibold">{{ $r['name'] }}</a>
                                    @else
                                        {{ $r['name'] }}
                                    @endcan
                                </td>
                                <td class="text-start fw-semibold">{{ number_format((float) $r['purchases'], 2) }}</td>
                                <td class="text-start text-success">{{ number_format((float) $r['paid'], 2) }}</td>
                                <td class="text-start fw-bold {{ (float) $r['balance'] > 0 ? 'text-danger' : '' }}">{{ number_format((float) $r['balance'], 2) }}</td>
                                <td class="text-start">{{ $r['orders'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted text-center py-3">لا يوجد موردون.</td></tr>
                        @endforelse
                    </tbody>
                    @if (count($rows))
                        <tfoot>
                            <tr class="an-grand">
                                <td>الإجمالي</td>
                                <td class="text-start">{{ number_format((float) $totals['purchases'], 2) }}</td>
                                <td class="text-start">{{ number_format((float) $totals['paid'], 2) }}</td>
                                <td class="text-start">{{ number_format((float) $totals['balance'], 2) }}</td>
                                <td class="text-start">—</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
