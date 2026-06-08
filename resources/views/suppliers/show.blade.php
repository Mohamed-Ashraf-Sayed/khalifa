@extends('layouts.app')

@section('title', 'بيانات المورد')

@section('content')
    {{-- شريط الإجراءات --}}
    <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
        <a href="{{ route('suppliers.statement', $supplier) }}" class="btn btn-sm" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-file-invoice ms-1"></i> كشف حساب</a>
        @can('suppliers.edit')
            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
        @endcan
        <a href="{{ route('suppliers.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
    </div>

    @php($poTotal = (float) $supplier->purchaseOrders->whereIn('status', ['partial','received'])->sum('net_amount'))
    @php($paidTotal = (float) $supplier->payments->sum('amount'))

    {{-- بطاقة الملف + المؤشرات المالية --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
                <span class="entity-avatar"><i class="fa-solid fa-truck-field"></i></span>
                <div class="flex-grow-1">
                    <div class="h5 mb-1">{{ $supplier->name }}</div>
                    <div class="text-muted small">{{ \App\Models\Supplier::TYPES[$supplier->type] ?? 'مورّد' }} · {{ $supplier->company_name ?: '—' }}</div>
                    <div class="mt-2">
                        @if ($supplier->is_active)<span class="badge text-bg-success">نشط</span>@else<span class="badge text-bg-secondary">غير نشط</span>@endif
                        @if ($supplier->overCreditLimit())<span class="badge text-bg-danger ms-1">تجاوز الحد الائتماني</span>@endif
                    </div>
                </div>
            </div></div>
        </div>
        <div class="col-lg-7">
            <div class="row g-3 h-100">
                <div class="col-6"><div class="stat-box accent"><div class="sl">الرصيد المستحقّ</div><div class="sv text-warning">{{ number_format((float) $supplier->balanceDue(), 2) }}</div></div></div>
                <div class="col-6"><div class="stat-box"><div class="sl">الحد الائتماني</div><div class="sv">{{ number_format((float) $supplier->credit_limit, 2) }}</div></div></div>
                <div class="col-6"><div class="stat-box"><div class="sl">إجمالي أوامر الشراء المستلَمة</div><div class="sv text-success">{{ number_format($poTotal, 2) }}</div></div></div>
                <div class="col-6"><div class="stat-box"><div class="sl">إجمالي المدفوع</div><div class="sv">{{ number_format($paidTotal, 2) }}</div></div></div>
            </div>
        </div>
    </div>

    {{-- بيانات المورّد --}}
    <div class="card mb-3"><div class="card-body">
        <h6 class="mb-3"><i class="fa-solid fa-address-card ms-1" style="color:#2b4c80"></i> بيانات المورّد</h6>
        <div class="info-list">
            <div class="il"><span class="k">الهاتف</span><span class="v" dir="ltr">{{ $supplier->phone ?: '—' }}</span></div>
            <div class="il"><span class="k">هاتف آخر</span><span class="v" dir="ltr">{{ $supplier->phone2 ?: '—' }}</span></div>
            <div class="il"><span class="k">البريد</span><span class="v" dir="ltr">{{ $supplier->email ?: '—' }}</span></div>
            <div class="il"><span class="k">الرقم الضريبي</span><span class="v">{{ $supplier->tax_number ?: '—' }}</span></div>
            <div class="il"><span class="k">السجل التجاري</span><span class="v">{{ $supplier->commercial_register ?: '—' }}</span></div>
            <div class="il"><span class="k">أيام السداد</span><span class="v">{{ $supplier->payment_terms !== null ? $supplier->payment_terms.' يوم' : '—' }}</span></div>
            <div class="il"><span class="k">العنوان</span><span class="v">{{ $supplier->address ?: '—' }}</span></div>
            @if ($supplier->creator)<div class="il"><span class="k">أُضيف بواسطة</span><span class="v">{{ $supplier->creator->name }}</span></div>@endif
            @if ($supplier->notes)<div class="il" style="grid-column:1/-1"><span class="k">ملاحظات</span><span class="v">{{ $supplier->notes }}</span></div>@endif
        </div>
    </div></div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">أوامر الشراء <span class="badge text-bg-light">{{ $supplier->purchaseOrders->count() }}</span></h6>
                @can('purchase_orders.create')
                    <a href="{{ route('purchase_orders.create', ['supplier_id' => $supplier->id]) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-plus ms-1"></i> أمر شراء جديد</a>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>رقم الأمر</th><th>التاريخ</th><th>القيمة</th><th>الحالة</th><th class="text-end"></th></tr></thead>
                    <tbody>
                        @forelse ($supplier->purchaseOrders as $order)
                            <tr>
                                <td><a href="{{ route('purchase_orders.show', $order) }}" class="fw-semibold text-decoration-none">{{ $order->order_number }}</a></td>
                                <td>{{ $order->order_date?->format('Y-m-d') ?: '—' }}</td>
                                <td>{{ number_format($order->total_amount, 2) }}</td>
                                <td><span class="badge text-bg-light">{{ \App\Models\PurchaseOrder::STATUSES[$order->status] ?? $order->status }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('purchase_orders.show', $order) }}" class="btn btn-sm btn-outline-secondary" title="عرض"><i class="fa-solid fa-eye"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">لا توجد أوامر شراء لهذا المورد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">المدفوعات <span class="badge text-bg-light">{{ $supplier->payments->count() }}</span></h6>
                @can('suppliers.create')
                    <a href="{{ route('supplier_payments.create', ['supplier_id' => $supplier->id]) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-plus ms-1"></i> دفعة جديدة</a>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>التاريخ</th><th>المبلغ</th><th>طريقة الدفع</th><th>المرجع</th><th class="text-end"></th></tr></thead>
                    <tbody>
                        @forelse ($supplier->payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_date?->format('Y-m-d') ?: '—' }}</td>
                                <td>{{ number_format($payment->amount, 2) }}</td>
                                <td><span class="badge text-bg-light">{{ \App\Models\SupplierPayment::PAYMENT_METHODS[$payment->payment_method] ?? $payment->payment_method }}</span></td>
                                <td>{{ $payment->reference_number ?: '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('supplier_payments.show', $payment) }}" class="btn btn-sm btn-outline-secondary" title="عرض"><i class="fa-solid fa-eye"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">لا توجد مدفوعات لهذا المورد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
