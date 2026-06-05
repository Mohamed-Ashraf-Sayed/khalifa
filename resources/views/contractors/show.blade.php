@extends('layouts.app')

@section('title', 'بيانات المقاول')

@section('content')
    {{-- شريط الإجراءات --}}
    <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
        <a href="{{ route('contractors.statement', $contractor) }}" class="btn btn-sm" style="background:#8b7355;color:#fff"><i class="fa-solid fa-file-invoice ms-1"></i> كشف حساب</a>
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
        <h6 class="mb-3"><i class="fa-solid fa-address-card ms-1" style="color:#8b7355"></i> بيانات المقاول</h6>
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

    <div class="card mb-3">
        <div class="card-body">
            <h6 class="mb-3">أحدث المستخلصات <span class="badge text-bg-light">{{ $contractor->extracts->count() }}</span></h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>رقم المستخلص</th><th>التاريخ</th><th>الصافي</th><th>الحالة</th></tr></thead>
                    <tbody>
                        @forelse ($contractor->extracts->take(10) as $extract)
                            <tr>
                                <td>{{ $extract->extract_number }}</td>
                                <td>{{ $extract->extract_date?->format('Y-m-d') ?? '—' }}</td>
                                <td>{{ number_format($extract->net_amount, 2) }}</td>
                                <td><span class="badge text-bg-light">{{ \App\Models\ContractorExtract::STATUSES[$extract->status] ?? $extract->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">لا توجد مستخلصات لهذا المقاول.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">أحدث الدفعات <span class="badge text-bg-light">{{ $contractor->payments->count() }}</span></h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>التاريخ</th><th>المبلغ</th><th>طريقة الدفع</th><th>المرجع</th></tr></thead>
                    <tbody>
                        @forelse ($contractor->payments->take(10) as $payment)
                            <tr>
                                <td>{{ $payment->payment_date?->format('Y-m-d') ?? '—' }}</td>
                                <td>{{ number_format($payment->amount, 2) }}</td>
                                <td>{{ \App\Models\ContractorPayment::PAYMENT_METHODS[$payment->payment_method] ?? $payment->payment_method }}</td>
                                <td>{{ $payment->reference_number ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">لا توجد دفعات لهذا المقاول.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
