@extends('layouts.app')

@section('title', 'دفعات المقاولين')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <div class="text-muted small">إجمالي دفعات المقاولين</div>
                <div class="fs-4 fw-bold text-danger">{{ number_format($total, 2) }} ج</div>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end gap-2 mb-3">
                @can('contractors.create')
                    <a href="{{ route('contractor_payments.create') }}" class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-plus ms-1"></i> دفعة جديدة</a>
                @endcan
            </div>

            <form method="GET" class="filter-bar row g-2 align-items-end mb-3">
                <div class="col-6 col-md-3">
                    <label class="form-label">المقاول</label>
                    <select name="contractor_id" class="form-select" onchange="this.form.submit()">
                        <option value="">كل المقاولين</option>
                        @foreach ($contractors as $c)
                            <option value="{{ $c->id }}" @selected($contractorId == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">طريقة الدفع</label>
                    <select name="payment_method" class="form-select" onchange="this.form.submit()">
                        <option value="">كل طرق الدفع</option>
                        @foreach (\App\Models\ContractorPayment::PAYMENT_METHODS as $k => $label)
                            <option value="{{ $k }}" @selected($paymentMethod === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">من تاريخ</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control" onchange="this.form.submit()">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">إلى تاريخ</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control" onchange="this.form.submit()">
                </div>
                <div class="col-12 col-md-auto">
                    <div class="filter-actions">
                        <button class="btn btn-primary"><i class="fa-solid fa-magnifying-glass ms-1"></i> بحث</button>
                        @if (request()->query())
                            <a href="{{ url()->current() }}" class="btn btn-light">مسح</a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>التاريخ</th>
                            <th>المقاول</th>
                            <th>المبلغ</th>
                            <th>طريقة الدفع</th>
                            <th>البنك</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                                <td class="fw-semibold">{{ $payment->contractor?->name ?? '—' }}</td>
                                <td class="fw-bold text-danger">{{ number_format($payment->amount, 2) }}</td>
                                <td>{{ \App\Models\ContractorPayment::PAYMENT_METHODS[$payment->payment_method] ?? $payment->payment_method }}</td>
                                <td>
                                    @if ($payment->bankAccount)
                                        <i class="fa-solid fa-building-columns text-muted small" title="{{ $payment->bankAccount->name }}"></i>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('contractor_payments.show', $payment) }}" class="btn btn-sm btn-outline-secondary" title="عرض"><i class="fa-solid fa-eye"></i></a>
                                    @can('contractors.edit')
                                        <a href="{{ route('contractor_payments.edit', $payment) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('contractors.delete')
                                        <form method="POST" action="{{ route('contractor_payments.destroy', $payment) }}" class="d-inline" data-confirm="حذف الدفعة؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">لا توجد دفعات بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $payments->links() }}
        </div>
    </div>
@endsection
