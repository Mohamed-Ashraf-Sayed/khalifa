@extends('layouts.app')

@section('title', 'دفتر أستاذ المشروع — ' . $project->name)

@section('content')
    <style>
        @media print {
            .no-print { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="m-0">دفتر أستاذ المشروع — {{ $project->name }}</h5>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
            <a href="{{ route('general_ledger.project', ['project' => $project, 'format' => 'pdf']) }}" class="btn btn-sm btn-danger"><i class="fa-solid fa-file-pdf ms-1"></i> PDF</a>
            <a href="{{ route('general_ledger.project', ['project' => $project, 'format' => 'xlsx']) }}" class="btn btn-sm btn-success"><i class="fa-solid fa-file-excel ms-1"></i> Excel</a>
            <a href="{{ route('general_ledger.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="text-center mb-3">
                <h4 class="m-0">دفتر أستاذ المشروع</h4>
                <div class="text-muted">{{ $project->name }}</div>
            </div>
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">إجمالي الإيرادات (دائن)</div><div class="fw-bold fs-5 text-success">{{ number_format((float) $totalCredit, 2) }}</div></div>
                <div class="col-md-4"><div class="text-muted small">إجمالي التكاليف (مدين)</div><div class="fw-bold fs-5 text-danger">{{ number_format((float) $totalDebit, 2) }}</div></div>
                <div class="col-md-4"><div class="text-muted small">صافي الربح / الخسارة</div><div class="fw-bold fs-5 {{ bccomp($net, '0', 2) >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $net, 2) }}</div></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">الحركات المالية</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>التاريخ</th>
                            <th>البيان</th>
                            <th class="text-end">تكلفة (−)</th>
                            <th class="text-end">إيراد (+)</th>
                            <th class="text-end">الرصيد الصافي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ optional($row['date'])->format('Y-m-d') ?: '—' }}</td>
                                <td>{{ $row['label'] }}</td>
                                <td class="text-end text-danger">{{ bccomp($row['debit'], '0', 2) > 0 ? number_format((float) $row['debit'], 2) : '' }}</td>
                                <td class="text-end text-success">{{ bccomp($row['credit'], '0', 2) > 0 ? number_format((float) $row['credit'], 2) : '' }}</td>
                                <td class="text-end fw-semibold {{ bccomp($row['running'], '0', 2) >= 0 ? '' : 'text-danger' }}">{{ number_format((float) $row['running'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">لا توجد حركات.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="2">الإجمالي</td>
                            <td class="text-end text-danger">{{ number_format((float) $totalDebit, 2) }}</td>
                            <td class="text-end text-success">{{ number_format((float) $totalCredit, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $net, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
