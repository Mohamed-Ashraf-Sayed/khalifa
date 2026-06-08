@extends('layouts.app')

@section('title', 'طباعة مسيّر رواتب: ' . $run->run_number)

@section('content')
    <style>
        @media print {
            .no-print { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            nav, .navbar, .sidebar, footer { display: none !important; }
            body { background: #fff !important; }
        }
        @page { size: A4 landscape; margin: 12mm; }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="m-0">طباعة مسيّر رواتب #{{ $run->run_number }}</h5>
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
                    @if ($phone = \App\Models\Setting::get('company_phone'))<div class="text-muted small">هاتف: {{ $phone }}</div>@endif
                </div>
                <div class="text-start">
                    <h4 class="m-0">مسيّر رواتب</h4>
                    <div class="fw-bold">#{{ $run->run_number }}</div>
                    <div class="text-muted small">{{ \App\Models\PayrollRun::MONTHS[$run->period_month] ?? $run->period_month }} {{ $run->period_year }}</div>
                    <span class="badge text-bg-secondary">{{ \App\Models\PayrollRun::STATUSES[$run->status] ?? $run->status }}</span>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div>الحساب البنكي: <strong>{{ $run->bankAccount?->name ?? '—' }}</strong></div>
                    <div>اعتمده: <strong>{{ $run->approver?->name ?? '—' }}</strong></div>
                </div>
                <div class="col-6 text-start">
                    <div>تاريخ الاعتماد: <strong>{{ $run->approved_at?->format('Y-m-d') ?? '—' }}</strong></div>
                    <div>تاريخ الصرف: <strong>{{ $run->paid_at?->format('Y-m-d') ?? '—' }}</strong></div>
                    <div class="text-muted small">تاريخ الطباعة: {{ now()->format('Y-m-d') }}</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>الموظف</th>
                            <th class="text-end">الأساسي</th>
                            <th class="text-end">البدلات</th>
                            <th class="text-end">المكافأة</th>
                            <th class="text-end">الخصومات</th>
                            <th class="text-end">خصم السلفة</th>
                            <th class="text-end">الصافي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($run->items as $i => $item)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td class="fw-semibold">{{ $item->employee?->name ?? '—' }}</td>
                                <td class="text-end">{{ number_format((float) $item->basic_salary, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $item->allowances, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $item->bonus, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $item->deductions, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $item->advance_deduction, 2) }}</td>
                                <td class="text-end fw-semibold">{{ number_format((float) $item->net_salary, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-3">لا توجد بنود.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="7" class="text-end">إجمالي الصافي</td>
                            <td class="text-end text-success">{{ number_format((float) $run->total_net, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if ($run->notes)
                <div class="border-top pt-3 mt-2">
                    <div class="text-muted small">ملاحظات</div>
                    <div>{{ $run->notes }}</div>
                </div>
            @endif

            <div class="row mt-5 pt-4">
                <div class="col-4 text-center">المحاسب<br><br>____________</div>
                <div class="col-4 text-center">المدير المالي<br><br>____________</div>
                <div class="col-4 text-center">المدير العام<br><br>____________</div>
            </div>
        </div>
    </div>
@endsection
