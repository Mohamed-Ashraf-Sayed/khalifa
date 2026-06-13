@extends('layouts.app')

@section('title', 'قائمة دخل المشروع')

@section('content')
    <style>
        @media print {
            .no-print { display: none !important; }
            .sidebar, .topbar, nav.navbar, aside { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            .is-head td, .is-grand td, .statcard { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        .is-table td { padding: .72rem .95rem; vertical-align: middle; }
        .is-table .is-head td { background: var(--brown-50); color: var(--brown-dark); font-weight: 700; font-size: .8rem; letter-spacing: .02em; border: 0; }
        .is-table .is-row td { border-bottom: 1px solid var(--line-2); }
        .is-table .is-row td:first-child { color: var(--ink-2); }
        .is-table .is-subtotal td { background: var(--bg); font-weight: 700; border-top: 1px solid var(--line); }
        .is-table .is-grand td { background: var(--brown); color: #fff; font-weight: 800; font-size: 1.02rem; }
        .is-num { font-variant-numeric: tabular-nums; font-weight: 600; }
    </style>

    {{-- شريط الإجراءات --}}
    <div class="d-flex justify-content-between align-items-center mb-3 no-print flex-wrap gap-2">
        <h5 class="m-0"><i class="fa-solid fa-chart-line ms-1" style="color:#2b4c80"></i> قائمة دخل المشروع</h5>
        <div class="d-flex gap-2 flex-wrap">
            <button onclick="window.print()" class="btn btn-sm" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
            @if ($project)
                <a href="{{ route('reports.project_income', ['project_id' => $project->id, 'format' => 'pdf']) }}" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-file-pdf ms-1"></i> PDF</a>
                <a href="{{ route('reports.project_income', ['project_id' => $project->id, 'format' => 'xlsx']) }}" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-file-excel ms-1"></i> Excel</a>
            @endif
            <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع للتقارير</a>
        </div>
    </div>

    {{-- اختيار المشروع --}}
    <form method="GET" class="filter-bar row g-2 align-items-end mb-3 no-print">
        <div class="col-12 col-md-5">
            <label class="form-label">المشروع</label>
            <select name="project_id" class="form-select" onchange="this.form.submit()">
                @forelse ($projects as $p)
                    <option value="{{ $p->id }}" @selected($project && $project->id === $p->id)>{{ $p->name }}</option>
                @empty
                    <option value="">لا توجد مشاريع</option>
                @endforelse
            </select>
        </div>
        <div class="col-auto">
            <div class="filter-actions">
                <button class="btn btn-primary"><i class="fa-solid fa-eye ms-1"></i> عرض</button>
            </div>
        </div>
    </form>

    @if (! $project)
        <div class="card"><div class="card-body text-center text-muted py-5">
            <i class="fa-solid fa-folder-open fa-2x mb-2"></i>
            <div>لا توجد مشاريع لعرض قائمة الدخل.</div>
        </div></div>
    @else
        @php($profitColor = (float) $grossProfit >= 0 ? 'success' : 'danger')

        {{-- ترويسة المشروع --}}
        <div class="card mb-3"><div class="card-body d-flex align-items-center gap-3 flex-wrap">
            <span class="entity-avatar"><i class="fa-solid fa-diagram-project"></i></span>
            <div class="flex-grow-1">
                <div class="h5 mb-0">{{ $project->name }}</div>
                <div class="text-muted small">قيمة العقد: {{ number_format((float) $contractValue, 2) }} ج</div>
            </div>
        </div></div>

        {{-- مؤشرات سريعة --}}
        <div class="row g-3 mb-3">
            @foreach ([
                ['إجمالي الإيرادات', number_format((float) $revenue, 0).' ج', 'fa-sack-dollar', 'success'],
                ['إجمالي التكاليف', number_format((float) $totalCost, 0).' ج', 'fa-money-bill-trend-up', 'danger'],
                ['صافي ربح المشروع', number_format((float) $grossProfit, 0).' ج', 'fa-equals', $profitColor],
                ['هامش الربح', number_format((float) $margin, 1).'%', 'fa-percent', 'info'],
            ] as [$l, $v, $icon, $c])
            <div class="col-md-3 col-6">
                <div class="statcard sc-{{ $c }} h-100">
                    <span class="sc-ic"><i class="fa-solid {{ $icon }}"></i></span>
                    <span><span class="sc-v d-block">{{ $v }}</span><span class="sc-l d-block">{{ $l }}</span></span>
                </div>
            </div>
            @endforeach
        </div>

        {{-- قائمة الدخل التفصيلية --}}
        <div class="card">
            <div class="card-body p-0">
                <table class="table is-table align-middle mb-0">
                    <tbody>
                        <tr class="is-head"><td><i class="fa-solid fa-arrow-trend-up ms-1"></i> الإيرادات</td><td class="text-start">القيمة (ج)</td></tr>
                        <tr class="is-row"><td>إجمالي الإيرادات</td><td class="text-start is-num text-success">{{ number_format((float) $revenue, 2) }}</td></tr>
                        <tr class="is-row"><td>المحصّل</td><td class="text-start is-num">{{ number_format((float) $collected, 2) }}</td></tr>
                        <tr class="is-row"><td>المتبقّي على العميل</td><td class="text-start is-num {{ (float) $remainingRevenue > 0 ? 'text-danger' : 'text-muted' }}">{{ number_format((float) $remainingRevenue, 2) }}</td></tr>

                        <tr class="is-head"><td><i class="fa-solid fa-arrow-trend-down ms-1"></i> التكاليف المباشرة</td><td class="text-start"></td></tr>
                        <tr class="is-row"><td>مستخلصات المقاولين</td><td class="text-start is-num">{{ number_format((float) $contractorExtracts, 2) }}</td></tr>
                        <tr class="is-row"><td>توريدات المورّدين</td><td class="text-start is-num">{{ number_format((float) $supplierSupplies, 2) }}</td></tr>
                        <tr class="is-row"><td>تكاليف المشروع</td><td class="text-start is-num">{{ number_format((float) $projectCosts, 2) }}</td></tr>
                        <tr class="is-row"><td>مصروفات المشروع</td><td class="text-start is-num">{{ number_format((float) $projectExpenses, 2) }}</td></tr>
                        <tr class="is-subtotal"><td>إجمالي التكاليف المباشرة</td><td class="text-start is-num text-danger">{{ number_format((float) $totalCost, 2) }}</td></tr>
                    </tbody>
                    <tfoot>
                        <tr class="is-grand">
                            <td>مجمل / صافي ربح المشروع</td>
                            <td class="text-start">
                                {{ number_format((float) $grossProfit, 2) }} ج
                                <span class="badge text-bg-light text-dark ms-1">هامش {{ number_format((float) $margin, 2) }}%</span>
                            </td>
                        </tr>
                        <tr class="is-subtotal">
                            <td>الفرق (قيمة العقد − التكلفة)</td>
                            <td class="text-start is-num {{ (float) $varianceVsContract < 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float) $varianceVsContract, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
@endsection
