@extends('layouts.app')

@section('title', 'لوحة التحكم')

@push('styles')
<style>
    .kpi { border-radius: 1rem; color: #fff; position: relative; overflow: hidden; }
    .kpi .ic { position: absolute; inset-inline-end: 1rem; top: 1rem; font-size: 2.2rem; opacity: .25; }
    .kpi .v { font-size: 1.7rem; font-weight: 700; line-height: 1.1; }
    .kpi .l { opacity: .9; font-size: .9rem; }
    .kpi-green { background: linear-gradient(135deg,#1f9d6b,#0f7a4f); }
    .kpi-red   { background: linear-gradient(135deg,#d9534f,#b52b27); }
    .kpi-blue  { background: linear-gradient(135deg,#3a7bd5,#2456a6); }
    .kpi-brown { background: linear-gradient(135deg,#8b7355,#6f5b43); }
    .mini-stat { background:#fff; border-radius:.8rem; padding:1rem; box-shadow:0 2px 10px rgba(139,115,85,.08); }
    .mini-stat .n { font-size:1.4rem; font-weight:700; }
</style>
@endpush

@section('content')
    {{-- KPIs --}}
    <div class="row g-3 mb-3">
        @foreach ([
            ['revenue','إجمالي الإيرادات','kpi-green','fa-sack-dollar'],
            ['expense','إجمالي المصروفات','kpi-red','fa-money-bill-trend-up'],
            ['net','صافي الربح','kpi-blue','fa-scale-balanced'],
            ['bank_balance','السيولة بالبنوك','kpi-brown','fa-building-columns'],
        ] as [$key,$label,$cls,$icon])
        <div class="col-md-3 col-6">
            <div class="kpi {{ $cls }} p-3 h-100">
                <i class="fa-solid {{ $icon }} ic"></i>
                <div class="l">{{ $label }}</div>
                <div class="v">{{ number_format($stats[$key], 0) }}</div>
                <div class="small opacity-75">ج.م</div>
            </div>
        </div>
        @endforeach
    </div>

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
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    Chart.defaults.font.family = 'Cairo, sans-serif';
    const egp = v => new Intl.NumberFormat('ar-EG').format(v);

    new Chart(document.getElementById('trendChart'), {
        type: 'bar',
        data: {
            labels: @json($chartMonths),
            datasets: [
                { label: 'إيرادات', data: @json($chartRevenue), backgroundColor: '#1f9d6b', borderRadius: 6 },
                { label: 'مصروفات', data: @json($chartExpense), backgroundColor: '#d9534f', borderRadius: 6 },
            ]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } },
            scales: { y: { ticks: { callback: v => egp(v) } } } }
    });

    new Chart(document.getElementById('catChart'), {
        type: 'doughnut',
        data: { labels: @json($catLabels),
            datasets: [{ data: @json($catValues),
                backgroundColor: ['#8b7355','#1f9d6b','#3a7bd5','#d9534f','#f0ad4e','#6f5b43','#5bc0de'] }] },
        options: { plugins: { legend: { position: 'bottom' } } }
    });

    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: { labels: @json($statusLabels),
            datasets: [{ data: @json($statusValues),
                backgroundColor: ['#6c757d','#3a7bd5','#1f9d6b','#f0ad4e','#d9534f'] }] },
        options: { plugins: { legend: { position: 'bottom' } } }
    });
</script>
@endpush
