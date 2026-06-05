@extends('layouts.app')

@section('title', 'تقرير الأصول والإهلاك')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-end mb-3 no-print">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'xlsx']) }}" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-file-excel ms-1"></i> Excel</a>
        <button onclick="window.print()" class="btn btn-sm" style="background:#8b7355;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
        <a href="{{ route('assets.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">إجمالي التكلفة</div><div class="fs-4 fw-bold">{{ number_format($totals['cost'], 2) }}</div></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">مجمّع الإهلاك</div><div class="fs-4 fw-bold text-danger">{{ number_format($totals['accumulated'], 2) }}</div></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">صافي القيمة الدفترية</div><div class="fs-4 fw-bold text-success">{{ number_format($totals['book'], 2) }}</div></div></div></div>
    </div>

    <div class="card"><div class="card-body">
        <h6 class="mb-3">سجل الأصول الثابتة والإهلاك</h6>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light"><tr><th>الكود</th><th>الأصل</th><th>الفئة</th><th>تاريخ الشراء</th><th>التكلفة</th><th>مجمّع الإهلاك</th><th>القسط السنوي</th><th>القيمة الدفترية</th><th>الحالة</th></tr></thead>
                <tbody>
                    @forelse ($rows as $r)
                        <tr>
                            <td class="fw-semibold">{{ $r['code'] }}</td>
                            <td>{{ $r['name'] }}</td>
                            <td>{{ $r['category'] ?: '—' }}</td>
                            <td>{{ $r['purchase_date'] ?? '—' }}</td>
                            <td>{{ number_format($r['purchase_value'], 2) }}</td>
                            <td class="text-danger">{{ number_format($r['accumulated'], 2) }}</td>
                            <td>{{ number_format($r['annual'], 2) }}</td>
                            <td class="fw-semibold text-success">{{ number_format($r['book'], 2) }}</td>
                            <td><span class="badge text-bg-light">{{ $r['status'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-3">لا توجد أصول.</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="4">الإجمالي</td>
                        <td>{{ number_format($totals['cost'], 2) }}</td>
                        <td class="text-danger">{{ number_format($totals['accumulated'], 2) }}</td>
                        <td>—</td>
                        <td class="text-success">{{ number_format($totals['book'], 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div></div>
@endsection
