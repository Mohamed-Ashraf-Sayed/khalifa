@extends('layouts.app')

@section('title', 'بيانات الشريك')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">{{ $partner->name }}</h5>
        <div class="d-flex gap-2">
            @can('partners.edit')
                <a href="{{ route('partners.edit', $partner) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
            @endcan
            <a href="{{ route('partners.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">الاسم</div><div class="fw-semibold">{{ $partner->name }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الهاتف</div><div dir="ltr" class="text-end">{{ $partner->phone ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">البريد</div><div dir="ltr" class="text-end">{{ $partner->email ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الرقم القومي</div><div>{{ $partner->national_id ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ الانضمام</div><div>{{ optional($partner->join_date)->format('Y-m-d') ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الحالة</div><div><span class="badge text-bg-light">{{ \App\Models\Partner::STATUSES[$partner->status] ?? $partner->status }}</span></div></div>
                <div class="col-md-12"><div class="text-muted small">العنوان</div><div>{{ $partner->address ?: '—' }}</div></div>
                @if ($partner->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $partner->notes }}</div></div>@endif
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><div class="text-muted small">إجمالي رأس المال المودَع</div><div class="fw-semibold">{{ number_format((float) $partner->totalCapital(), 2) }}</div></div>
                <div class="col-md-6"><div class="text-muted small">الرصيد الحالي</div><div class="fw-semibold">{{ number_format((float) $partner->currentBalance(), 2) }}</div></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">حركات الشريك <span class="badge text-bg-light">{{ $partner->transactions->count() }}</span></h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>التاريخ</th><th>النوع</th><th>المبلغ</th><th>البيان</th></tr></thead>
                    <tbody>
                        @forelse ($partner->transactions as $t)
                            <tr>
                                <td>{{ optional($t->transaction_date)->format('Y-m-d') ?: '—' }}</td>
                                <td><span class="badge text-bg-light">{{ \App\Models\PartnerTransaction::TYPES[$t->type] ?? $t->type }}</span></td>
                                <td>{{ number_format($t->amount, 2) }}</td>
                                <td>{{ $t->description ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">لا توجد حركات لهذا الشريك.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
