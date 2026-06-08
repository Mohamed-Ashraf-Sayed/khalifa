@extends('layouts.app')

@section('title', 'مقارنة الفترات')

@section('content')
    <style>
        @media print {
            .no-print { display: none !important; }
            .sidebar, .topbar, nav.navbar, aside { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            .pc-head { background: #f3efe9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        .pc-bar { height: 8px; border-radius: 4px; background: #2b4c80; }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="m-0">مقارنة الفترات</h5>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
            <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع للتقارير</a>
        </div>
    </div>

    {{-- فلتر الفترة --}}
    <div class="card mb-3 no-print">
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
                    <a href="{{ route('reports.period_comparison') }}" class="btn btn-light">الافتراضي</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body text-center">
            <h4 class="m-0">مقارنة الفترات</h4>
            <div class="text-muted">
                الفترة الحالية: {{ $from }} — {{ $to }}
                &nbsp;|&nbsp;
                الفترة السابقة: {{ $prevFrom }} — {{ $prevTo }}
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light pc-head">
                        <tr>
                            <th>البند</th>
                            <th class="text-start">الفترة الحالية</th>
                            <th class="text-start">الفترة السابقة</th>
                            <th class="text-start">التغيّر</th>
                            <th class="text-start">نسبة التغيّر</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($metrics as $m)
                            <tr>
                                <td class="fw-semibold">{{ $m['label'] }}</td>
                                <td class="text-start">{{ number_format((float) $m['current'], 2) }}</td>
                                <td class="text-start">{{ number_format((float) $m['previous'], 2) }}</td>
                                <td class="text-start fw-semibold {{ (float) $m['change'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ (float) $m['change'] >= 0 ? '+' : '' }}{{ number_format((float) $m['change'], 2) }}
                                </td>
                                <td class="text-start">
                                    @if (is_null($m['pct']))
                                        <span class="text-muted">—</span>
                                    @else
                                        <span class="{{ $m['pct'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $m['pct'] >= 0 ? '+' : '' }}{{ number_format($m['pct'], 1) }}%
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
