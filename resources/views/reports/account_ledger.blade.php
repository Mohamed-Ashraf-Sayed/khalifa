@extends('layouts.app')

@section('title', 'دفتر الأستاذ')

@section('content')
    <style>
        @media print {
            .no-print { display: none !important; }
            .sidebar, .topbar, nav.navbar, aside { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="m-0">دفتر الأستاذ</h5>
        <div class="d-flex gap-2">
            @if ($account)
                <a href="{{ request()->fullUrlWithQuery(['export' => 'xlsx']) }}" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-file-excel ms-1"></i> Excel</a>
            @endif
            <button onclick="window.print()" class="btn btn-sm" style="background:#8b7355;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
        </div>
    </div>

    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small">الحساب</label>
                    <select name="account_id" class="form-select" onchange="this.form.submit()">
                        @foreach ($accounts as $acc)
                            <option value="{{ $acc->id }}" @selected($account && $account->id === $acc->id)>{{ $acc->code }} - {{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-filter ms-1"></i> عرض</button>
                </div>
            </form>
        </div>
    </div>

    @if (! $account)
        <div class="card"><div class="card-body text-center text-muted py-4">لا توجد حسابات قابلة للترحيل.</div></div>
    @else
        <div class="card mb-3">
            <div class="card-body text-center">
                <h4 class="m-0">{{ $account->code }} - {{ $account->name }}</h4>
                <div class="text-muted">دفتر أستاذ الحساب — الحركات المرحّلة</div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>التاريخ</th>
                                <th>رقم القيد</th>
                                <th>البيان</th>
                                <th class="text-start">مدين</th>
                                <th class="text-start">دائن</th>
                                <th class="text-start">الرصيد الجاري</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="table-light">
                                <td colspan="5" class="fw-semibold">الرصيد الافتتاحي</td>
                                <td class="text-start fw-bold">{{ number_format((float) $opening, 2) }}</td>
                            </tr>
                            @forelse ($lines as $l)
                                <tr>
                                    <td>{{ $l['date'] }}</td>
                                    <td class="text-muted">{{ $l['entry_number'] }}</td>
                                    <td>{{ $l['description'] }}</td>
                                    <td class="text-start">{{ bccomp($l['debit'], '0', 2) > 0 ? number_format((float) $l['debit'], 2) : '—' }}</td>
                                    <td class="text-start">{{ bccomp($l['credit'], '0', 2) > 0 ? number_format((float) $l['credit'], 2) : '—' }}</td>
                                    <td class="text-start fw-semibold {{ bccomp($l['running'], '0', 2) >= 0 ? '' : 'text-danger' }}">{{ number_format((float) $l['running'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">لا توجد حركات مرحّلة على هذا الحساب.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="3">الإجمالي</td>
                                <td class="text-start">{{ number_format((float) $totalDebit, 2) }}</td>
                                <td class="text-start">{{ number_format((float) $totalCredit, 2) }}</td>
                                <td class="text-start {{ bccomp($closing, '0', 2) >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $closing, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
