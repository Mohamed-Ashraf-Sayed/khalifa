@extends('layouts.app')

@section('title', 'أمر شراء ' . $purchaseOrder->order_number)

@section('content')
    @php($badge = match($purchaseOrder->status) {
        'received' => 'success', 'partial' => 'info', 'approved' => 'primary',
        'pending' => 'warning', 'cancelled' => 'danger', default => 'secondary' })
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">أمر شراء رقم {{ $purchaseOrder->order_number }}</h5>
        <div class="d-flex gap-2">
            @can('purchase_orders.edit')
                @if (in_array($purchaseOrder->status, ['draft', 'pending']))
                    <form method="POST" action="{{ route('purchase_orders.approve', $purchaseOrder) }}" class="d-inline" data-confirm="اعتماد أمر الشراء؟">
                        @csrf
                        <button class="btn btn-sm btn-success"><i class="fa-solid fa-check ms-1"></i> اعتماد</button>
                    </form>
                @endif
                @if (! in_array($purchaseOrder->status, ['received', 'cancelled']))
                    <form method="POST" action="{{ route('purchase_orders.receive', $purchaseOrder) }}" class="d-inline" data-confirm="تأكيد استلام الأصناف{{ $purchaseOrder->add_to_inventory ? ' وإضافتها للمخزون' : '' }}؟">
                        @csrf
                        <button class="btn btn-sm" style="background:#0f7a4f;color:#fff"><i class="fa-solid fa-truck-ramp-box ms-1"></i> استلام</button>
                    </form>
                @endif
                <a href="{{ route('purchase_orders.edit', $purchaseOrder) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
            @endcan
            <a href="{{ route('purchase_orders.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-7">
            <div class="card h-100"><div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><div class="text-muted small">المورّد</div><div class="fw-semibold">{{ $purchaseOrder->supplier?->name ?? '—' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">المشروع</div><div>{{ $purchaseOrder->project?->name ?? 'عام' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">الحالة</div><div><span class="badge text-bg-{{ $badge }}">{{ \App\Models\PurchaseOrder::STATUSES[$purchaseOrder->status] ?? $purchaseOrder->status }}</span></div></div>
                    <div class="col-md-6"><div class="text-muted small">تاريخ الأمر</div><div>{{ $purchaseOrder->order_date?->format('Y-m-d') ?? '—' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">التسليم المتوقع</div><div>{{ $purchaseOrder->expected_delivery?->format('Y-m-d') ?? '—' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">التسليم الفعلي</div><div>{{ $purchaseOrder->actual_delivery?->format('Y-m-d') ?? '—' }}</div></div>
                    @if ($purchaseOrder->approver)<div class="col-md-6"><div class="text-muted small">اعتمده</div><div>{{ $purchaseOrder->approver->name }} · {{ $purchaseOrder->approved_at?->format('Y-m-d') }}</div></div>@endif
                    <div class="col-md-6"><div class="text-muted small">إضافة للمخزون</div><div>{{ $purchaseOrder->add_to_inventory ? 'نعم' : 'لا' }}</div></div>
                    @if ($purchaseOrder->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $purchaseOrder->notes }}</div></div>@endif
                </div>
            </div></div>
        </div>
        <div class="col-md-5">
            <div class="card h-100"><div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted">إجمالي الأصناف</td><td class="text-end fw-semibold">{{ number_format($purchaseOrder->total_amount, 2) }}</td></tr>
                    <tr><td class="text-muted">الخصم</td><td class="text-end text-danger">− {{ number_format($purchaseOrder->discount, 2) }}</td></tr>
                    <tr><td class="text-muted">الضريبة</td><td class="text-end">+ {{ number_format($purchaseOrder->tax, 2) }}</td></tr>
                    <tr class="border-top"><td class="fw-bold">الصافي</td><td class="text-end fw-bold fs-5 text-success">{{ number_format($purchaseOrder->net_amount, 2) }}</td></tr>
                    <tr><td class="text-muted">المدفوع</td><td class="text-end">{{ number_format($purchaseOrder->paid_amount, 2) }}</td></tr>
                    <tr><td class="text-muted">المتبقّي</td><td class="text-end fw-semibold text-warning">{{ number_format($purchaseOrder->remaining(), 2) }}</td></tr>
                </table>
            </div></div>
        </div>
    </div>

    @can('purchase_orders.edit')
    <div class="card mb-3"><div class="card-body">
        <form method="POST" action="{{ route('purchase_order_items.store', $purchaseOrder) }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-4"><label class="form-label small">الصنف</label><input type="text" name="description" class="form-control" required></div>
            <div class="col-md-2"><label class="form-label small">الوحدة</label><input type="text" name="unit" class="form-control" placeholder="قطعة/طن.."></div>
            <div class="col-md-2"><label class="form-label small">الكمية</label><input type="number" step="0.001" min="0.001" name="quantity" value="1" class="form-control" required></div>
            <div class="col-md-2"><label class="form-label small">سعر الوحدة</label><input type="number" step="0.01" min="0" name="unit_price" class="form-control" required></div>
            <div class="col-md-2"><button class="btn w-100" style="background:#2b4c80;color:#fff">إضافة صنف</button></div>
        </form>
    </div></div>
    @endcan

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">أصناف أمر الشراء</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light"><tr><th>الصنف</th><th>الوحدة</th><th>الكمية</th><th>سعر الوحدة</th><th>الإجمالي</th><th class="text-end"></th></tr></thead>
                    <tbody>
                        @forelse ($purchaseOrder->items as $item)
                            <tr>
                                <td>{{ $item->description }}</td>
                                <td>{{ $item->unit ?? '—' }}</td>
                                <td>{{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</td>
                                <td>{{ number_format($item->unit_price, 2) }}</td>
                                <td class="fw-semibold">{{ number_format($item->total_price, 2) }}</td>
                                <td class="text-end">
                                    @can('purchase_orders.edit')
                                        <form method="POST" action="{{ route('purchase_order_items.destroy', $item) }}" class="d-inline" data-confirm="حذف الصنف؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">لا توجد أصناف. أضِف صنفاً من الأعلى.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
