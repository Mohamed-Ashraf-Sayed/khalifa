@extends('layouts.app')

@section('title', 'التقارير المالية')

@push('styles')
<style>
    .rt-group-title { font-size:.78rem; font-weight:700; color:var(--muted); letter-spacing:.4px; margin:.2rem 0 .6rem; display:flex; align-items:center; gap:.5rem; }
    .rt-group-title::after { content:''; flex:1; height:1px; background:var(--line); }
    .report-tile { display:flex; align-items:center; gap:.75rem; padding:.8rem .85rem; background:#fff; border:1px solid var(--line); border-radius:14px; box-shadow:var(--shadow-sm); transition:transform .15s, box-shadow .15s, border-color .15s; height:100%; }
    .report-tile:hover { transform:translateY(-2px); box-shadow:var(--shadow); border-color:var(--bg-2); }
    .report-tile .rt-icon { width:44px; height:44px; min-width:44px; border-radius:12px; background:var(--brown-50); color:var(--brown-dark); display:inline-flex; align-items:center; justify-content:center; font-size:1.05rem; }
    .report-tile .rt-title { font-weight:700; font-size:.92rem; color:var(--ink); display:block; }
    .report-tile .rt-arrow { margin-inline-start:auto; color:#c9bfab; font-size:.85rem; transition:.15s; }
    .report-tile:hover .rt-arrow { color:var(--brown); transform:translateX(-3px); }
    .rt-exports { display:flex; gap:.35rem; margin-top:.35rem; position:relative; z-index:2; }
    .rt-exports a { font-size:.66rem; font-weight:700; padding:.08rem .45rem; border-radius:.45rem; border:1px solid; line-height:1.5; }
    .rt-pdf { color:var(--danger); border-color:var(--danger-bg); } .rt-pdf:hover { background:var(--danger-bg); color:var(--danger); }
    .rt-xls { color:var(--success); border-color:var(--success-bg); } .rt-xls:hover { background:var(--success-bg); color:var(--success); }
</style>
@endpush

@section('content')
    {{-- مكتبة التقارير --}}
    <div class="card mb-3"><div class="card-body">
        <h6 class="mb-3"><i class="fa-solid fa-folder-open ms-1" style="color:#2b4c80"></i> مكتبة التقارير المالية</h6>

        <div class="rt-group-title">القوائم المالية</div>
        <div class="row g-3 mb-3">
            @foreach ([
                ['reports.balance_sheet', 'قائمة المركز المالي (الميزانية)', 'fa-scale-balanced', true],
                ['reports.income_statement', 'قائمة الدخل العامة', 'fa-chart-line', true],
                ['reports.project_income', 'قائمة دخل المشروع', 'fa-diagram-project', false],
                ['reports.work_in_progress', 'العمل تحت التنفيذ (WIP)', 'fa-bars-progress', false],
            ] as [$r, $label, $icon, $exp])
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="report-tile position-relative">
                        <span class="rt-icon"><i class="fa-solid {{ $icon }}"></i></span>
                        <div class="flex-grow-1">
                            <a href="{{ route($r) }}" class="rt-title stretched-link text-reset">{{ $label }}</a>
                            @if ($exp)
                                <div class="rt-exports">
                                    <a href="{{ route($r, ['format' => 'pdf']) }}" class="rt-pdf">PDF</a>
                                    <a href="{{ route($r, ['format' => 'xlsx']) }}" class="rt-xls">Excel</a>
                                </div>
                            @endif
                        </div>
                        <i class="fa-solid fa-chevron-left rt-arrow"></i>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="rt-group-title">الذمم والتدفقات والمقارنات</div>
        <div class="row g-3 mb-3">
            @foreach ([
                ['reports.cash_flow', 'التدفّق النقدي', 'fa-money-bill-transfer'],
                ['reports.ar_aging', 'أعمار الذمم المدينة', 'fa-hand-holding-dollar'],
                ['reports.ap_aging', 'أعمار الذمم الدائنة', 'fa-file-invoice-dollar'],
                ['reports.period_comparison', 'مقارنة الفترات', 'fa-chart-column'],
                ['reports.taxes', 'تقرير الضرائب', 'fa-receipt'],
            ] as [$r, $label, $icon])
                <div class="col-12 col-sm-6 col-lg-4">
                    <a href="{{ route($r) }}" class="report-tile text-reset">
                        <span class="rt-icon"><i class="fa-solid {{ $icon }}"></i></span>
                        <span class="rt-title flex-grow-1">{{ $label }}</span>
                        <i class="fa-solid fa-chevron-left rt-arrow"></i>
                    </a>
                </div>
            @endforeach
        </div>

        <div class="rt-group-title">تقارير تشغيلية</div>
        <div class="row g-3">
            @can('contractors.view')
                <div class="col-12 col-sm-6 col-lg-4"><a href="{{ route('contractors.report') }}" class="report-tile text-reset"><span class="rt-icon"><i class="fa-solid fa-hard-hat"></i></span><span class="rt-title flex-grow-1">تقرير المقاولين</span><i class="fa-solid fa-chevron-left rt-arrow"></i></a></div>
            @endcan
            @can('projects.view')
                <div class="col-12 col-sm-6 col-lg-4"><a href="{{ route('project_costs.report') }}" class="report-tile text-reset"><span class="rt-icon"><i class="fa-solid fa-coins"></i></span><span class="rt-title flex-grow-1">تكاليف المشاريع</span><i class="fa-solid fa-chevron-left rt-arrow"></i></a></div>
            @endcan
            @can('materials.view')
                <div class="col-12 col-sm-6 col-lg-4"><a href="{{ route('materials.report') }}" class="report-tile text-reset"><span class="rt-icon"><i class="fa-solid fa-boxes-stacked"></i></span><span class="rt-title flex-grow-1">تقرير المخزون</span><i class="fa-solid fa-chevron-left rt-arrow"></i></a></div>
            @endcan
            @can('assets.view')
                <div class="col-12 col-sm-6 col-lg-4"><a href="{{ route('assets.report') }}" class="report-tile text-reset"><span class="rt-icon"><i class="fa-solid fa-warehouse"></i></span><span class="rt-title flex-grow-1">الأصول والإهلاك</span><i class="fa-solid fa-chevron-left rt-arrow"></i></a></div>
            @endcan
        </div>

        <div class="rt-group-title">التحليلات</div>
        <div class="row g-3">
            @foreach ([
                ['analytics.project_profitability', 'ربحية المشاريع', 'fa-chart-pie'],
                ['analytics.budget_vs_actual', 'الموازنة مقابل الفعلي', 'fa-scale-unbalanced'],
                ['analytics.supplier_performance', 'أداء المورّدين', 'fa-truck-field'],
                ['analytics.contractor_performance', 'أداء المقاولين', 'fa-helmet-safety'],
                ['analytics.payroll', 'كشف الرواتب', 'fa-money-check-dollar'],
                ['analytics.partner_forecast', 'توقّعات أرباح الشركاء', 'fa-handshake'],
            ] as [$r, $label, $icon])
                <div class="col-12 col-sm-6 col-lg-4">
                    <a href="{{ route($r) }}" class="report-tile text-reset">
                        <span class="rt-icon"><i class="fa-solid {{ $icon }}"></i></span>
                        <span class="rt-title flex-grow-1">{{ $label }}</span>
                        <i class="fa-solid fa-chevron-left rt-arrow"></i>
                    </a>
                </div>
            @endforeach
        </div>
    </div></div>

    {{-- فلتر الفترة --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">من تاريخ</label>
                    <input type="date" name="from" value="{{ $from }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">إلى تاريخ</label>
                    <input type="date" name="to" value="{{ $to }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-filter ms-1"></i> عرض</button>
                    <a href="{{ route('reports.index') }}" class="btn btn-light">الكل</a>
                </div>
            </form>
        </div>
    </div>

    {{-- ملخّص عام --}}
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <div class="text-muted small">إجمالي الإيرادات</div>
                <div class="fs-4 fw-bold text-success">{{ number_format($totalRevenue, 2) }} ج</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <div class="text-muted small">إجمالي المصروفات</div>
                <div class="fs-4 fw-bold text-danger">{{ number_format($totalExpense, 2) }} ج</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <div class="text-muted small">صافي الربح/الخسارة</div>
                <div class="fs-4 fw-bold {{ $net >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($net, 2) }} ج</div>
            </div></div>
        </div>
    </div>

    <div class="row g-3">
        {{-- المصروفات حسب الفئة --}}
        <div class="col-md-5">
            <div class="card h-100"><div class="card-body">
                <h6 class="mb-3">المصروفات حسب الفئة</h6>
                <table class="table table-sm">
                    <tbody>
                        @forelse ($byCategory as $cat => $sum)
                            <tr>
                                <td>{{ \App\Models\Expense::CATEGORIES[$cat] ?? $cat }}</td>
                                <td class="text-start fw-semibold">{{ number_format($sum, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-muted text-center py-3">لا توجد مصروفات في الفترة.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div></div>
        </div>

        {{-- ربحية المشاريع --}}
        <div class="col-md-7">
            <div class="card h-100"><div class="card-body">
                <h6 class="mb-3">ربحية المشاريع</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light"><tr><th>المشروع</th><th>إيرادات</th><th>مصروفات</th><th>الصافي</th></tr></thead>
                        <tbody>
                            @forelse ($projects as $p)
                                @php($pnet = (float) $p->rev - (float) $p->exp)
                                <tr>
                                    <td>{{ $p->name }}</td>
                                    <td class="text-success">{{ number_format((float) $p->rev, 2) }}</td>
                                    <td class="text-danger">{{ number_format((float) $p->exp, 2) }}</td>
                                    <td class="fw-bold {{ $pnet >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($pnet, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted text-center py-3">لا توجد مشاريع.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div></div>
        </div>
    </div>
@endsection
