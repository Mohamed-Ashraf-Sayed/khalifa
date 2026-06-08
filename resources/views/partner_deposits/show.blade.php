@extends('layouts.app')

@section('title', 'تفاصيل إيداع')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">إيداع رأس مال — {{ $deposit->partner?->name ?? '—' }}</h5>
        <div class="d-flex gap-2">
            @if ($deposit->status === 'active')
                @can('partners.create')
                    <form method="POST" action="{{ route('partner_deposits.settle', $deposit) }}" class="d-inline d-flex gap-2 align-items-center" data-confirm="تسوية الإيداع وإرجاع رأس المال؟">
                        @csrf
                        <select name="bank_account_id" class="form-select form-select-sm" style="max-width:200px">
                            <option value="">— بدون حساب —</option>
                            @foreach ($accounts as $a)
                                <option value="{{ $a->id }}">{{ $a->name }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-sm btn-warning"><i class="fa-solid fa-rotate-left ms-1"></i> تسوية وإرجاع رأس المال</button>
                    </form>
                @endcan
            @endif
            <a href="{{ route('partner_deposits.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-7">
            <div class="card h-100"><div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><div class="text-muted small">الشريك</div><div class="fw-semibold">{{ $deposit->partner?->name ?? '—' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">رأس المال</div><div class="fw-bold">{{ number_format($deposit->amount, 2) }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">تاريخ الإيداع</div><div>{{ optional($deposit->deposit_date)->format('Y-m-d') ?: '—' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">نسبة الربح السنوية</div><div>{{ rtrim(rtrim(number_format($deposit->profit_rate, 2), '0'), '.') }}%</div></div>
                    <div class="col-md-6"><div class="text-muted small">الدورية</div><div>{{ \App\Models\PartnerDeposit::PAYOUT_FREQUENCIES[$deposit->payout_frequency] ?? $deposit->payout_frequency }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">المدة</div><div>{{ $deposit->duration_months }} شهر</div></div>
                    <div class="col-md-6"><div class="text-muted small">الحساب البنكي</div><div>{{ $deposit->bankAccount?->name ?? '—' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">الحالة</div><div><span class="badge text-bg-{{ $deposit->status === 'active' ? 'success' : 'secondary' }}">{{ \App\Models\PartnerDeposit::STATUSES[$deposit->status] ?? $deposit->status }}</span></div></div>
                    @if ($deposit->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $deposit->notes }}</div></div>@endif
                </div>
            </div></div>
        </div>
        <div class="col-md-5">
            <div class="card h-100"><div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted">ربح الدورة الواحدة</td><td class="text-end fw-semibold">{{ number_format((float) $deposit->profitPerPeriod(), 2) }}</td></tr>
                    <tr><td class="text-muted">إجمالي الأرباح المجدولة</td><td class="text-end">{{ number_format((float) $deposit->totalScheduledProfit(), 2) }}</td></tr>
                    <tr class="border-top"><td class="text-muted">الأرباح المصروفة</td><td class="text-end fw-bold text-success">{{ number_format((float) $deposit->totalPaidProfit(), 2) }}</td></tr>
                </table>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">جدول صرف الأرباح <span class="badge text-bg-light">{{ $deposit->schedules->count() }}</span></h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>تاريخ الاستحقاق</th><th>المبلغ</th><th>الحالة</th><th>تاريخ الصرف</th><th class="text-end">إجراء</th></tr></thead>
                    <tbody>
                        @forelse ($deposit->schedules as $schedule)
                            <tr>
                                <td>{{ optional($schedule->due_date)->format('Y-m-d') ?: '—' }}</td>
                                <td class="fw-semibold">{{ number_format($schedule->amount, 2) }}</td>
                                <td>
                                    @if ($schedule->is_paid)
                                        <span class="badge text-bg-success">مصروف</span>
                                    @else
                                        <span class="badge text-bg-secondary">غير مصروف</span>
                                    @endif
                                </td>
                                <td>{{ optional($schedule->paid_date)->format('Y-m-d') ?: '—' }}</td>
                                <td class="text-end">
                                    @if (! $schedule->is_paid)
                                        @can('partners.create')
                                            <form method="POST" action="{{ route('partner_deposits.pay_profit', [$deposit, $schedule]) }}" class="d-inline-flex gap-2 align-items-center" data-confirm="صرف هذه الدفعة؟">
                                                @csrf
                                                <select name="bank_account_id" class="form-select form-select-sm" style="max-width:170px">
                                                    <option value="">— بدون حساب —</option>
                                                    @foreach ($accounts as $a)
                                                        <option value="{{ $a->id }}">{{ $a->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button class="btn btn-sm" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-money-bill-wave ms-1"></i> صرف</button>
                                            </form>
                                        @endcan
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">لا يوجد جدول أرباح.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
