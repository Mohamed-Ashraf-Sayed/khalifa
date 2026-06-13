@extends('layouts.app')

@section('title', 'تقرير مراكز التكلفة')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h5 class="mb-0">تقرير مراكز التكلفة (المصروفات والإيرادات)</h5>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
            <a href="{{ route('cost_centers.index') }}" class="btn btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4 col-12"><div class="card h-100"><div class="card-body py-3"><i class="fa-solid fa-arrow-trend-up text-success"></i><div class="fs-4 fw-bold">{{ number_format((float) $totalRevenues, 2) }}</div><div class="small text-muted">إجمالي الإيرادات</div></div></div></div>
        <div class="col-md-4 col-6"><div class="card h-100"><div class="card-body py-3"><i class="fa-solid fa-arrow-trend-down text-danger"></i><div class="fs-4 fw-bold">{{ number_format((float) $totalExpenses, 2) }}</div><div class="small text-muted">إجمالي المصروفات</div></div></div></div>
        <div class="col-md-4 col-6"><div class="card h-100"><div class="card-body py-3"><i class="fa-solid fa-equals {{ bccomp($totalNet, '0', 2) < 0 ? 'text-danger' : 'text-warning' }}"></i><div class="fs-4 fw-bold {{ bccomp($totalNet, '0', 2) < 0 ? 'text-danger' : '' }}">{{ number_format((float) $totalNet, 2) }}</div><div class="small text-muted">الصافي (الإيرادات − المصروفات)</div></div></div></div>
    </div>

    <div class="card">
        <div class="card-header bg-white fw-semibold">التفصيل حسب مركز التكلفة</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>مركز التكلفة</th>
                            <th class="text-end">الإيرادات</th>
                            <th class="text-end">المصروفات</th>
                            <th class="text-end">الصافي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td class="fw-semibold">
                                    @if ($row['center'])
                                        {{ $row['center']->name }} <span class="badge text-bg-light">{{ $row['center']->code }}</span>
                                    @else
                                        <span class="text-muted">غير محدد</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format((float) $row['revenues'], 2) }}</td>
                                <td class="text-end">{{ number_format((float) $row['expenses'], 2) }}</td>
                                <td class="text-end fw-semibold {{ bccomp($row['net'], '0', 2) < 0 ? 'text-danger' : '' }}">{{ number_format((float) $row['net'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">لا توجد بيانات.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th>الإجمالي</th>
                            <th class="text-end">{{ number_format((float) $totalRevenues, 2) }}</th>
                            <th class="text-end">{{ number_format((float) $totalExpenses, 2) }}</th>
                            <th class="text-end {{ bccomp($totalNet, '0', 2) < 0 ? 'text-danger' : '' }}">{{ number_format((float) $totalNet, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
