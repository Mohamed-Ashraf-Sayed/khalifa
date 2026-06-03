@extends('layouts.app')

@section('title', 'كشف حساب ' . $employee->name)

@section('content')
    <style>
        @media print {
            .no-print { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="m-0">كشف حساب — {{ $employee->name }}</h5>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm" style="background:#8b7355;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
            <a href="{{ route('employees.statement', ['employee' => $employee, 'format' => 'pdf']) }}" class="btn btn-sm btn-danger"><i class="fa-solid fa-file-pdf ms-1"></i> PDF</a>
            <a href="{{ route('employees.statement', ['employee' => $employee, 'format' => 'xlsx']) }}" class="btn btn-sm btn-success"><i class="fa-solid fa-file-excel ms-1"></i> Excel</a>
            <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="text-center mb-3">
                <h4 class="m-0">كشف حساب موظف</h4>
                <div class="text-muted">{{ $employee->name }}{{ $employee->job_title ? ' — ' . $employee->job_title : '' }}</div>
            </div>
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">رصيد السلف المستحقّ</div><div class="fw-bold fs-5">{{ number_format((float) $advanceBalance, 2) }}</div></div>
                <div class="col-md-4"><div class="text-muted small">رصيد العهدة في يد الموظف</div><div class="fw-bold fs-5">{{ number_format((float) $custodyBalance, 2) }}</div></div>
                <div class="col-md-4"><div class="text-muted small">صافي النقد المُسلَّم</div><div class="fw-bold fs-5">{{ number_format((float) $netGiven, 2) }}</div></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">الحركات</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>التاريخ</th>
                            <th>النوع</th>
                            <th>البيان</th>
                            <th class="text-end">صُرف (+)</th>
                            <th class="text-end">خصم/ردّ (−)</th>
                            <th class="text-end">صافي جارٍ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-light">
                            <td colspan="5" class="fw-semibold">رصيد افتتاحي</td>
                            <td class="text-end fw-semibold">0.00</td>
                        </tr>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ optional($row['txn']->transaction_date)->format('Y-m-d') ?: '—' }}</td>
                                <td><span class="badge text-bg-light">{{ \App\Models\EmployeeTransaction::TYPES[$row['txn']->type] ?? $row['txn']->type }}</span></td>
                                <td>{{ $row['txn']->description ?: '—' }}</td>
                                <td class="text-end text-success">{{ $row['isReducer'] ? '' : number_format($row['txn']->amount, 2) }}</td>
                                <td class="text-end text-danger">{{ $row['isReducer'] ? number_format($row['txn']->amount, 2) : '' }}</td>
                                <td class="text-end fw-semibold">{{ number_format((float) $row['running'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">لا توجد حركات.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="5">صافي النقد المُسلَّم</td>
                            <td class="text-end">{{ number_format((float) $netGiven, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
