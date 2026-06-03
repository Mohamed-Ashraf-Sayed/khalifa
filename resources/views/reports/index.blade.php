@extends('layouts.app')

@section('title', 'التقارير المالية')

@section('content')
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
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-filter ms-1"></i> عرض</button>
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
