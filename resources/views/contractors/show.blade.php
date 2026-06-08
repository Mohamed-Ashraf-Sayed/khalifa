@extends('layouts.app')

@section('title', 'بيانات المقاول')

@section('content')
    {{-- شريط الإجراءات --}}
    <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
        @can('contractors.create')
            <a href="{{ route('contractor_extracts.create', ['contractor_id' => $contractor->id]) }}" class="btn btn-sm" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-plus ms-1"></i> مستخلص جديد</a>
            <a href="{{ route('contractor_payments.create', ['contractor_id' => $contractor->id]) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-money-bill-wave ms-1"></i> دفعة جديدة</a>
        @endcan
        <a href="{{ route('contractors.statement', $contractor) }}" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-file-invoice ms-1"></i> كشف حساب</a>
        @can('contractors.edit')
            <a href="{{ route('contractors.edit', $contractor) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
        @endcan
        <a href="{{ route('contractors.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
    </div>

    @php
        $earned = (float) $contractor->extracts->whereIn('status', ['approved','partial','paid'])->sum('net_amount');
        $paid = (float) $contractor->payments->sum('amount');
    @endphp

    {{-- بطاقة الملف + المؤشرات المالية --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
                <span class="entity-avatar"><i class="fa-solid fa-hard-hat"></i></span>
                <div class="flex-grow-1">
                    <div class="h5 mb-1">{{ $contractor->name }}</div>
                    <div class="text-muted small">{{ $contractor->specialty ?: 'مقاول' }} · {{ $contractor->contractor_code }}</div>
                    <div class="mt-2">
                        @if ($contractor->is_active)<span class="badge text-bg-success">نشط</span>@else<span class="badge text-bg-secondary">غير نشط</span>@endif
                    </div>
                </div>
            </div></div>
        </div>
        <div class="col-lg-7">
            <div class="row g-3 h-100">
                <div class="col-6"><div class="stat-box accent"><div class="sl">الرصيد المستحقّ</div><div class="sv text-warning">{{ number_format((float) $contractor->balanceDue(), 2) }}</div></div></div>
                <div class="col-6"><div class="stat-box"><div class="sl">المحتجز (غير المُحرَّر)</div><div class="sv text-info">{{ number_format((float) $contractor->retentionHeld(), 2) }}</div></div></div>
                <div class="col-6"><div class="stat-box"><div class="sl">إجمالي المستخلصات الصافية</div><div class="sv text-success">{{ number_format($earned, 2) }}</div></div></div>
                <div class="col-6"><div class="stat-box"><div class="sl">إجمالي المدفوع</div><div class="sv">{{ number_format($paid, 2) }}</div></div></div>
            </div>
        </div>
    </div>

    {{-- بيانات المقاول --}}
    <div class="card mb-3"><div class="card-body">
        <h6 class="mb-3"><i class="fa-solid fa-address-card ms-1" style="color:#2b4c80"></i> بيانات المقاول</h6>
        <div class="info-list">
            <div class="il"><span class="k">الشركة</span><span class="v">{{ $contractor->company_name ?: '—' }}</span></div>
            <div class="il"><span class="k">الهاتف</span><span class="v" dir="ltr">{{ $contractor->phone ?: '—' }}</span></div>
            <div class="il"><span class="k">هاتف آخر</span><span class="v" dir="ltr">{{ $contractor->phone2 ?: '—' }}</span></div>
            <div class="il"><span class="k">البريد</span><span class="v" dir="ltr">{{ $contractor->email ?: '—' }}</span></div>
            <div class="il"><span class="k">الرقم القومي</span><span class="v">{{ $contractor->national_id ?: '—' }}</span></div>
            <div class="il"><span class="k">الرقم الضريبي</span><span class="v">{{ $contractor->tax_number ?: '—' }}</span></div>
            <div class="il"><span class="k">أُضيف بواسطة</span><span class="v">{{ $contractor->creator?->name ?? '—' }}</span></div>
            @if ($contractor->notes)<div class="il" style="grid-column:1/-1"><span class="k">ملاحظات</span><span class="v">{{ $contractor->notes }}</span></div>@endif
        </div>
    </div></div>

    @php($extractBadge = ['pending' => 'text-bg-warning', 'approved' => 'text-bg-info', 'partial' => 'text-bg-warning', 'paid' => 'text-bg-success', 'cancelled' => 'text-bg-secondary'])
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">أحدث المستخلصات <span class="badge text-bg-light">{{ $contractor->extracts->count() }}</span></h6>
                <a href="{{ route('contractor_extracts.index', ['contractor_id' => $contractor->id]) }}" class="small text-decoration-none">عرض الكل</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>رقم المستخلص</th><th>المشروع</th><th>التاريخ</th><th>الصافي</th><th>الحالة</th><th class="text-end"></th></tr></thead>
                    <tbody>
                        @forelse ($contractor->extracts->take(10) as $extract)
                            <tr role="button" style="cursor:pointer" onclick="window.location='{{ route('contractor_extracts.show', $extract) }}'">
                                <td class="fw-semibold">{{ $extract->extract_number }}</td>
                                <td>{{ $extract->project?->name ?? '—' }}</td>
                                <td>{{ $extract->extract_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="fw-semibold">{{ number_format($extract->net_amount, 2) }}</td>
                                <td><span class="badge {{ $extractBadge[$extract->status] ?? 'text-bg-light' }}">{{ \App\Models\ContractorExtract::STATUSES[$extract->status] ?? $extract->status }}</span></td>
                                <td class="text-end"><i class="fa-solid fa-chevron-left text-muted small"></i></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">لا توجد مستخلصات لهذا المقاول.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">أحدث الدفعات <span class="badge text-bg-light">{{ $contractor->payments->count() }}</span></h6>
                <a href="{{ route('contractor_payments.index', ['contractor_id' => $contractor->id]) }}" class="small text-decoration-none">عرض الكل</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>التاريخ</th><th>المبلغ</th><th>طريقة الدفع</th><th>المرجع</th><th class="text-end"></th></tr></thead>
                    <tbody>
                        @forelse ($contractor->payments->take(10) as $payment)
                            <tr role="button" style="cursor:pointer" onclick="window.location='{{ route('contractor_payments.show', $payment) }}'">
                                <td>{{ $payment->payment_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="fw-semibold">{{ number_format($payment->amount, 2) }}</td>
                                <td>{{ \App\Models\ContractorPayment::PAYMENT_METHODS[$payment->payment_method] ?? $payment->payment_method }}</td>
                                <td>{{ $payment->reference_number ?: '—' }}</td>
                                <td class="text-end"><i class="fa-solid fa-chevron-left text-muted small"></i></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">لا توجد دفعات لهذا المقاول.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
