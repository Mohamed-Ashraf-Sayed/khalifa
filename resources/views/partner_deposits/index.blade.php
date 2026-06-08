@extends('layouts.app')

@section('title', 'إيداعات الشركاء')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small">رأس المال النشط</div>
                <div class="fw-bold fs-5">{{ number_format((float) $stats['active_capital'], 2) }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small">إجمالي الأرباح المجدولة</div>
                <div class="fw-bold fs-5">{{ number_format((float) $stats['scheduled_profit'], 2) }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small">الأرباح المصروفة</div>
                <div class="fw-bold fs-5 text-success">{{ number_format((float) $stats['paid_profit'], 2) }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small">عدد الإيداعات</div>
                <div class="fw-bold fs-5">{{ $stats['count'] }}</div>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2 flex-wrap" method="GET">
                    <select name="partner_id" class="form-select" style="min-width:180px" onchange="this.form.submit()">
                        <option value="">كل الشركاء</option>
                        @foreach ($partners as $p)
                            <option value="{{ $p->id }}" @selected($partnerId == $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="form-select" style="min-width:150px" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\PartnerDeposit::STATUSES as $k => $label)
                            <option value="{{ $k }}" @selected($status === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('partner_deposits.index') }}" class="btn btn-outline-secondary" title="مسح الفلاتر"><i class="fa-solid fa-xmark"></i></a>
                </form>
                @can('partners.create')
                    <a href="{{ route('partner_deposits.create') }}" class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-plus ms-1"></i> إيداع جديد</a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>الشريك</th>
                            <th>رأس المال</th>
                            <th>نسبة الربح</th>
                            <th>الدورية</th>
                            <th>المدة</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($deposits as $deposit)
                            <tr>
                                <td class="fw-semibold">{{ $deposit->partner?->name ?? '—' }}</td>
                                <td class="fw-bold">{{ number_format($deposit->amount, 2) }}</td>
                                <td>{{ rtrim(rtrim(number_format($deposit->profit_rate, 2), '0'), '.') }}%</td>
                                <td>{{ \App\Models\PartnerDeposit::PAYOUT_FREQUENCIES[$deposit->payout_frequency] ?? $deposit->payout_frequency }}</td>
                                <td>{{ $deposit->duration_months }} شهر</td>
                                <td><span class="badge text-bg-{{ $deposit->status === 'active' ? 'success' : 'secondary' }}">{{ \App\Models\PartnerDeposit::STATUSES[$deposit->status] ?? $deposit->status }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('partner_deposits.show', $deposit) }}" class="btn btn-sm btn-outline-secondary" title="عرض"><i class="fa-solid fa-eye"></i></a>
                                    @can('partners.delete')
                                        <form method="POST" action="{{ route('partner_deposits.destroy', $deposit) }}" class="d-inline" data-confirm="حذف الإيداع؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">لا توجد إيداعات بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $deposits->links() }}
        </div>
    </div>
@endsection
