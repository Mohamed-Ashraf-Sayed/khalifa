@extends('layouts.app')

@section('title', 'قائمة الدخل (محاسبي)')

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

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="m-0">قائمة الدخل (محاسبي)</h5>
        <div class="d-flex gap-2">
            <a href="{{ request()->fullUrlWithQuery(['export' => 'xlsx']) }}" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-file-excel ms-1"></i> Excel</a>
            <button onclick="window.print()" class="btn btn-sm" style="background:#8b7355;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
        </div>
    </div>

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
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-filter ms-1"></i> عرض</button>
                    <a href="{{ route('accounting.income_statement') }}" class="btn btn-light">الكل</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body text-center">
            <h4 class="m-0">قائمة الدخل (محاسبي)</h4>
            <div class="text-muted">
                @if ($from || $to)
                    عن الفترة {{ $from ?: '...' }} — {{ $to ?: '...' }}
                @else
                    كل الفترات
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-sm align-middle mb-0">
                <tbody>
                    <tr class="is-head"><td colspan="2">الإيرادات</td></tr>
                    @forelse ($revenueGroups as $g)
                        <tr><td>{{ $g['name'] }}</td><td class="text-start text-success">{{ number_format((float) $g['total'], 2) }}</td></tr>
                    @empty
                        <tr><td class="text-muted">لا توجد إيرادات</td><td class="text-start">0.00</td></tr>
                    @endforelse
                    <tr class="is-subtotal"><td>إجمالي الإيرادات</td><td class="text-start text-success">{{ number_format((float) $totalRevenue, 2) }}</td></tr>

                    <tr class="is-head"><td colspan="2">المصروفات</td></tr>
                    @forelse ($expenseGroups as $g)
                        <tr><td>{{ $g['name'] }}</td><td class="text-start text-danger">{{ number_format((float) $g['total'], 2) }}</td></tr>
                    @empty
                        <tr><td class="text-muted">لا توجد مصروفات</td><td class="text-start">0.00</td></tr>
                    @endforelse
                    <tr class="is-subtotal"><td>إجمالي المصروفات</td><td class="text-start text-danger">{{ number_format((float) $totalExpense, 2) }}</td></tr>
                </tbody>
                <tfoot>
                    <tr class="is-grand">
                        <td>صافي الربح / الخسارة</td>
                        <td class="text-start">{{ number_format((float) $netProfit, 2) }} ج</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
