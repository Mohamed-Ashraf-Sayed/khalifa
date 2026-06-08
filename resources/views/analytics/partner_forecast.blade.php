@extends('layouts.app')

@section('title', 'توقّعات أرباح الشركاء')

@section('content')
    <style>
        @media print {
            .no-print { display: none !important; }
            .sidebar, .topbar, nav.navbar, aside { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            .an-grand { background: #2b4c80 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .an-partner { background: #f3efe9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .an-over { background: #fdecec !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        .an-grand { background: #2b4c80; color: #fff; font-weight: 700; }
        .an-partner { background: #f3efe9; color: #5c4a32; font-weight: 700; }
        .an-over { background: #fdecec; }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="m-0">توقّعات أرباح الشركاء</h5>
        <div class="d-flex gap-2">
            <a href="{{ route('analytics.partner_forecast', ['format' => 'xlsx']) }}" class="btn btn-sm btn-success"><i class="fa-solid fa-file-excel ms-1"></i> تصدير Excel</a>
            <button onclick="window.print()" class="btn btn-sm" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card"><div class="card-body">
                <div class="text-muted small">إجمالي الالتزامات القادمة</div>
                <div class="fs-4 fw-bold" style="color:#2b4c80">{{ number_format((float) $totals['upcoming'], 2) }} ج</div>
            </div></div>
        </div>
        <div class="col-md-6">
            <div class="card"><div class="card-body">
                <div class="text-muted small">إجمالي المتأخّر (مستحقّ ولم يُدفع)</div>
                <div class="fs-4 fw-bold text-danger">{{ number_format((float) $totals['overdue'], 2) }} ج</div>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>تاريخ الاستحقاق</th>
                            <th class="text-start">المبلغ</th>
                            <th class="text-start">الحالة</th>
                        </tr>
                    </thead>
                    @forelse ($partners as $p)
                        <tbody>
                            <tr class="an-partner">
                                <td colspan="2">{{ $p['name'] }}</td>
                                <td class="text-start">إجمالي قادم: {{ number_format((float) $p['total'], 2) }}</td>
                            </tr>
                            @foreach ($p['items'] as $item)
                                <tr class="{{ $item['overdue'] ? 'an-over' : '' }}">
                                    <td>{{ $item['due_date'] }}</td>
                                    <td class="text-start fw-semibold">{{ number_format((float) $item['amount'], 2) }}</td>
                                    <td class="text-start">
                                        @if ($item['overdue'])
                                            <span class="badge bg-danger">متأخّر</span>
                                        @else
                                            <span class="badge bg-secondary">قادم</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    @empty
                        <tbody>
                            <tr><td colspan="3" class="text-muted text-center py-3">لا توجد مستحقّات أرباح غير مدفوعة.</td></tr>
                        </tbody>
                    @endforelse
                    @if (count($partners))
                        <tfoot>
                            <tr class="an-grand">
                                <td>الإجمالي القادم</td>
                                <td class="text-start">{{ number_format((float) $totals['upcoming'], 2) }}</td>
                                <td class="text-start">منه متأخّر: {{ number_format((float) $totals['overdue'], 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
