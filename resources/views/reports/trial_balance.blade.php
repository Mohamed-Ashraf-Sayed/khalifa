@extends('layouts.app')

@section('title', 'ميزان المراجعة')

@section('content')
    <style>
        @media print {
            .no-print { display: none !important; }
            .sidebar, .topbar, nav.navbar, aside { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="m-0">ميزان المراجعة</h5>
        <div class="d-flex gap-2">
            <a href="{{ request()->fullUrlWithQuery(['export' => 'xlsx']) }}" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-file-excel ms-1"></i> Excel</a>
            <button onclick="window.print()" class="btn btn-sm" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
        </div>
    </div>

    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">حتى تاريخ</label>
                    <input type="date" name="to" value="{{ $to }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-filter ms-1"></i> عرض</button>
                    <a href="{{ route('accounting.trial_balance') }}" class="btn btn-light">الكل</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body text-center">
            <h4 class="m-0">ميزان المراجعة</h4>
            <div class="text-muted">@if ($to) كما في {{ $to }} @else بكل الحركات المرحّلة @endif</div>
            <div class="mt-2">
                @if ($movementBalanced && $closingBalanced)
                    <span class="badge text-bg-success fs-6">متوازن ✓</span>
                @else
                    <span class="badge text-bg-danger fs-6">غير متوازن</span>
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>الكود</th>
                            <th>اسم الحساب</th>
                            <th class="text-start">حركة مدينة</th>
                            <th class="text-start">حركة دائنة</th>
                            <th class="text-start">رصيد ختامي مدين</th>
                            <th class="text-start">رصيد ختامي دائن</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $r)
                            <tr>
                                <td class="text-muted">{{ $r['code'] }}</td>
                                <td class="fw-semibold">{{ $r['name'] }}</td>
                                <td class="text-start">{{ number_format((float) $r['debit_movement'], 2) }}</td>
                                <td class="text-start">{{ number_format((float) $r['credit_movement'], 2) }}</td>
                                <td class="text-start">{{ bccomp($r['closing_debit'], '0', 2) > 0 ? number_format((float) $r['closing_debit'], 2) : '—' }}</td>
                                <td class="text-start">{{ bccomp($r['closing_credit'], '0', 2) > 0 ? number_format((float) $r['closing_credit'], 2) : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">لا توجد حسابات بحركة.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="2">الإجمالي</td>
                            <td class="text-start {{ $movementBalanced ? 'text-success' : 'text-danger' }}">{{ number_format((float) $sumDebitMovement, 2) }}</td>
                            <td class="text-start {{ $movementBalanced ? 'text-success' : 'text-danger' }}">{{ number_format((float) $sumCreditMovement, 2) }}</td>
                            <td class="text-start {{ $closingBalanced ? 'text-success' : 'text-danger' }}">{{ number_format((float) $sumClosingDebit, 2) }}</td>
                            <td class="text-start {{ $closingBalanced ? 'text-success' : 'text-danger' }}">{{ number_format((float) $sumClosingCredit, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
