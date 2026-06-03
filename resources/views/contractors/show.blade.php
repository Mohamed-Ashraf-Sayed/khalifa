@extends('layouts.app')

@section('title', 'بيانات المقاول')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">{{ $contractor->name }}</h5>
        <div class="d-flex gap-2">
            @can('contractors.edit')
                <a href="{{ route('contractors.edit', $contractor) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
            @endcan
            <a href="{{ route('contractors.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">الكود</div><div class="fw-semibold">{{ $contractor->contractor_code }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الاسم</div><div class="fw-semibold">{{ $contractor->name }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الشركة</div><div>{{ $contractor->company_name ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">التخصص</div><div>{{ $contractor->specialty ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الهاتف</div><div dir="ltr" class="text-end">{{ $contractor->phone ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">هاتف آخر</div><div dir="ltr" class="text-end">{{ $contractor->phone2 ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">البريد</div><div dir="ltr" class="text-end">{{ $contractor->email ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الرقم القومي</div><div>{{ $contractor->national_id ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الرقم الضريبي</div><div>{{ $contractor->tax_number ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الحالة</div><div>
                    @if ($contractor->is_active)
                        <span class="badge text-bg-success">نشط</span>
                    @else
                        <span class="badge text-bg-secondary">غير نشط</span>
                    @endif
                </div></div>
                <div class="col-md-4"><div class="text-muted small">الرصيد المستحقّ</div><div class="fw-semibold">{{ number_format((float) $contractor->balanceDue(), 2) }}</div></div>
                <div class="col-md-4"><div class="text-muted small">أضيف بواسطة</div><div>{{ $contractor->creator?->name ?? '—' }}</div></div>
                @if ($contractor->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $contractor->notes }}</div></div>@endif
            </div>
        </div>
    </div>

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
