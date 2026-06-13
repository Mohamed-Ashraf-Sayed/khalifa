@extends('layouts.app')

@section('title', 'لوحة التحكم')

@push('styles')
<style>
    .kpi { background:#fff; border:1px solid var(--line); border-top:3px solid var(--brown-light); border-radius:14px;
        color:var(--ink); position:relative; overflow:hidden; padding:1.1rem 1.25rem !important;
        box-shadow:var(--shadow-sm); transition:transform .18s ease, box-shadow .18s ease, border-color .18s; }
    a:hover > .kpi { transform: translateY(-2px); box-shadow:var(--shadow); }
    .kpi .ic { position: absolute; inset-inline-end: 1rem; top: .95rem; font-size: 1.5rem; opacity: .9; z-index: 1; }
    .kpi .v { font-size: 1.55rem; font-weight: 800; line-height: 1.15; position: relative; z-index: 1; letter-spacing: -.02em; color:var(--ink); }
    .kpi .l { color:var(--muted); font-size: .83rem; font-weight: 600; position: relative; z-index: 1; }
    .kpi .small.opacity-75 { color:var(--muted) !important; opacity:1 !important; }
    .kpi-green { border-top-color: var(--success); } .kpi-green .ic { color: var(--success); }
    .kpi-red   { border-top-color: var(--danger);  } .kpi-red   .ic { color: var(--danger);  }
    .kpi-blue  { border-top-color: var(--info);    } .kpi-blue  .ic { color: var(--info);    }
    .kpi-brown { border-top-color: var(--brown);   } .kpi-brown .ic { color: var(--brown);   }
    .mini-stat { display:flex; align-items:center; gap:.85rem; background:#fff; border:1px solid var(--line); border-radius:14px;
        padding:.85rem .95rem; box-shadow: var(--shadow-sm); position:relative; overflow:hidden; height:100%;
        transition: transform .15s, box-shadow .15s, border-color .15s; }
    a:hover > .mini-stat { transform: translateY(-2px); box-shadow: var(--shadow); border-color: var(--bg-2); }
    .mini-stat::before { content:''; position:absolute; inset-block:0; inset-inline-start:0; width:4px; background:var(--c, var(--brown)); }
    .mini-stat .mini-ic { flex:0 0 auto; width:46px; height:46px; border-radius:12px; display:grid; place-items:center;
        background:var(--c-bg, var(--brown-50)); color:var(--c, var(--brown)); font-size:1.15rem; }
    .mini-stat .mini-tx { min-width:0; }
    .mini-stat .n { font-size:1.35rem; font-weight:800; letter-spacing:-.02em; line-height:1.15; color:var(--ink); }
    .mini-stat .l { font-size:.78rem; color:var(--muted); font-weight:600; margin-top:1px; }
    .ms-primary   { --c: var(--brown);   --c-bg: var(--brown-50); }
    .ms-info      { --c: var(--info);    --c-bg: var(--info-bg); }
    .ms-warning   { --c: var(--warning); --c-bg: var(--warning-bg); }
    .ms-secondary { --c: var(--muted);   --c-bg: var(--bg-2); }
    .ms-success   { --c: var(--success); --c-bg: var(--success-bg); }
    .ms-danger    { --c: var(--danger);  --c-bg: var(--danger-bg); }
    /* ===== لوحة التنبيهات — "أعلام حالة" هندسية منظّمة ===== */
    .alerts-panel { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); padding: .8rem 1rem 1rem; box-shadow: var(--shadow-sm); }
    .alerts-head { display: flex; align-items: center; gap: .5rem; margin-bottom: .75rem; padding-bottom: .6rem; border-bottom: 1px dashed var(--line); }
    .alerts-head .ah-title { font-weight: 800; font-size: .9rem; color: var(--ink); letter-spacing: -.01em; }
    .alerts-head .ah-title i { color: var(--warning); margin-inline-end: .3rem; }
    .alerts-head .ah-count { margin-inline-start: auto; background: var(--brown); color: #fff; font-size: .72rem; font-weight: 800; min-width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; border-radius: 7px; padding: 0 6px; }
    .alerts-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(235px, 1fr)); gap: .5rem; }

    .alert-chip { display: flex; align-items: center; gap: .6rem; background: #fff; border: 1px solid var(--line);
        border-inline-start: 3px solid var(--c); border-radius: 9px; padding: .55rem .8rem; color: var(--ink); text-decoration: none;
        box-shadow: 0 1px 2px rgba(20,38,74,.04); transition: transform .14s ease, box-shadow .14s ease, background .14s ease; }
    .alert-chip:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(20,38,74,.10); background: var(--c-bg); }
    .alert-chip i { font-size: 1rem; color: var(--c); width: 1.15em; text-align: center; flex: 0 0 auto; }
    .alert-chip > span:not(.c) { font-size: .82rem; font-weight: 600; color: var(--ink-2); flex: 1 1 auto; line-height: 1.25; }
    .alert-chip .c { flex: 0 0 auto; font-size: 1.05rem; font-weight: 800; color: var(--c); font-variant-numeric: tabular-nums; line-height: 1; padding-inline-start: .6rem; border-inline-start: 1px solid var(--line); }
    .alert-chip.warning   { --c: var(--warning); --c-bg: var(--warning-bg); }
    .alert-chip.danger    { --c: var(--danger);  --c-bg: var(--danger-bg); }
    .alert-chip.info      { --c: var(--info);    --c-bg: var(--info-bg); }
    .alert-chip.success   { --c: var(--success); --c-bg: var(--success-bg); }
    .alert-chip.secondary { --c: var(--muted);   --c-bg: var(--bg-2); }
    .alert-chip.primary   { --c: var(--brown);   --c-bg: var(--brown-50); }
</style>
@endpush

@section('content')
    {{-- KPIs --}}
    <div class="row g-3 mb-3">
        @foreach ([
            ['revenue','إجمالي الإيرادات','kpi-green','fa-sack-dollar','revenues.index'],
            ['expense','إجمالي المصروفات','kpi-red','fa-money-bill-trend-up','expenses.index'],
            ['net','صافي الربح','kpi-blue','fa-arrow-trend-up','reports.index'],
            ['bank_balance','السيولة بالبنوك','kpi-brown','fa-building-columns','bank_accounts.index'],
        ] as [$key,$label,$cls,$icon,$route])
        <div class="col-md-3 col-6">
            <a href="{{ route($route) }}" class="text-decoration-none">
                <div class="kpi {{ $cls }} p-3 h-100">
                    <i class="fa-solid {{ $icon }} ic"></i>
                    <div class="l">{{ $label }}</div>
                    <div class="v">{{ number_format($stats[$key], 0) }}</div>
                    <div class="small opacity-75">ج.م</div>
                </div>
            </a>
        </div>
        @endforeach
    </div>

    {{-- لوحة التنبيهات --}}
    @if (count($alerts))
    <div class="alerts-panel mb-3">
        <div class="alerts-head">
            <span class="ah-title"><i class="fa-solid fa-triangle-exclamation"></i> ما يحتاج انتباهك</span>
            <span class="ah-count">{{ count($alerts) }}</span>
        </div>
        <div class="alerts-grid">
            @foreach ($alerts as $a)
                <a href="{{ $a['url'] }}" class="alert-chip {{ $a['color'] }}">
                    <i class="fa-solid {{ $a['icon'] }}"></i>
                    <span>{{ $a['label'] }}</span>
                    <span class="c">{{ $a['count'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- فلتر النطاق الزمني للمؤشرات المالية --}}
    <form method="GET" class="row g-2 align-items-end mb-3">
        <div class="col-md-3 col-6">
            <label class="form-label small mb-1">من تاريخ</label>
            <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-3 col-6">
            <label class="form-label small mb-1">إلى تاريخ</label>
            <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-3 col-12">
            <button type="submit" class="btn btn-sm" style="background:#2b4c80;color:#fff">
                <i class="fa-solid fa-filter ms-1"></i> تطبيق
            </button>
            @if ($from || $to)
                <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-xmark ms-1"></i> مسح
                </a>
            @endif
        </div>
    </form>

    {{-- mini stats --}}
    <div class="row g-3 mb-3">
        @foreach ([
            ['fa-diagram-project','المشاريع',$stats['projects'].' ('.$stats['projects_active'].' نشط)','projects.index','primary'],
            ['fa-users','العملاء',$stats['clients'],'clients.index','info'],
            ['fa-hard-hat','المقاولون',$stats['contractors'],'contractors.index','warning'],
            ['fa-truck','الموردون',$stats['suppliers'],'suppliers.index','secondary'],
            ['fa-id-badge','الموظفون',$stats['employees'],'employees.index','success'],
            ['fa-file-lines','فواتير غير مدفوعة',$stats['invoices_unpaid'],'invoices.index','danger'],
        ] as [$icon,$label,$val,$route,$c])
        <div class="col-md-4 col-xl-2 col-6">
            <a href="{{ route($route) }}" class="text-decoration-none text-reset">
                <div class="mini-stat ms-{{ $c }} h-100">
                    <span class="mini-ic"><i class="fa-solid {{ $icon }}"></i></span>
                    <span class="mini-tx">
                        <span class="n d-block">{{ $val }}</span>
                        <span class="l d-block">{{ $label }}</span>
                    </span>
                </div>
            </a>
        </div>
        @endforeach
    </div>

    {{-- charts --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card h-100"><div class="card-body">
                <h6 class="mb-3">الإيرادات مقابل المصروفات — آخر 6 شهور</h6>
                <canvas id="trendChart" height="110"></canvas>
            </div></div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100"><div class="card-body">
                <h6 class="mb-3">المصروفات حسب الفئة</h6>
                <canvas id="catChart" height="200"></canvas>
            </div></div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card h-100"><div class="card-body">
                <h6 class="mb-3">المشاريع حسب الحالة</h6>
                <canvas id="statusChart" height="200"></canvas>
            </div></div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100"><div class="card-body">
                <h6 class="mb-3">أرصدة البنوك</h6>
                @forelse ($banks as $b)
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span><i class="fa-solid fa-building-columns text-muted ms-1"></i> {{ $b->name }}</span>
                        <span class="fw-bold">{{ number_format($b->current_balance, 0) }}</span>
                    </div>
                @empty
                    <div class="text-muted">لا توجد حسابات.</div>
                @endforelse
            </div></div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100"><div class="card-body">
                <h6 class="mb-3">أعلى المقاولين رصيداً مستحقاً</h6>
                @forelse ($topContractors as $c)
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>{{ $c['name'] }}</span>
                        <span class="fw-bold text-danger">{{ number_format($c['balance'], 0) }}</span>
                    </div>
                @empty
                    <div class="text-muted">لا يوجد.</div>
                @endforelse
            </div></div>
        </div>
    </div>

    {{-- قوائم الاستحقاق --}}
    <div class="row g-3 mt-1">
        {{-- فواتير متأخرة --}}
        <div class="col-lg-4">
            <div class="card h-100"><div class="card-body">
                <h6 class="mb-3"><i class="fa-solid fa-file-invoice text-danger ms-1"></i> فواتير متأخرة</h6>
                @forelse ($overdueInvoices as $inv)
                    <a href="{{ route('invoices.show', $inv) }}" class="text-decoration-none text-reset">
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span>
                                {{ $inv->client?->name ?? '—' }}
                                <span class="d-block small text-muted">استحقاق {{ $inv->due_date?->format('Y-m-d') }}</span>
                            </span>
                            <span class="text-start">
                                <span class="fw-bold">{{ number_format($inv->total_amount, 0) }}</span>
                                <span class="d-block small text-danger">متبقّي {{ number_format((float) $inv->remaining(), 0) }}</span>
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="text-muted">لا توجد فواتير متأخرة.</div>
                @endforelse
            </div></div>
        </div>

        {{-- مخزون منخفض --}}
        <div class="col-lg-4">
            <div class="card h-100"><div class="card-body">
                <h6 class="mb-3"><i class="fa-solid fa-boxes-stacked text-warning ms-1"></i> مخزون منخفض</h6>
                @forelse ($lowStockMaterials as $m)
                    <a href="{{ route('materials.show', $m) }}" class="text-decoration-none text-reset">
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span>
                                {{ $m->name }}
                                <span class="d-block small text-muted">{{ $m->project?->name ?? 'مخزن عام' }}</span>
                            </span>
                            <span class="text-start">
                                <span class="fw-bold text-danger">{{ number_format($m->current_stock, 0) }}</span>
                                <span class="d-block small text-muted">حد {{ number_format($m->min_stock, 0) }} {{ $m->unit }}</span>
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="text-muted">لا يوجد مخزون منخفض.</div>
                @endforelse
            </div></div>
        </div>

        {{-- أرباح شركاء مستحقة --}}
        <div class="col-lg-4">
            <div class="card h-100"><div class="card-body">
                <h6 class="mb-3"><i class="fa-solid fa-coins text-info ms-1"></i> أرباح شركاء مستحقة</h6>
                @forelse ($duePartnerProfits as $s)
                    <a href="{{ route('partner_deposits.show', $s->deposit) }}" class="text-decoration-none text-reset">
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span>
                                {{ $s->deposit?->partner?->name ?? '—' }}
                                <span class="d-block small text-muted">استحقاق {{ $s->due_date?->format('Y-m-d') }}</span>
                            </span>
                            <span class="fw-bold">{{ number_format($s->amount, 0) }}</span>
                        </div>
                    </a>
                @empty
                    <div class="text-muted">لا توجد أقساط مستحقة.</div>
                @endforelse
            </div></div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    Chart.defaults.font.family = 'Cairo, sans-serif';
    Chart.defaults.color = '#8794a6';
    Chart.defaults.borderColor = '#e3e8ef';
    const egp = v => new Intl.NumberFormat('ar-EG').format(v);
    const PALETTE = ['#2b4c80','#5b7bab','#8aa0c4','#1f3a63','#b8893f','#5b7e96','#4f8a6b'];

    new Chart(document.getElementById('trendChart'), {
        type: 'bar',
        data: {
            labels: @json($chartMonths),
            datasets: [
                { label: 'إيرادات', data: @json($chartRevenue), backgroundColor: '#4f8a6b', borderRadius: 6 },
                { label: 'مصروفات', data: @json($chartExpense), backgroundColor: '#b65f5b', borderRadius: 6 },
            ]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } },
            scales: { y: { ticks: { callback: v => egp(v) } } } }
    });

    new Chart(document.getElementById('catChart'), {
        type: 'doughnut',
        data: { labels: @json($catLabels),
            datasets: [{ data: @json($catValues),
                backgroundColor: PALETTE, borderWidth: 0 }] },
        options: { plugins: { legend: { position: 'bottom' } } }
    });

    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: { labels: @json($statusLabels),
            datasets: [{ data: @json($statusValues),
                backgroundColor: ['#8aa0c4','#5b7e96','#4f8a6b','#b8893f','#b65f5b'], borderWidth: 0 }] },
        options: { plugins: { legend: { position: 'bottom' } } }
    });
</script>
@endpush
