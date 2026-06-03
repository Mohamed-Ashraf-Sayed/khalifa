@extends('layouts.app')

@section('title', 'كشف حساب ' . $partner->name)

@section('content')
    <style>
        @media print {
            .no-print { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="m-0">كشف حساب — {{ $partner->name }}</h5>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm" style="background:#8b7355;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
            <a href="{{ route('partners.show', $partner) }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="text-center mb-3">
                <h4 class="m-0">كشف حساب شريك</h4>
                <div class="text-muted">{{ $partner->name }}</div>
            </div>
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">إجمالي رأس المال</div><div class="fw-bold fs-5">{{ number_format((float) $partner->totalCapital(), 2) }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الأرباح المصروفة</div><div class="fw-bold fs-5 text-success">{{ number_format((float) $partner->totalProfitPaid(), 2) }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الرصيد الحالي</div><div class="fw-bold fs-5">{{ number_format((float) $partner->currentBalance(), 2) }}</div></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">الحركات</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>التاريخ</th>
                            <th>النوع</th>
                            <th>البيان</th>
                            <th class="text-end">مدين (−)</th>
                            <th class="text-end">دائن (+)</th>
                            <th class="text-end">الرصيد الجاري</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-light">
                            <td colspan="5" class="fw-semibold">رصيد افتتاحي</td>
                            <td class="text-end fw-semibold">0.00</td>
                        </tr>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ optional($row['txn']->transaction_date)->format('Y-m-d') ?: '—' }}</td>
                                <td><span class="badge text-bg-light">{{ \App\Models\PartnerTransaction::TYPES[$row['txn']->type] ?? $row['txn']->type }}</span></td>
                                <td>{{ $row['txn']->description ?: '—' }}</td>
                                <td class="text-end text-danger">{{ $row['txn']->type === 'deposit' ? '' : number_format($row['txn']->amount, 2) }}</td>
                                <td class="text-end text-success">{{ $row['txn']->type === 'deposit' ? number_format($row['txn']->amount, 2) : '' }}</td>
                                <td class="text-end fw-semibold">{{ number_format((float) $row['running'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">لا توجد حركات.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
