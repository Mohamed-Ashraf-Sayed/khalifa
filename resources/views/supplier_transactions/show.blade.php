@extends('layouts.app')

@section('title', 'عملية شراء')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">عملية شراء — {{ $transaction->item_description }}</h5>
        <div class="d-flex gap-2">
            @can('suppliers.edit')
                <a href="{{ route('supplier_transactions.edit', $transaction) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
            @endcan
            <a href="{{ route('supplier_transactions.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <div class="row g-3">
            <div class="col-md-4"><div class="text-muted small">المورّد</div><div class="fw-semibold">{{ $transaction->supplier?->name ?? '—' }}</div></div>
            <div class="col-md-4"><div class="text-muted small">المشروع</div><div>{{ $transaction->project?->name ?? 'عام' }}</div></div>
            <div class="col-md-4"><div class="text-muted small">التاريخ</div><div>{{ $transaction->transaction_date?->format('Y-m-d') }}</div></div>
            <div class="col-md-4"><div class="text-muted small">الفئة</div><div>{{ \App\Models\SupplierTransaction::CATEGORIES[$transaction->category] ?? '—' }}</div></div>
            <div class="col-md-4"><div class="text-muted small">الكمية × السعر</div><div>{{ rtrim(rtrim(number_format($transaction->quantity, 3), '0'), '.') }} {{ $transaction->unit }} × {{ number_format($transaction->unit_price, 2) }}</div></div>
            <div class="col-md-4"><div class="text-muted small">طريقة الدفع</div><div>{{ \App\Models\SupplierTransaction::PAYMENT_METHODS[$transaction->payment_method] ?? '—' }} @if($transaction->check_number)· شيك {{ $transaction->check_number }}@endif</div></div>
        </div>
        <hr>
        <div class="row g-3 text-center">
            <div class="col"><div class="text-muted small">الإجمالي</div><div class="fs-5 fw-bold">{{ number_format($transaction->total_amount, 2) }}</div></div>
            <div class="col"><div class="text-muted small">الخصم</div><div class="fs-5">{{ rtrim(rtrim(number_format($transaction->discount_percentage, 2), '0'), '.') }}%</div></div>
            <div class="col"><div class="text-muted small">الصافي</div><div class="fs-5 fw-bold text-success">{{ number_format($transaction->net_amount, 2) }}</div></div>
            <div class="col"><div class="text-muted small">المدفوع</div><div class="fs-5">{{ number_format($transaction->paid_amount, 2) }}</div></div>
            <div class="col"><div class="text-muted small">المتبقّي</div><div class="fs-5 fw-bold text-warning">{{ number_format($transaction->remaining(), 2) }}</div></div>
        </div>
        @if ($transaction->notes)<hr><div class="text-muted small">ملاحظات</div><div>{{ $transaction->notes }}</div>@endif
    </div></div>
@endsection
