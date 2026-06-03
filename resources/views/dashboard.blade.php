@extends('layouts.app')

@section('title', 'لوحة التحكم')

@section('content')
    <p class="text-muted">أهلاً، {{ auth()->user()->name }} 👋</p>

    {{-- مؤشرات مالية --}}
    <div class="row g-3 mb-2">
        <div class="col-md-3 col-6">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small"><i class="fa-solid fa-sack-dollar text-success ms-1"></i> الإيرادات</div>
                <div class="fs-5 fw-bold text-success">{{ number_format($stats['revenue'], 2) }}</div>
            </div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small"><i class="fa-solid fa-money-bill-wave text-danger ms-1"></i> المصروفات</div>
                <div class="fs-5 fw-bold text-danger">{{ number_format($stats['expense'], 2) }}</div>
            </div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small"><i class="fa-solid fa-scale-balanced ms-1"></i> صافي الربح</div>
                <div class="fs-5 fw-bold {{ $stats['net'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($stats['net'], 2) }}</div>
            </div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small"><i class="fa-solid fa-building-columns text-primary ms-1"></i> أرصدة البنوك</div>
                <div class="fs-5 fw-bold">{{ number_format($stats['bank_balance'], 2) }}</div>
            </div></div>
        </div>
    </div>

    {{-- عدّادات --}}
    <div class="row g-3 mb-3">
        @php($counters = [
            ['المشاريع', $stats['projects'], 'fa-diagram-project', 'projects.view', 'projects.index'],
            ['العملاء', $stats['clients'], 'fa-users', 'clients.view', 'clients.index'],
            ['المقاولون', $stats['contractors'], 'fa-hard-hat', 'contractors.view', 'contractors.index'],
            ['الموردون', $stats['suppliers'], 'fa-truck', 'suppliers.view', 'suppliers.index'],
            ['الموظفون', $stats['employees'], 'fa-id-badge', 'employees.view', 'employees.index'],
        ])
        @foreach ($counters as [$label, $count, $icon, $perm, $route])
            @can($perm)
            <div class="col-md col-6">
                <a href="{{ route($route) }}" class="text-decoration-none text-reset">
                    <div class="card h-100 text-center"><div class="card-body">
                        <i class="fa-solid {{ $icon }} fa-lg text-secondary"></i>
                        <div class="fs-4 fw-bold mt-1">{{ $count }}</div>
                        <div class="small text-muted">{{ $label }}</div>
                    </div></div>
                </a>
            </div>
            @endcan
        @endforeach
    </div>

    {{-- أحدث المشاريع --}}
    @can('projects.view')
    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">أحدث المشاريع</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>المشروع</th><th>العميل</th><th>قيمة العقد</th><th>الحالة</th></tr></thead>
                    <tbody>
                        @forelse ($recentProjects as $p)
                            <tr>
                                <td class="fw-semibold">{{ $p->name }}</td>
                                <td>{{ $p->client?->name ?? '—' }}</td>
                                <td>{{ number_format($p->contract_value, 2) }}</td>
                                <td><span class="badge text-bg-light">{{ \App\Models\Project::STATUSES[$p->status] ?? $p->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">لا توجد مشاريع بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endcan
@endsection
