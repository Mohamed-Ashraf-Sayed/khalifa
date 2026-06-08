@extends('layouts.app')

@section('title', 'الضرائب')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2" method="GET">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بحث باسم الضريبة">
                    <select name="status" class="form-select" style="min-width:160px" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\Tax::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                @can('taxes.create')
                    <a href="{{ route('taxes.create') }}" class="btn" style="background:#8b7355;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> ضريبة جديدة
                    </a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>الاسم</th>
                            <th>النوع</th>
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
                            <tr><td colspan="6" class="text-center text-muted py-4">لا توجد ضرائب بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $taxes->links() }}
        </div>
    </div>
@endsection
