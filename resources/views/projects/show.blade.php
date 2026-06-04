@extends('layouts.app')

@section('title', 'بيانات المشروع')

@section('content')
    @php($badge = match($project->status) {
        'completed' => 'success', 'in_progress' => 'primary',
        'on_hold' => 'warning', 'cancelled' => 'danger', default => 'secondary' })
    @php($accent = '#8b7355')

    {{-- 1. الهيدر --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h5 class="m-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-diagram-project" style="color: {{ $accent }}"></i>
            {{ $project->name }}
            <span class="badge text-bg-{{ $badge }}">{{ \App\Models\Project::STATUSES[$project->status] ?? $project->status }}</span>
        </h5>
        <div class="d-flex flex-wrap gap-2">
            @can('projects.edit')
                <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
            @endcan
            <a href="{{ route('general_ledger.project', $project) }}" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-book ms-1"></i> دفتر أستاذ المشروع</a>
            <a href="{{ route('reports.project_income', ['project_id' => $project->id]) }}" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-file-invoice-dollar ms-1"></i> قائمة دخل المشروع</a>
            <a href="{{ route('projects.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    {{-- 2. بطاقات المؤشّرات --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small"><i class="fa-solid fa-file-signature ms-1"></i> قيمة العقد</div>
                    <div class="fs-5 fw-bold">{{ number_format((float) $summary['contractValue'], 2) }} <span class="small text-muted">ج</span></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small"><i class="fa-solid fa-arrow-trend-up ms-1"></i> الإيرادات</div>
                    <div class="fs-5 fw-bold text-success">{{ number_format((float) $summary['revenue'], 2) }} <span class="small text-muted">ج</span></div>
                    <div class="small text-muted">المحصّل: {{ number_format((float) $summary['collected'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small"><i class="fa-solid fa-money-bill-trend-up ms-1"></i> التكاليف الفعلية</div>
                    <div class="fs-5 fw-bold text-danger">{{ number_format((float) $summary['actualCost'], 2) }} <span class="small text-muted">ج</span></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small"><i class="fa-solid fa-sack-dollar ms-1"></i> صافي الربح</div>
                    <div class="fs-5 fw-bold {{ bccomp((string) $summary['profit'], '0', 2) >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format((float) $summary['profit'], 2) }} <span class="small text-muted">ج</span>
                    </div>
                    <div class="small text-muted">الهامش: {{ number_format($summary['margin'], 2) }}%</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small"><i class="fa-solid fa-gauge-high ms-1"></i> نسبة استهلاك الموازنة</div>
                    @php($pct = $summary['costVsContract'])
                    @php($barColor = $pct > 100 ? 'bg-danger' : ($pct > 80 ? 'bg-warning' : 'bg-success'))
                    <div class="fw-bold mb-1">{{ number_format($pct, 1) }}%</div>
                    <div class="progress" style="height: 10px;" role="progressbar" aria-valuenow="{{ min($pct, 100) }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar {{ $barColor }}" style="width: {{ min(max($pct, 0), 100) }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. بطاقة بيانات المشروع --}}
    <div class="card mb-3">
        <div class="card-body">
            <h6 class="mb-3"><i class="fa-solid fa-circle-info ms-1"></i> بيانات المشروع</h6>
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">العميل</div><div class="fw-semibold">{{ $project->client?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">مدير المشروع</div><div>{{ $project->manager?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">النوع</div><div>{{ \App\Models\Project::TYPES[$project->project_type] ?? $project->project_type }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ البداية</div><div>{{ $project->start_date?->format('Y-m-d') ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ النهاية</div><div>{{ $project->end_date?->format('Y-m-d') ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ النهاية الفعلي</div><div>{{ $project->actual_end_date?->format('Y-m-d') ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الموقع</div><div>{{ $project->location ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">أنشئ بواسطة</div><div>{{ $project->creator?->name ?? '—' }}</div></div>
                @if ($project->description)<div class="col-12"><div class="text-muted small">الوصف</div><div>{{ $project->description }}</div></div>@endif
                @if ($project->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $project->notes }}</div></div>@endif
            </div>
        </div>
    </div>

    {{-- 4. الأقسام المرتبطة --}}

    {{-- الفواتير --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="m-0"><i class="fa-solid fa-file-invoice ms-1"></i> الفواتير <span class="badge text-bg-secondary">{{ $project->invoices->count() }}</span></h6>
                <a href="{{ route('invoices.index') }}" class="small text-decoration-none">عرض الكل</a>
            </div>
            <div class="small text-muted mb-2">إجمالي مُفوتر: {{ number_format((float) $summary['invoicedTotal'], 2) }} ج · مدفوع: {{ number_format((float) $summary['invoicePaid'], 2) }} ج</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>رقم الفاتورة</th><th>الإجمالي</th><th>المدفوع</th><th>الحالة</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($project->invoices as $invoice)
                            <tr>
                                <td>{{ $invoice->invoice_number }}</td>
                                <td>{{ number_format((float) $invoice->total_amount, 2) }}</td>
                                <td>{{ number_format((float) $invoice->paid_amount, 2) }}</td>
                                <td><span class="badge text-bg-light">{{ \App\Models\Invoice::STATUSES[$invoice->status] ?? $invoice->status }}</span></td>
                                <td class="text-end"><a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-eye"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">لا توجد فواتير.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- المستخلصات --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="m-0"><i class="fa-solid fa-file-contract ms-1"></i> مستخلصات المقاولين <span class="badge text-bg-secondary">{{ $project->contractorExtracts->count() }}</span></h6>
                <a href="{{ route('contractor_extracts.index') }}" class="small text-decoration-none">عرض الكل</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>رقم المستخلص</th><th>المقاول</th><th>الصافي</th><th>الحالة</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($project->contractorExtracts as $extract)
                            <tr>
                                <td>{{ $extract->extract_number }}</td>
                                <td>{{ $extract->contractor?->name ?? '—' }}</td>
                                <td>{{ number_format((float) $extract->net_amount, 2) }}</td>
                                <td><span class="badge text-bg-light">{{ \App\Models\ContractorExtract::STATUSES[$extract->status] ?? $extract->status }}</span></td>
                                <td class="text-end"><a href="{{ route('contractor_extracts.show', $extract) }}" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-eye"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">لا توجد مستخلصات.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- أوامر الشراء --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="m-0"><i class="fa-solid fa-cart-shopping ms-1"></i> أوامر الشراء <span class="badge text-bg-secondary">{{ $project->purchaseOrders->count() }}</span></h6>
                <a href="{{ route('purchase_orders.index') }}" class="small text-decoration-none">عرض الكل</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>رقم الأمر</th><th>المورّد</th><th>الصافي</th><th>الحالة</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($project->purchaseOrders as $po)
                            <tr>
                                <td>{{ $po->order_number }}</td>
                                <td>{{ $po->supplier?->name ?? '—' }}</td>
                                <td>{{ number_format((float) $po->net_amount, 2) }}</td>
                                <td><span class="badge text-bg-light">{{ \App\Models\PurchaseOrder::STATUSES[$po->status] ?? $po->status }}</span></td>
                                <td class="text-end"><a href="{{ route('purchase_orders.show', $po) }}" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-eye"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">لا توجد أوامر شراء.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- مشتريات الموردين --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="m-0"><i class="fa-solid fa-truck-field ms-1"></i> مشتريات الموردين <span class="badge text-bg-secondary">{{ $project->supplierTransactions->count() }}</span></h6>
                <a href="{{ route('supplier_transactions.index') }}" class="small text-decoration-none">عرض الكل</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>الصنف</th><th>المورّد</th><th>الصافي</th><th>التاريخ</th></tr></thead>
                    <tbody>
                        @forelse ($project->supplierTransactions as $txn)
                            <tr>
                                <td>{{ $txn->item_description }}</td>
                                <td>{{ $txn->supplier?->name ?? '—' }}</td>
                                <td>{{ number_format((float) $txn->net_amount, 2) }}</td>
                                <td>{{ $txn->transaction_date?->format('Y-m-d') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">لا توجد مشتريات موردين.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- بنود التكلفة --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="m-0"><i class="fa-solid fa-list-check ms-1"></i> بنود التكلفة <span class="badge text-bg-secondary">{{ $project->projectCosts->count() }}</span></h6>
                <a href="{{ route('project_costs.index') }}" class="small text-decoration-none">عرض الكل</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>بند العمل</th><th>المقاول/المورّد</th><th>القيمة</th></tr></thead>
                    <tbody>
                        @forelse ($project->projectCosts as $cost)
                            <tr>
                                <td>{{ $cost->work_item }}</td>
                                <td>{{ $cost->contractor_supplier ?: '—' }}</td>
                                <td>{{ number_format((float) $cost->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">لا توجد بنود تكلفة.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- الإيرادات والمصروفات --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="m-0"><i class="fa-solid fa-coins ms-1"></i> الإيرادات <span class="badge text-bg-secondary">{{ $project->revenues->count() }}</span></h6>
                        <a href="{{ route('revenues.index') }}" class="small text-decoration-none">عرض الكل</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th>البيان</th><th>القيمة</th><th>المحصّل</th></tr></thead>
                            <tbody>
                                @forelse ($project->revenues as $rev)
                                    <tr>
                                        <td>{{ \Illuminate\Support\Str::limit($rev->description, 30) ?: '—' }}</td>
                                        <td>{{ number_format((float) $rev->amount, 2) }}</td>
                                        <td>{{ number_format((float) $rev->paid_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">لا توجد إيرادات.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="m-0"><i class="fa-solid fa-receipt ms-1"></i> المصروفات <span class="badge text-bg-secondary">{{ $project->expenses->count() }}</span></h6>
                        <a href="{{ route('expenses.index') }}" class="small text-decoration-none">عرض الكل</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th>البيان</th><th>التصنيف</th><th>القيمة</th></tr></thead>
                            <tbody>
                                @forelse ($project->expenses as $exp)
                                    <tr>
                                        <td>{{ \Illuminate\Support\Str::limit($exp->description, 25) ?: '—' }}</td>
                                        <td>{{ \App\Models\Expense::CATEGORIES[$exp->category] ?? $exp->category }}</td>
                                        <td>{{ number_format((float) $exp->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">لا توجد مصروفات.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- العقود --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="m-0"><i class="fa-solid fa-file-signature ms-1"></i> العقود <span class="badge text-bg-secondary">{{ $project->contracts->count() }}</span></h6>
                <a href="{{ route('contracts.index') }}" class="small text-decoration-none">عرض الكل</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>رقم العقد</th><th>القيمة</th><th>الحالة</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($project->contracts as $contract)
                            <tr>
                                <td>{{ $contract->contract_number }}</td>
                                <td>{{ number_format((float) $contract->contract_value, 2) }}</td>
                                <td><span class="badge text-bg-light">{{ \App\Models\ProjectContract::STATUSES[$contract->status] ?? $contract->status }}</span></td>
                                <td class="text-end"><a href="{{ route('contracts.show', $contract) }}" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-eye"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">لا توجد عقود.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- فريق المشروع (موجود — يُحافَظ عليه كما هو) --}}
    <div class="card mb-3">
        <div class="card-body">
            <h6 class="mb-3"><i class="fa-solid fa-users-gear ms-1"></i> فريق المشروع</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>الموظف</th><th>الدور</th><th>تاريخ البداية</th><th>تاريخ النهاية</th><th class="text-end">إجراءات</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($project->assignedEmployees as $employee)
                            <tr>
                                <td>{{ $employee->name }}</td>
                                <td>{{ $employee->pivot->role ?: '—' }}</td>
                                <td>{{ $employee->pivot->start_date ? \Illuminate\Support\Carbon::parse($employee->pivot->start_date)->format('Y-m-d') : '—' }}</td>
                                <td>{{ $employee->pivot->end_date ? \Illuminate\Support\Carbon::parse($employee->pivot->end_date)->format('Y-m-d') : '—' }}</td>
                                <td class="text-end">
                                    @can('projects.edit')
                                        <form method="POST" action="{{ route('projectEmployees.destroy', $employee->pivot->id) }}" class="d-inline" onsubmit="return confirm('حذف الموظف من المشروع؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">لا يوجد موظفون معيّنون بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @can('projects.edit')
                <form method="POST" action="{{ route('projectEmployees.store', $project) }}" class="row g-2 mt-2 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label small">الموظف <span class="text-danger">*</span></label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">— اختر —</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}" @selected((int) old('employee_id') === $emp->id)>{{ $emp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">الدور</label>
                        <input type="text" name="role" value="{{ old('role') }}" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">تاريخ البداية</label>
                        <input type="date" name="start_date" value="{{ old('start_date') }}" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">تاريخ النهاية</label>
                        <input type="date" name="end_date" value="{{ old('end_date') }}" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <button class="btn w-100" style="background:#8b7355;color:#fff"><i class="fa-solid fa-plus"></i></button>
                    </div>
                </form>
            @endcan
        </div>
    </div>

    {{-- المواد المستهلكة (موجود — يُحافَظ عليه كما هو) --}}
    <div class="card mb-3">
        <div class="card-body">
            <h6 class="mb-3"><i class="fa-solid fa-boxes-stacked ms-1"></i> المواد المستهلكة</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>المادة</th><th>الكمية</th><th>الوحدة</th><th>سعر الوحدة</th><th>القيمة</th><th>التاريخ</th><th class="text-end">إجراءات</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($project->materialConsumptions as $consumption)
                            <tr>
                                <td>{{ $consumption->material?->name ?? '—' }}</td>
                                <td>{{ rtrim(rtrim(number_format($consumption->quantity, 3), '0'), '.') }}</td>
                                <td>{{ $consumption->unit ?: '—' }}</td>
                                <td>{{ number_format($consumption->unit_price, 2) }}</td>
                                <td class="fw-semibold">{{ number_format($consumption->total_value, 2) }}</td>
                                <td>{{ $consumption->consumption_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="text-end">
                                    @can('projects.edit')
                                        <form method="POST" action="{{ route('projectMaterialConsumptions.destroy', $consumption) }}" class="d-inline" onsubmit="return confirm('حذف سجل الاستهلاك؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">لا توجد مواد مستهلكة بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @can('projects.edit')
                <form method="POST" action="{{ route('projectMaterialConsumptions.store', $project) }}" class="row g-2 mt-2 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label small">المادة <span class="text-danger">*</span></label>
                        <select name="material_id" class="form-select" required>
                            <option value="">— اختر —</option>
                            @foreach ($materials as $mat)
                                <option value="{{ $mat->id }}" @selected((int) old('material_id') === $mat->id)>{{ $mat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">الكمية <span class="text-danger">*</span></label>
                        <input type="number" step="0.001" min="0" name="quantity" value="{{ old('quantity') }}" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">الوحدة</label>
                        <input type="text" name="unit" value="{{ old('unit') }}" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">سعر الوحدة <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="unit_price" value="{{ old('unit_price') }}" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">التاريخ <span class="text-danger">*</span></label>
                        <input type="date" name="consumption_date" value="{{ old('consumption_date', now()->toDateString()) }}" class="form-control" required>
                    </div>
                    <div class="col-md-1">
                        <button class="btn w-100" style="background:#8b7355;color:#fff"><i class="fa-solid fa-plus"></i></button>
                    </div>
                </form>
            @endcan
        </div>
    </div>

    {{-- الملفات --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="m-0"><i class="fa-solid fa-folder-open ms-1"></i> ملفات المشروع <span class="badge text-bg-secondary">{{ $project->files->count() }}</span></h6>
                <a href="{{ route('project_files.index', ['project_id' => $project->id]) }}" class="small text-decoration-none">عرض الكل</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>اسم الملف</th><th>الوصف</th><th>الحجم</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($project->files as $file)
                            <tr>
                                <td><i class="fa-solid fa-file ms-1 text-muted"></i> {{ $file->original_name }}</td>
                                <td>{{ $file->description ?: '—' }}</td>
                                <td>{{ number_format($file->size / 1024, 1) }} ك.ب</td>
                                <td class="text-end"><a href="{{ route('project_files.download', $file) }}" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-download"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">لا توجد ملفات مرفوعة.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- الشركاء المرتبطون --}}
    @if ($project->partners->isNotEmpty())
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="m-0"><i class="fa-solid fa-handshake ms-1"></i> الشركاء المرتبطون <span class="badge text-bg-secondary">{{ $project->partners->count() }}</span></h6>
                    <a href="{{ route('partners.index') }}" class="small text-decoration-none">عرض الكل</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>الاسم</th><th>الحالة</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($project->partners as $partner)
                                <tr>
                                    <td>{{ $partner->name }}</td>
                                    <td><span class="badge text-bg-light">{{ \App\Models\Partner::STATUSES[$partner->status] ?? $partner->status }}</span></td>
                                    <td class="text-end"><a href="{{ route('partners.show', $partner) }}" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-eye"></i></a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
