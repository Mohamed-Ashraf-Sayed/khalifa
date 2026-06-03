@extends('layouts.app')

@section('title', 'تقرير المقاولين')

@section('content')
    <style>
        @media print {
            .no-print { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="m-0">تقرير المقاولين</h5>
        <div class="d-flex gap-2">
            <a href="{{ route('contractors.report', ['export' => 'csv']) }}" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-file-csv ms-1"></i> تصدير CSV</a>
            <button onclick="window.print()" class="btn btn-sm" style="background:#8b7355;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
            <a href="{{ route('contractors.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 text-center">
                <div class="col-md-4"><div class="text-muted small">إجمالي المستحقّ</div><div class="fw-bold fs-5 text-success">{{ number_format((float) $grandEarned, 2) }}</div></div>
                <div class="col-md-4"><div class="text-muted small">إجمالي المدفوع</div><div class="fw-bold fs-5 text-danger">{{ number_format((float) $grandPaid, 2) }}</div></div>
                <div class="col-md-4"><div class="text-muted small">إجمالي الرصيد المتبقّي</div><div class="fw-bold fs-5">{{ number_format((float) $grandBalance, 2) }}</div></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">المقاولون <span class="badge text-bg-light">{{ $contractors->count() }}</span></h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>المقاول</th>
                            <th class="text-end">إجمالي المستحقّ</th>
                            <th class="text-end">إجمالي المدفوع</th>
                            <th class="text-end">الرصيد المتبقّي</th>
                            <th class="text-center no-print">كشف الحساب</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($contractors as $row)
                            <tr>
                                <td>{{ $row['contractor']->name }}{{ $row['contractor']->company_name ? ' — ' . $row['contractor']->company_name : '' }}</td>
                                <td class="text-end text-success">{{ number_format((float) $row['totalEarned'], 2) }}</td>
                                <td class="text-end text-danger">{{ number_format((float) $row['totalPaid'], 2) }}</td>
                                <td class="text-end fw-semibold">{{ number_format((float) $row['balance'], 2) }}</td>
                                <td class="text-center no-print">
                                    <a href="{{ route('contractors.statement', $row['contractor']) }}" class="btn btn-sm btn-light"><i class="fa-solid fa-file-invoice"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">لا يوجد مقاولون.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td>الإجمالي</td>
                            <td class="text-end text-success">{{ number_format((float) $grandEarned, 2) }}</td>
                            <td class="text-end text-danger">{{ number_format((float) $grandPaid, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $grandBalance, 2) }}</td>
                            <td class="no-print"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
