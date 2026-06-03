@extends('layouts.app')

@section('title', 'بيانات أمر الشراء')

@section('content')
    @php($badge = match($purchaseOrder->status) {
        'received' => 'success', 'approved' => 'primary',
        'pending' => 'warning', 'cancelled' => 'danger', default => 'secondary' })
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">أمر شراء رقم {{ $purchaseOrder->order_number }}</h5>
        <div class="d-flex gap-2">
            @can('purchase_orders.edit')
                <a href="{{ route('purchase_orders.edit', $purchaseOrder) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
            @endcan
            <a href="{{ route('purchase_orders.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">رقم الأمر</div><div class="fw-semibold">{{ $purchaseOrder->order_number }}</div></div>
                <div class="col-md-4"><div class="text-muted small">المورّد</div><div>{{ $purchaseOrder->supplier?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">المشروع</div><div>{{ $purchaseOrder->project?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">المبلغ الإجمالي</div><div class="fw-bold">{{ number_format($purchaseOrder->total_amount, 2) }} ج</div></div>
                <div class="col-md-4"><div class="text-muted small">الحالة</div><div><span class="badge text-bg-{{ $badge }}">{{ \App\Models\PurchaseOrder::STATUSES[$purchaseOrder->status] ?? $purchaseOrder->status }}</span></div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ الأمر</div><div>{{ $purchaseOrder->order_date?->format('Y-m-d') ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ التسليم المتوقع</div><div>{{ $purchaseOrder->expected_delivery?->format('Y-m-d') ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">أنشأ بواسطة</div><div>{{ $purchaseOrder->creator?->name ?? '—' }}</div></div>
                @if ($purchaseOrder->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $purchaseOrder->notes }}</div></div>@endif
            </div>
        </div>
    </div>
@endsection
