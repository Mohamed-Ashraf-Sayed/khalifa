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
    .mini-stat { background:#fff; border:1px solid var(--line); border-radius:14px; padding:1rem 1.1rem; box-shadow: var(--shadow-sm); transition: transform .15s, box-shadow .15s, border-color .15s; height:100%; }
    a:hover > .mini-stat { transform: translateY(-2px); box-shadow: var(--shadow); border-color: var(--bg-2); }
    .mini-stat i { font-size: 1.05rem; }
    .mini-stat .n { font-size:1.35rem; font-weight:800; letter-spacing:-.02em; }
</style>
@endpush

@section('content')
    {{-- KPIs --}}
    <div class="row g-3 mb-3">
        @foreach ([
            ['revenue','إجمالي الإيرادات','kpi-green','fa-sack-dollar','revenues.index'],
            ['expense','إجمالي المصروفات','kpi-red','fa-money-bill-trend-up','expenses.index'],
            ['net','صافي الربح','kpi-blue','fa-scale-balanced','reports.index'],
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

    {{-- شريط التنبيهات --}}
    @if (count($alerts))
    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach ($alerts as $a)
            <a href="{{ $a['url'] }}" class="text-decoration-none">
                <span class="badge rounded-pill bg-{{ $a['color'] }} py-2 px-3">
                    <i class="fa-solid {{ $a['icon'] }} ms-1"></i>
                    {{ $a['label'] }}
                    <span class="badge bg-light text-dark ms-1">{{ $a['count'] }}</span>
                </span>
            </a>
        @endforeach
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
            <button type="submit" class="btn btn-sm" style="background:#8b7355;color:#fff">
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
            ['fa-diagram-project','المشاريع',$stats['projects'].' ('.$stats['projects_active'].' نشط)','projects.index','text-primary'],
            ['fa-users','العملاء',$stats['clients'],'clients.index','text-info'],
            ['fa-hard-hat','المقاولون',$stats['contractors'],'contractors.index','text-warning'],
            ['fa-truck','الموردون',$stats['suppliers'],'suppliers.index','text-secondary'],
            ['fa-id-badge','الموظفون',$stats['employees'],'employees.index','text-success'],
            ['fa-file-lines','فواتير غير مدفوعة',$stats['invoices_unpaid'],'invoices.index','text-danger'],
        ] as [$icon,$label,$val,$route,$color])
        <div class="col-md-2 col-6">
            <a href="{{ route($route) }}" class="text-decoration-none text-reset">
                <div class="mini-stat h-100">
                    <i class="fa-solid {{ $icon }} {{ $color }}"></i>
                    <div class="n">{{ $val }}</div>
                    <div class="small text-muted">{{ $label }}</div>
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
    Chart.defaults.color = '#8d877b';
    Chart.defaults.borderColor = '#e9e6e0';
    const egp = v => new Intl.NumberFormat('ar-EG').format(v);
    const PALETTE = ['#8b7355','#a3895f','#c0ad8e','#6f5b43','#b8893f','#5b7e96','#4f8a6b'];

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
                backgroundColor: ['#c0ad8e','#5b7e96','#4f8a6b','#b8893f','#b65f5b'], borderWidth: 0 }] },
        options: { plugins: { legend: { position: 'bottom' } } }
    });
</script>
@endpush
