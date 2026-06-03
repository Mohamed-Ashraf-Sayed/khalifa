@extends('layouts.app')

@section('title', 'مدفوعات الموردين')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <div class="text-muted small">إجمالي المدفوعات</div>
                <div class="fs-4 fw-bold text-danger">{{ number_format($total, 2) }} ج</div>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2 flex-wrap" method="GET">
                    <select name="supplier_id" class="form-select" style="min-width:180px" onchange="this.form.submit()">
                        <option value="">كل الموردين</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}" @selected($supplierId == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                    <select name="payment_method" class="form-select" style="min-width:150px" onchange="this.form.submit()">
                        <option value="">كل طرق الدفع</option>
                        @foreach (\App\Models\SupplierPayment::PAYMENT_METHODS as $k => $label)
                            <option value="{{ $k }}" @selected($paymentMethod === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control" style="min-width:150px" title="من تاريخ" onchange="this.form.submit()">
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control" style="min-width:150px" title="إلى تاريخ" onchange="this.form.submit()">
                    <a href="{{ route('supplier_payments.index') }}" class="btn btn-outline-secondary" title="مسح الفلاتر"><i class="fa-solid fa-xmark"></i></a>
                </form>
                @can('suppliers.create')
                    <a href="{{ route('supplier_payments.create') }}" class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-plus ms-1"></i> دفعة جديدة</a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>التاريخ</th>
                            <th>المورد</th>
                            <th>المبلغ</th>
                            <th>طريقة الدفع</th>
                            <th>الحساب البنكي</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                                <td class="fw-semibold">{{ $payment->supplier?->name ?? '—' }}</td>
                                <td class="fw-bold text-danger">{{ number_format($payment->amount, 2) }}</td>
                                <td>{{ \App\Models\SupplierPayment::PAYMENT_METHODS[$payment->payment_method] ?? $payment->payment_method }}</td>
                                <td>
                                    @if ($payment->bankAccount)
                                        <i class="fa-solid fa-building-columns text-muted small"></i> {{ $payment->bankAccount->name }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('supplier_payments.show', $payment) }}" class="btn btn-sm btn-outline-secondary" title="عرض"><i class="fa-solid fa-eye"></i></a>
                                    @can('suppliers.edit')
                                        <a href="{{ route('supplier_payments.edit', $payment) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('suppliers.delete')
                                        <form method="POST" action="{{ route('supplier_payments.destroy', $payment) }}" class="d-inline" onsubmit="return confirm('حذف الدفعة؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">لا توجد مدفوعات بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $payments->links() }}
        </div>
    </div>
@endsection
