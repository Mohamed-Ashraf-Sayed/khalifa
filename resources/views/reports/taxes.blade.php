@extends('layouts.app')

@section('title', 'تقرير الضرائب')

@section('content')
    <style>
        @media print {
            .no-print { display: none !important; }
            .sidebar, .topbar, nav.navbar, aside { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="m-0">تقرير الضرائب</h5>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm" style="background:#8b7355;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
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
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-filter ms-1"></i> عرض</button>
                    <a href="{{ route('reports.taxes') }}" class="btn btn-light">الكل</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body text-center">
            <h4 class="m-0">تقرير الضرائب — القيمة المضافة</h4>
            <div class="text-muted">
                @if ($from || $to)
                    عن الفترة {{ $from ?: '...' }} — {{ $to ?: '...' }}
                @else
                    كل الفترات
                @endif
            </div>
        </div>
    </div>

    {{-- بطاقات ضريبة القيمة المضافة --}}
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small">ضريبة المخرجات (من الفواتير)</div>
                <div class="fs-4 fw-bold text-success">{{ number_format((float) $outputVat, 2) }} ج</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small">ضريبة المدخلات (من مدفوعات المورّدين)</div>
                <div class="fs-4 fw-bold text-danger">{{ number_format((float) $inputVat, 2) }} ج</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card h-100" style="border-color:#8b7355"><div class="card-body">
                <div class="text-muted small">صافي الضريبة المستحقّة</div>
                <div class="fs-4 fw-bold {{ (float) $netVat < 0 ? 'text-danger' : '' }}" style="color:#8b7355">{{ number_format((float) $netVat, 2) }} ج</div>
            </div></div>
        </div>
    </div>

    {{-- سجلّات الضرائب حسب النوع --}}
    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">سجلّات الضرائب حسب النوع</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>نوع الضريبة</th><th class="text-start">الإجمالي</th></tr></thead>
                    <tbody>
                        @php($grand = '0')
                        @forelse ($taxesByType as $type => $total)
                            @php($grand = bcadd($grand, (string) $total, 2))
                            <tr>
                                <td>{{ \App\Models\Tax::TYPES[$type] ?? $type }}</td>
                                <td class="text-start fw-semibold">{{ number_format((float) $total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted py-3">لا توجد سجلّات ضرائب في الفترة.</td></tr>
                        @endforelse
                    </tbody>
                    @if (count($taxesByType))
                        <tfoot>
                            <tr style="background:#8b7355;color:#fff;font-weight:700">
                                <td>الإجمالي</td>
                                <td class="text-start">{{ number_format((float) $grand, 2) }} ج</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
