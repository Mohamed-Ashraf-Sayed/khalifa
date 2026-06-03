@extends('layouts.app')

@section('title', 'بيانات المورد')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">{{ $supplier->name }}</h5>
        <div class="d-flex gap-2">
            <a href="{{ route('suppliers.statement', $supplier) }}" class="btn btn-sm" style="background:#8b7355;color:#fff"><i class="fa-solid fa-file-invoice ms-1"></i> كشف حساب</a>
            @can('suppliers.edit')
                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
            @endcan
            <a href="{{ route('suppliers.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">الاسم</div><div class="fw-semibold">{{ $supplier->name }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الشركة</div><div>{{ $supplier->company_name ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">النوع</div><div><span class="badge text-bg-light">{{ \App\Models\Supplier::TYPES[$supplier->type] ?? $supplier->type }}</span></div></div>
                <div class="col-md-4"><div class="text-muted small">الهاتف</div><div dir="ltr" class="text-end">{{ $supplier->phone }}</div></div>
                <div class="col-md-4"><div class="text-muted small">هاتف آخر</div><div dir="ltr" class="text-end">{{ $supplier->phone2 ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">البريد</div><div dir="ltr" class="text-end">{{ $supplier->email ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الرقم الضريبي</div><div>{{ $supplier->tax_number ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">السجل التجاري</div><div>{{ $supplier->commercial_register ?: '—' }}</div></div>
                <div class="col-md-4">
                    <div class="text-muted small">الحالة</div>
                    <div>
                        @if ($supplier->is_active)
                            <span class="badge text-bg-success">نشط</span>
                        @else
                            <span class="badge text-bg-secondary">غير نشط</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-4"><div class="text-muted small">الرصيد المستحق</div><div class="fw-semibold">{{ number_format((float) $supplier->balanceDue(), 2) }}</div></div>
                @if ($supplier->creator)<div class="col-md-4"><div class="text-muted small">أُضيف بواسطة</div><div>{{ $supplier->creator->name }}</div></div>@endif
                <div class="col-md-12"><div class="text-muted small">العنوان</div><div>{{ $supplier->address ?: '—' }}</div></div>
                @if ($supplier->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $supplier->notes }}</div></div>@endif
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h6 class="mb-3">أوامر الشراء <span class="badge text-bg-light">{{ $supplier->purchaseOrders->count() }}</span></h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>رقم الأمر</th><th>التاريخ</th><th>القيمة</th><th>الحالة</th></tr></thead>
                    <tbody>
                        @forelse ($supplier->purchaseOrders as $order)
                            <tr>
                                <td>{{ $order->order_number }}</td>
                                <td>{{ $order->order_date?->format('Y-m-d') ?: '—' }}</td>
                                <td>{{ number_format($order->total_amount, 2) }}</td>
                                <td><span class="badge text-bg-light">{{ \App\Models\PurchaseOrder::STATUSES[$order->status] ?? $order->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">لا توجد أوامر شراء لهذا المورد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">المدفوعات <span class="badge text-bg-light">{{ $supplier->payments->count() }}</span></h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>التاريخ</th><th>المبلغ</th><th>طريقة الدفع</th><th>المرجع</th></tr></thead>
                    <tbody>
                        @forelse ($supplier->payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_date?->format('Y-m-d') ?: '—' }}</td>
                                <td>{{ number_format($payment->amount, 2) }}</td>
                                <td><span class="badge text-bg-light">{{ \App\Models\SupplierPayment::PAYMENT_METHODS[$payment->payment_method] ?? $payment->payment_method }}</span></td>
                                <td>{{ $payment->reference_number ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">لا توجد مدفوعات لهذا المورد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
