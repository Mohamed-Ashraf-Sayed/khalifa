@extends('layouts.app')

@section('title', 'أوامر الشراء')

@section('content')
    <div class="row g-3 mb-3">
        @foreach ([
            ['عدد الأوامر', number_format($stats['count']), 'fa-cart-shopping', 'text-primary'],
            ['صافي المشتريات', number_format($stats['net'], 0), 'fa-sack-dollar', 'text-success'],
            ['المدفوع', number_format($stats['paid'], 0), 'fa-money-check-dollar', 'text-info'],
            ['قيد الاعتماد', number_format($stats['pending']), 'fa-hourglass-half', 'text-warning'],
        ] as [$label, $val, $icon, $color])
        <div class="col-md-3 col-6">
            <div class="card h-100"><div class="card-body py-3">
                <i class="fa-solid {{ $icon }} {{ $color }}"></i>
                <div class="fs-4 fw-bold">{{ $val }}</div>
                <div class="small text-muted">{{ $label }}</div>
            </div></div>
        </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2" method="GET">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بحث برقم الأمر">
                    <select name="status" class="form-select" style="min-width:160px" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\PurchaseOrder::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                @can('purchase_orders.create')
                    <a href="{{ route('purchase_orders.create') }}" class="btn" style="background:#8b7355;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> أمر شراء جديد
                    </a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>رقم الأمر</th>
                            <th>المورّد</th>
                            <th>المشروع</th>
                            <th>الصافي</th>
                            <th>المتبقّي</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($purchaseOrders as $purchaseOrder)
                            @php($badge = match($purchaseOrder->status) {
                                'received' => 'success', 'partial' => 'info', 'approved' => 'primary',
                                'pending' => 'warning', 'cancelled' => 'danger', default => 'secondary' })
                            <tr>
                                <td class="fw-semibold">{{ $purchaseOrder->order_number }}</td>
                                <td>{{ $purchaseOrder->supplier?->name ?? '—' }}</td>
                                <td>{{ $purchaseOrder->project?->name ?? '—' }}</td>
                                <td class="fw-bold">{{ number_format($purchaseOrder->net_amount, 2) }} ج</td>
                                <td class="text-warning">{{ number_format($purchaseOrder->remaining(), 2) }}</td>
                                <td><span class="badge text-bg-{{ $badge }}">{{ \App\Models\PurchaseOrder::STATUSES[$purchaseOrder->status] ?? $purchaseOrder->status }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('purchase_orders.show', $purchaseOrder) }}" class="btn btn-sm btn-outline-secondary" title="عرض"><i class="fa-solid fa-eye"></i></a>
                                    @can('purchase_orders.edit')
                                        <a href="{{ route('purchase_orders.edit', $purchaseOrder) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('purchase_orders.delete')
                                        <form method="POST" action="{{ route('purchase_orders.destroy', $purchaseOrder) }}" class="d-inline"
                                              data-confirm="متأكد من حذف أمر الشراء؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">لا توجد أوامر شراء بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $purchaseOrders->links() }}
        </div>
    </div>
@endsection
