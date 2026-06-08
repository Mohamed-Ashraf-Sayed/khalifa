@extends('layouts.app')

@section('title', 'الضرائب')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card"><div class="card-body">
                <div class="text-muted small">إجمالي المستحق</div>
                <div class="fs-4 fw-bold text-danger">{{ number_format($totalPending, 2) }} ج</div>
            </div></div>
        </div>
        <div class="col-md-6">
            <div class="card"><div class="card-body">
                <div class="text-muted small">إجمالي المدفوع</div>
                <div class="fs-4 fw-bold text-success">{{ number_format($totalPaid, 2) }} ج</div>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end gap-2 mb-3">
                @can('taxes.create')
                    <a href="{{ route('taxes.create') }}" class="btn" style="background:#2b4c80;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> ضريبة جديدة
                    </a>
                @endcan
            </div>

            <form method="GET" class="filter-bar row g-2 align-items-end mb-3">
                <div class="col-6 col-md-3">
                    <label class="form-label">بحث</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\Tax::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
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
                        <tr>
                            <th>الاسم</th>
                            <th>النوع</th>
                            <th>المشروع</th>
                            <th>النسبة</th>
                            <th>المبلغ</th>
                            <th>تاريخ الاستحقاق</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($taxes as $tax)
                            @php($badge = match($tax->status) {
                                'paid' => 'success', 'cancelled' => 'danger', default => 'secondary' })
                            <tr>
                                <td class="fw-semibold">{{ $tax->name }}</td>
                                <td><span class="badge text-bg-info">{{ \App\Models\Tax::TYPES[$tax->tax_type] ?? $tax->tax_type }}</span></td>
                                <td>{{ $tax->project?->name ?? '—' }}</td>
                                <td>{{ number_format($tax->rate, 2) }}%</td>
                                <td>{{ number_format($tax->amount, 2) }} ج</td>
                                <td>{{ $tax->due_date?->format('Y-m-d') ?? '—' }}</td>
                                <td><span class="badge text-bg-{{ $badge }}">{{ \App\Models\Tax::STATUSES[$tax->status] ?? $tax->status }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('taxes.show', $tax) }}" class="btn btn-sm btn-outline-secondary" title="عرض"><i class="fa-solid fa-eye"></i></a>
                                    @can('taxes.edit')
                                        <a href="{{ route('taxes.edit', $tax) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('taxes.delete')
                                        <form method="POST" action="{{ route('taxes.destroy', $tax) }}" class="d-inline"
                                              data-confirm="متأكد من حذف الضريبة؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">لا توجد ضرائب بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $taxes->links() }}
        </div>
    </div>
@endsection
