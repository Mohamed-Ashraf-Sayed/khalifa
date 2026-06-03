@extends('layouts.app')

@section('title', 'بيانات الإيراد')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">{{ $revenue->description }}</h5>
        <div class="d-flex gap-2">
            @can('revenues.edit')
                <a href="{{ route('revenues.edit', $revenue) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
            @endcan
            <a href="{{ route('revenues.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">البيان</div><div class="fw-semibold">{{ $revenue->description }}</div></div>
                <div class="col-md-4"><div class="text-muted small">المبلغ</div><div class="fw-bold text-success">{{ number_format($revenue->amount, 2) }} ج</div></div>
                <div class="col-md-4"><div class="text-muted small">التاريخ</div><div>{{ $revenue->revenue_date->format('Y-m-d') }}</div></div>
                <div class="col-md-4"><div class="text-muted small">المشروع</div><div>{{ $revenue->project?->name ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">طريقة الاستلام</div><div>{{ \App\Models\Revenue::PAYMENT_METHODS[$revenue->payment_method] ?? $revenue->payment_method }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الحساب البنكي</div><div>{{ $revenue->bankAccount?->name ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">سُجّل بواسطة</div><div>{{ $revenue->creator?->name ?: '—' }}</div></div>
                @if ($revenue->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $revenue->notes }}</div></div>@endif
            </div>
        </div>
    </div>

    @if ($revenue->bankAccount)
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3">الحساب البنكي المرتبط</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>الحساب</th><th>البنك</th><th>رقم الحساب</th></tr></thead>
                        <tbody>
                            <tr>
                                <td>{{ $revenue->bankAccount->name }}</td>
                                <td>{{ $revenue->bankAccount->bank_name ?: '—' }}</td>
                                <td dir="ltr" class="text-end">{{ $revenue->bankAccount->account_number ?: '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
