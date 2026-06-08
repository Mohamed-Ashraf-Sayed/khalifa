@extends('layouts.app')

@section('title', 'مسيّر رواتب: ' . $run->run_number)

@section('content')
    @php($badge = match($run->status) { 'draft'=>'secondary','approved'=>'info','paid'=>'success', default=>'secondary' })

    <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
        @can('employees.edit')
            @if ($run->status === 'draft')
                <form method="POST" action="{{ route('payroll.approve', $run) }}" class="d-inline" data-confirm="تأكيد اعتماد المسيّر؟">
                    @csrf
                    <button class="btn btn-sm btn-outline-info"><i class="fa-solid fa-circle-check ms-1"></i> اعتماد</button>
                </form>
            @endif
        @endcan
        @can('employees.delete')
            @if ($run->status === 'draft')
                <form method="POST" action="{{ route('payroll.destroy', $run) }}" class="d-inline" data-confirm="متأكد من حذف المسيّر؟">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash ms-1"></i> حذف</button>
                </form>
            @endif
        @endcan
        <a href="{{ route('payroll.print', $run) }}" class="btn btn-sm" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة المسيّر</a>
        <a href="{{ route('payroll.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
                <span class="entity-avatar"><i class="fa-solid fa-money-check-dollar"></i></span>
                <div class="flex-grow-1">
                    <div class="h5 mb-1">{{ $run->run_number }}</div>
                    <div class="text-muted small">{{ \App\Models\PayrollRun::MONTHS[$run->period_month] ?? $run->period_month }} {{ $run->period_year }}</div>
                    <div class="mt-2">
                        <span class="badge text-bg-{{ $badge }}">{{ \App\Models\PayrollRun::STATUSES[$run->status] ?? $run->status }}</span>
                    </div>
                </div>
            </div></div>
        </div>
        <div class="col-lg-7">
            <div class="row g-3 h-100">
                <div class="col-6"><div class="stat-box accent"><div class="sl">إجمالي الصافي</div><div class="sv text-success">{{ number_format((float) $run->total_net, 2) }}</div></div></div>
                <div class="col-6"><div class="stat-box"><div class="sl">عدد البنود</div><div class="sv">{{ number_format($run->items->count()) }}</div></div></div>
            </div>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <h6 class="mb-3"><i class="fa-solid fa-circle-info ms-1" style="color:#2b4c80"></i> بيانات المسيّر</h6>
        <div class="info-list">
            <div class="il"><span class="k">الحالة</span><span class="v">{{ \App\Models\PayrollRun::STATUSES[$run->status] ?? $run->status }}</span></div>
            <div class="il"><span class="k">الحساب البنكي</span><span class="v">{{ $run->bankAccount?->name ?? '—' }}</span></div>
            <div class="il"><span class="k">اعتمده</span><span class="v">{{ $run->approver?->name ?? '—' }}</span></div>
            <div class="il"><span class="k">تاريخ الاعتماد</span><span class="v">{{ $run->approved_at?->format('Y-m-d H:i') ?? '—' }}</span></div>
            <div class="il"><span class="k">تاريخ الصرف</span><span class="v">{{ $run->paid_at?->format('Y-m-d H:i') ?? '—' }}</span></div>
            <div class="il"><span class="k">أُنشئ بواسطة</span><span class="v">{{ $run->creator?->name ?? '—' }}</span></div>
            @if ($run->notes)<div class="il" style="grid-column:1/-1"><span class="k">ملاحظات</span><span class="v">{{ $run->notes }}</span></div>@endif
        </div>
    </div></div>

    @can('employees.edit')
        @if ($run->status === 'approved')
            <div class="card mb-3"><div class="card-body">
                <h6 class="mb-3"><i class="fa-solid fa-sack-dollar ms-1" style="color:#2b4c80"></i> صرف الرواتب</h6>
                <form method="POST" action="{{ route('payroll.pay', $run) }}" class="row g-3 align-items-end" data-confirm="تأكيد صرف الرواتب من الحساب المختار؟">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label">الحساب البنكي <span class="text-danger">*</span></label>
                        <select name="bank_account_id" class="form-select" required>
                            <option value="">— اختر الحساب —</option>
                            @foreach ($bankAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }} ({{ number_format((float) $account->current_balance, 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-money-bill-transfer ms-1"></i> صرف الرواتب</button>
                    </div>
                </form>
            </div></div>
        @endif
    @endcan

    <div class="card mb-3"><div class="card-body">
        <h6 class="mb-3"><i class="fa-solid fa-users ms-1" style="color:#2b4c80"></i> بنود الرواتب</h6>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>الموظف</th>
                        <th>الأساسي</th>
                        <th>البدلات</th>
                        <th>المكافأة</th>
                        <th>الخصومات</th>
                        <th>خصم السلفة</th>
                        <th>الصافي</th>
                        <th>الحالة</th>
                        @if ($run->status === 'draft')
                            @can('employees.edit')<th class="text-end">تعديل</th>@endcan
                        @else
                            <th class="text-end">قسيمة</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($run->items as $item)
                        @if ($run->status === 'draft')
                            <tr>
                                <td class="fw-semibold">{{ $item->employee?->name ?? '—' }}</td>
                                <td>{{ number_format((float) $item->basic_salary, 2) }}</td>
                                <td><input form="item-form-{{ $item->id }}" type="number" step="0.01" min="0" name="allowances" value="{{ number_format((float) $item->allowances, 2, '.', '') }}" class="form-control form-control-sm" style="min-width:90px"></td>
                                <td><input form="item-form-{{ $item->id }}" type="number" step="0.01" min="0" name="bonus" value="{{ number_format((float) $item->bonus, 2, '.', '') }}" class="form-control form-control-sm" style="min-width:90px"></td>
                                <td><input form="item-form-{{ $item->id }}" type="number" step="0.01" min="0" name="deductions" value="{{ number_format((float) $item->deductions, 2, '.', '') }}" class="form-control form-control-sm" style="min-width:90px"></td>
                                <td><input form="item-form-{{ $item->id }}" type="number" step="0.01" min="0" name="advance_deduction" value="{{ number_format((float) $item->advance_deduction, 2, '.', '') }}" class="form-control form-control-sm" style="min-width:90px"></td>
                                <td class="fw-semibold">{{ number_format((float) $item->net_salary, 2) }}</td>
                                <td><span class="badge text-bg-secondary">غير مدفوع</span></td>
                                @can('employees.edit')
                                    <td class="text-end">
                                        <form id="item-form-{{ $item->id }}" method="POST" action="{{ route('payroll.update_item', [$run, $item]) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-primary" title="حفظ"><i class="fa-solid fa-floppy-disk"></i></button>
                                        </form>
                                    </td>
                                @endcan
                            </tr>
                        @else
                            <tr>
                                <td class="fw-semibold">{{ $item->employee?->name ?? '—' }}</td>
                                <td>{{ number_format((float) $item->basic_salary, 2) }}</td>
                                <td>{{ number_format((float) $item->allowances, 2) }}</td>
                                <td>{{ number_format((float) $item->bonus, 2) }}</td>
                                <td>{{ number_format((float) $item->deductions, 2) }}</td>
                                <td>{{ number_format((float) $item->advance_deduction, 2) }}</td>
                                <td class="fw-semibold">{{ number_format((float) $item->net_salary, 2) }}</td>
                                <td><span class="badge text-bg-{{ $item->paid ? 'success' : 'secondary' }}">{{ $item->paid ? 'مدفوع' : 'غير مدفوع' }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('payroll.payslip', [$run, $item]) }}" class="btn btn-sm btn-outline-secondary" title="قسيمة الراتب"><i class="fa-solid fa-print"></i></a>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">لا توجد بنود.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div></div>
@endsection
