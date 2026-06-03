@extends('layouts.app')

@section('title', 'مستخلص ' . $extract->extract_number)

@section('content')
    @php($badge = match($extract->status) {
        'paid' => 'success', 'approved' => 'primary',
        'partial' => 'info', 'cancelled' => 'danger', default => 'secondary' })

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">مستخلص رقم {{ $extract->extract_number }}</h5>
        <div class="d-flex gap-2">
            @can('contractors.edit')
                @if ($extract->status === 'pending')
                    <form method="POST" action="{{ route('contractor_extracts.approve', $extract) }}" class="d-inline" onsubmit="return confirm('اعتماد المستخلص؟')">
                        @csrf
                        <button class="btn btn-sm btn-success"><i class="fa-solid fa-check ms-1"></i> اعتماد</button>
                    </form>
                @endif
                <a href="{{ route('contractor_extracts.edit', $extract) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
            @endcan
            <a href="{{ route('contractor_extracts.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-7">
            <div class="card h-100"><div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><div class="text-muted small">المقاول</div><div class="fw-semibold">{{ $extract->contractor?->name ?? '—' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">المشروع</div><div>{{ $extract->project?->name ?? 'عام' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">التاريخ</div><div>{{ optional($extract->extract_date)->format('Y-m-d') ?: '—' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">الحالة</div><div><span class="badge text-bg-{{ $badge }}">{{ \App\Models\ContractorExtract::STATUSES[$extract->status] ?? $extract->status }}</span></div></div>
                    <div class="col-md-6"><div class="text-muted small">نسبة التنفيذ</div><div>{{ rtrim(rtrim(number_format($extract->execution_percent, 2), '0'), '.') }}%</div></div>
                    @if ($extract->approver)<div class="col-md-6"><div class="text-muted small">اعتمده</div><div>{{ $extract->approver->name }} · {{ $extract->approved_at?->format('Y-m-d') }}</div></div>@endif
                    @if ($extract->description)<div class="col-12"><div class="text-muted small">الوصف</div><div>{{ $extract->description }}</div></div>@endif
                    @if ($extract->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $extract->notes }}</div></div>@endif
                </div>
            </div></div>
        </div>
        <div class="col-md-5">
            <div class="card h-100"><div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted">إجمالي البنود</td><td class="text-end fw-semibold">{{ number_format($extract->total_amount, 2) }}</td></tr>
                    <tr><td class="text-muted">الإضافات</td><td class="text-end">+ {{ number_format($extract->additions, 2) }}</td></tr>
                    <tr><td class="text-muted">الخصومات</td><td class="text-end text-danger">− {{ number_format($extract->deductions, 2) }}</td></tr>
                    <tr class="border-top"><td class="fw-bold">الصافي</td><td class="text-end fw-bold fs-5 text-success">{{ number_format($extract->net_amount, 2) }}</td></tr>
                    <tr><td class="text-muted">المدفوع</td><td class="text-end">{{ number_format($extract->paid_amount, 2) }}</td></tr>
                    <tr><td class="text-muted">المتبقّي</td><td class="text-end fw-semibold text-warning">{{ number_format($extract->remaining(), 2) }}</td></tr>
                    <tr>
                        <td class="text-muted">المحتجز ({{ rtrim(rtrim(number_format($extract->retention_percent, 2), '0'), '.') }}%)</td>
                        <td class="text-end">
                            {{ number_format($extract->retention_amount, 2) }}
                            @if ($extract->retention_released)
                                <span class="badge text-bg-success ms-1">مُحرَّر</span>
                            @endif
                        </td>
                    </tr>
                </table>
                @can('contractors.edit')
                    @if (bccomp((string) $extract->retention_amount, '0', 2) > 0 && ! $extract->retention_released)
                        <form method="POST" action="{{ route('contractor_extracts.release_retention', $extract) }}" onsubmit="return confirm('تحرير المبلغ المحتجز؟')">
                            @csrf
                            <button class="btn btn-sm w-100 mt-2" style="background:#8b7355;color:#fff"><i class="fa-solid fa-unlock ms-1"></i> تحرير المحتجز</button>
                        </form>
                    @endif
                @endcan
            </div></div>
        </div>
    </div>

    @can('contractors.edit')
    <div class="card mb-3"><div class="card-body">
        <form method="POST" action="{{ route('contractor_extract_items.store', $extract) }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-4"><label class="form-label small">بند الأعمال</label><input type="text" name="description" class="form-control" required></div>
            <div class="col-md-2"><label class="form-label small">الوحدة</label><input type="text" name="unit" class="form-control" placeholder="م2/م3/طن.."></div>
            <div class="col-md-2"><label class="form-label small">الكمية</label><input type="number" step="0.001" min="0.001" name="quantity" value="1" class="form-control" required></div>
            <div class="col-md-2"><label class="form-label small">سعر الوحدة</label><input type="number" step="0.01" min="0" name="unit_price" class="form-control" required></div>
            <div class="col-md-2"><button class="btn w-100" style="background:#8b7355;color:#fff">إضافة بند</button></div>
        </form>
    </div></div>
    @endcan

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">بنود الأعمال</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light"><tr><th>البند</th><th>الوحدة</th><th>الكمية</th><th>سعر الوحدة</th><th>الإجمالي</th><th class="text-end"></th></tr></thead>
                    <tbody>
                        @forelse ($extract->items as $item)
                            <tr>
                                <td>{{ $item->description }}</td>
                                <td>{{ $item->unit ?? '—' }}</td>
                                <td>{{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</td>
                                <td>{{ number_format($item->unit_price, 2) }}</td>
                                <td class="fw-semibold">{{ number_format($item->total_price, 2) }}</td>
                                <td class="text-end">
                                    @can('contractors.edit')
                                        <form method="POST" action="{{ route('contractor_extract_items.destroy', $item) }}" class="d-inline" onsubmit="return confirm('حذف البند؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">لا توجد بنود. أضِف بنداً من الأعلى.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('partials.attachments', ['model' => $extract])
@endsection
