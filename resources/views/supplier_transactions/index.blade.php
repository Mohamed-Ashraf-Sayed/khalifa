@extends('layouts.app')

@section('title', 'مشتريات الموردين')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-4 col-6"><div class="card h-100"><div class="card-body py-3"><i class="fa-solid fa-bag-shopping text-primary"></i><div class="fs-4 fw-bold">{{ number_format($totalNet, 0) }}</div><div class="small text-muted">صافي المشتريات</div></div></div></div>
        <div class="col-md-4 col-6"><div class="card h-100"><div class="card-body py-3"><i class="fa-solid fa-money-check-dollar text-success"></i><div class="fs-4 fw-bold">{{ number_format($totalPaid, 0) }}</div><div class="small text-muted">المدفوع عند الشراء</div></div></div></div>
        <div class="col-md-4 col-12"><div class="card h-100"><div class="card-body py-3"><i class="fa-solid fa-scale-balanced text-warning"></i><div class="fs-4 fw-bold">{{ number_format($totalNet - $totalPaid, 0) }}</div><div class="small text-muted">المتبقّي</div></div></div></div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end gap-2 mb-3">
                @can('suppliers.create')
                    <a href="{{ route('supplier_transactions.create') }}" class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-plus ms-1"></i> عملية شراء جديدة</a>
                @endcan
            </div>

            <form method="GET" class="filter-bar row g-2 align-items-end mb-3">
                <div class="col-6 col-md-3">
                    <label class="form-label">المورد</label>
                    <select name="supplier_id" class="form-select" onchange="this.form.submit()">
                        <option value="">كل الموردين</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}" @selected($supplierId == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">الفئة</label>
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">كل الفئات</option>
                        @foreach (\App\Models\SupplierTransaction::CATEGORIES as $k => $label)
                            <option value="{{ $k }}" @selected($category === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
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
                        <tr><th>التاريخ</th><th>المورّد</th><th>البيان</th><th>الفئة</th><th>الكمية</th><th>الصافي</th><th>المدفوع</th><th>المتبقّي</th><th class="text-end">إجراءات</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $t)
                            <tr>
                                <td>{{ $t->transaction_date?->format('Y-m-d') }}</td>
                                <td>{{ $t->supplier?->name ?? '—' }}</td>
                                <td>{{ $t->item_description }}</td>
                                <td>{{ \App\Models\SupplierTransaction::CATEGORIES[$t->category] ?? '—' }}</td>
                                <td>{{ rtrim(rtrim(number_format($t->quantity, 3), '0'), '.') }} {{ $t->unit }}</td>
                                <td class="fw-semibold">{{ number_format($t->net_amount, 2) }}</td>
                                <td>{{ number_format($t->paid_amount, 2) }}</td>
                                <td class="text-warning">{{ number_format($t->remaining(), 2) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('supplier_transactions.show', $t) }}" class="btn btn-sm btn-outline-secondary" title="عرض"><i class="fa-solid fa-eye"></i></a>
                                    @can('suppliers.edit')
                                        <a href="{{ route('supplier_transactions.edit', $t) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('suppliers.delete')
                                        <form method="POST" action="{{ route('supplier_transactions.destroy', $t) }}" class="d-inline" data-confirm="حذف العملية؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">لا توجد عمليات شراء بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $transactions->links() }}
        </div>
    </div>
@endsection
