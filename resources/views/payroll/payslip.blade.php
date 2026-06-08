@extends('layouts.app')

@section('title', 'قسيمة راتب: ' . ($item->employee?->name ?? '—'))

@section('content')
    <style>
        @media print {
            .no-print { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            nav, .navbar, .sidebar, footer { display: none !important; }
            body { background: #fff !important; }
        }
        @page { size: A4; margin: 14mm; }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="m-0">قسيمة راتب</h5>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
            <a href="{{ route('payroll.show', $run) }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
                <div>
                    <h3 class="m-0" style="color:#2b4c80">{{ \App\Models\Setting::get('company_name', 'القروانة') }}</h3>
                    @if ($addr = \App\Models\Setting::get('company_address'))<div class="text-muted small">{{ $addr }}</div>@endif
                </div>
                <div class="text-start">
                    <h4 class="m-0">قسيمة راتب</h4>
                    <div class="text-muted small">{{ \App\Models\PayrollRun::MONTHS[$run->period_month] ?? $run->period_month }} {{ $run->period_year }}</div>
                    <div class="text-muted small">#{{ $run->run_number }}</div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div class="text-muted small">الموظف</div>
                    <div class="fw-bold">{{ $item->employee?->name ?? '—' }}</div>
                    @if ($item->employee?->job_title)<div class="text-muted small">{{ $item->employee->job_title }}</div>@endif
                </div>
                <div class="col-6 text-start">
                    <div>الكود: <strong>{{ $item->employee?->employee_code ?? '—' }}</strong></div>
                    <div>الحالة: <strong>{{ $item->paid ? 'مدفوع' : 'غير مدفوع' }}</strong></div>
                    @if ($run->paid_at)<div>تاريخ الصرف: <strong>{{ $run->paid_at->format('Y-m-d') }}</strong></div>@endif
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8">
                    <table class="table table-sm">
                        <tr><td class="text-muted">الراتب الأساسي</td><td class="text-end fw-semibold">{{ number_format((float) $item->basic_salary, 2) }}</td></tr>
                        <tr><td class="text-muted">البدلات</td><td class="text-end fw-semibold text-success">{{ number_format((float) $item->allowances, 2) }}</td></tr>
                        <tr><td class="text-muted">المكافأة</td><td class="text-end fw-semibold text-success">{{ number_format((float) $item->bonus, 2) }}</td></tr>
                        <tr><td class="text-muted">الخصومات</td><td class="text-end fw-semibold text-danger">{{ number_format((float) $item->deductions, 2) }}</td></tr>
                        <tr><td class="text-muted">خصم السلفة</td><td class="text-end fw-semibold text-danger">{{ number_format((float) $item->advance_deduction, 2) }}</td></tr>
                        <tr class="border-top"><td class="fw-bold">صافي الراتب</td><td class="text-end fw-bold fs-5 text-success">{{ number_format((float) $item->net_salary, 2) }}</td></tr>
                    </table>
                </div>
            </div>

            <div class="row mt-5 pt-4">
                <div class="col-6 text-center">توقيع المستلم<br><br>____________</div>
                <div class="col-6 text-center">المدير المالي<br><br>____________</div>
            </div>
            <div class="text-muted small text-center mt-3">تاريخ الطباعة: {{ now()->format('Y-m-d') }}</div>
        </div>
    </div>
@endsection
