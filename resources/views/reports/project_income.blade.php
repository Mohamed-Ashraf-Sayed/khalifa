@extends('layouts.app')

@section('title', 'قائمة دخل المشروع')

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
        .is-grand { background: #8b7355; color: #fff; font-weight: 700; }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print flex-wrap gap-2">
        <h5 class="m-0">قائمة دخل المشروع</h5>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <button onclick="window.print()" class="btn btn-sm" style="background:#8b7355;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
            @if ($project)
                <a href="{{ route('reports.project_income', ['project_id' => $project->id, 'format' => 'pdf']) }}" class="btn btn-sm btn-danger"><i class="fa-solid fa-file-pdf ms-1"></i> PDF</a>
                <a href="{{ route('reports.project_income', ['project_id' => $project->id, 'format' => 'xlsx']) }}" class="btn btn-sm btn-success"><i class="fa-solid fa-file-excel ms-1"></i> Excel</a>
            @endif
            <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع للتقارير</a>
        </div>
    </div>

    {{-- اختيار المشروع --}}
    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label small">المشروع</label>
                    <select name="project_id" class="form-select" onchange="this.form.submit()">
                        @forelse ($projects as $p)
                            <option value="{{ $p->id }}" @selected($project && $project->id === $p->id)>{{ $p->name }}</option>
                        @empty
                            <option value="">لا توجد مشاريع</option>
                        @endforelse
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-filter ms-1"></i> عرض</button>
                </div>
            </form>
        </div>
    </div>

    @if (! $project)
        <div class="card"><div class="card-body text-center text-muted py-5">
            <i class="fa-solid fa-folder-open fa-2x mb-2"></i>
            <div>لا توجد مشاريع لعرض قائمة الدخل.</div>
        </div></div>
    @else
        <div class="card mb-3">
            <div class="card-body text-center">
                <h4 class="m-0">قائمة دخل المشروع</h4>
                <div class="fw-semibold mt-1">{{ $project->name }}</div>
                <div class="text-muted">قيمة العقد: {{ number_format((float) $contractValue, 2) }} ج</div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="table table-sm align-middle mb-0">
                    <tbody>
                        <tr class="is-head"><td colspan="2">الإيرادات</td></tr>
                        <tr><td>إجمالي الإيرادات</td><td class="text-start fw-semibold text-success">{{ number_format((float) $revenue, 2) }}</td></tr>
                        <tr><td>المحصّل</td><td class="text-start">{{ number_format((float) $collected, 2) }}</td></tr>
                        <tr><td>المتبقّي</td><td class="text-start {{ (float) $remainingRevenue > 0 ? 'text-danger' : '' }}">{{ number_format((float) $remainingRevenue, 2) }}</td></tr>

                        <tr class="is-head"><td colspan="2">التكاليف المباشرة</td></tr>
                        <tr><td>مستخلصات المقاولين</td><td class="text-start">{{ number_format((float) $contractorExtracts, 2) }}</td></tr>
                        <tr><td>توريدات المورّدين</td><td class="text-start">{{ number_format((float) $supplierSupplies, 2) }}</td></tr>
                        <tr><td>تكاليف المشروع</td><td class="text-start">{{ number_format((float) $projectCosts, 2) }}</td></tr>
                        <tr><td>مصروفات المشروع</td><td class="text-start">{{ number_format((float) $projectExpenses, 2) }}</td></tr>
                        <tr class="is-subtotal"><td>إجمالي التكاليف المباشرة</td><td class="text-start text-danger">{{ number_format((float) $totalCost, 2) }}</td></tr>
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
                            <td class="text-start {{ (float) $varianceVsContract < 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float) $varianceVsContract, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
@endsection
