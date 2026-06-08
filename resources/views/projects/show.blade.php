@extends('layouts.app')

@section('title', 'بيانات المشروع')

@section('content')
    @php($badge = match($project->status) {
        'completed' => 'success', 'in_progress' => 'primary',
        'on_hold' => 'warning', 'cancelled' => 'danger', default => 'secondary' })
    @php($accent = '#2b4c80')

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

    {{-- مراحل/جدول المشروع --}}
    <div class="card mb-3">
        <div class="card-body">
            <h6 class="mb-3"><i class="fa-solid fa-list-ol ms-1"></i> مراحل المشروع <span class="badge text-bg-secondary">{{ $project->milestones->count() }}</span></h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>المرحلة</th><th>المخطط (بداية / نهاية)</th><th>الفعلي (بداية / نهاية)</th><th style="min-width:140px">التقدّم</th><th>الحالة</th><th class="text-end">إجراءات</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($project->milestones as $milestone)
                            @php($mBadge = match($milestone->status) { 'done' => 'success', 'in_progress' => 'primary', 'delayed' => 'danger', default => 'secondary' })
                            <tr>
                                <td class="fw-semibold">{{ $milestone->name }}</td>
                                <td class="small">{{ $milestone->planned_start?->format('Y-m-d') ?? '—' }} / {{ $milestone->planned_end?->format('Y-m-d') ?? '—' }}</td>
                                <td class="small">{{ $milestone->actual_start?->format('Y-m-d') ?? '—' }} / {{ $milestone->actual_end?->format('Y-m-d') ?? '—' }}</td>
                                <td>
                                    @php($p = (int) $milestone->progress_percent)
                                    @php($pBar = $p >= 100 ? 'bg-success' : ($p > 0 ? 'bg-primary' : 'bg-secondary'))
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 8px;"><div class="progress-bar {{ $pBar }}" style="width: {{ min(max($p, 0), 100) }}%"></div></div>
                                        <span class="small text-muted">{{ $p }}%</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge text-bg-{{ $mBadge }}">{{ \App\Models\ProjectMilestone::STATUSES[$milestone->status] ?? $milestone->status }}</span>
                                    @if ($milestone->isDelayed())<span class="small text-danger d-block">متأخرة</span>@endif
                                </td>
                                <td class="text-end">
                                    @can('projects.edit')
                                        <form method="POST" action="{{ route('project_milestones.destroy', $milestone) }}" class="d-inline" data-confirm="حذف المرحلة؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">لا توجد مراحل بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @can('projects.edit')
                <form method="POST" action="{{ route('project_milestones.store', $project) }}" class="row g-2 mt-2 align-items-end">
                    @csrf
                    <div class="col-md-3"><label class="form-label small">المرحلة <span class="text-danger">*</span></label><input type="text" name="name" value="{{ old('name') }}" class="form-control" required></div>
                    <div class="col-md-2"><label class="form-label small">بداية مخططة</label><input type="date" name="planned_start" value="{{ old('planned_start') }}" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label small">نهاية مخططة</label><input type="date" name="planned_end" value="{{ old('planned_end') }}" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label small">نسبة التقدّم %</label><input type="number" min="0" max="100" name="progress_percent" value="{{ old('progress_percent', 0) }}" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label small">الحالة <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            @foreach (\App\Models\ProjectMilestone::STATUSES as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', 'pending') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1"><button class="btn w-100" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-plus"></i></button></div>
                </form>
            @endcan
        </div>
    </div>

    {{-- المخطط الزمني (Gantt) --}}
    @php($ms = $project->milestones->whereNotNull('planned_start')->whereNotNull('planned_end'))
    @if ($ms->count())
        @php($gStart = \Illuminate\Support\Carbon::parse($ms->min('planned_start')))
        @php($gEnd = \Illuminate\Support\Carbon::parse($ms->max('planned_end')))
        @php($gSpan = max(1, $gStart->diffInDays($gEnd)))
        <div class="card mb-3"><div class="card-body">
            <h6 class="mb-3"><i class="fa-solid fa-bars-staggered ms-1" style="color:#2b4c80"></i> المخطط الزمني (Gantt)</h6>
            @foreach ($ms as $m)
                @php($s = \Illuminate\Support\Carbon::parse($m->planned_start))
                @php($e = \Illuminate\Support\Carbon::parse($m->planned_end))
                @php($offset = ($gStart->diffInDays($s) / $gSpan) * 100)
                @php($width = max(2, ($s->diffInDays($e) / $gSpan) * 100))
                @php($color = match($m->status) { 'done' => '#4f8a6b', 'in_progress' => '#2b4c80', 'delayed' => '#b65f5b', default => '#5b7bab' })
                <div class="d-flex align-items-center mb-2" style="gap:.5rem">
                    <div style="width:140px;min-width:140px;font-size:.82rem" class="text-truncate" title="{{ $m->name }}">{{ $m->name }}</div>
                    <div style="position:relative;flex:1;height:22px;background:var(--bg-2);border-radius:6px">
                        <div title="{{ $s->format('Y-m-d') }} ← {{ $e->format('Y-m-d') }}" style="position:absolute;top:0;bottom:0;right:{{ $offset }}%;width:{{ $width }}%;background:{{ $color }};border-radius:6px;min-width:6px"></div>
                    </div>
                    <div style="width:42px;font-size:.78rem" class="text-muted text-end">{{ (int) $m->progress_percent }}%</div>
                </div>
            @endforeach
            <div class="d-flex justify-content-between text-muted small mt-1"><span>{{ $gStart->format('Y-m-d') }}</span><span>{{ $gEnd->format('Y-m-d') }}</span></div>
        </div></div>
    @endif

    {{-- أوامر التغيير --}}
    @can('contracts.view')
        @php($coAdd = $project->changeOrders->where('status', 'approved')->where('change_type', 'addition')->sum('amount'))
        @php($coDed = $project->changeOrders->where('status', 'approved')->where('change_type', 'deduction')->sum('amount'))
        @php($coNetSigned = bcsub((string) $coAdd, (string) $coDed, 2))
        @php($revisedContractValue = bcadd((string) ($project->contract_value ?? '0'), $coNetSigned, 2))
        <div class="card mb-3"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0"><i class="fa-solid fa-file-pen ms-1" style="color:#2b4c80"></i> أوامر التغيير <span class="badge text-bg-secondary">{{ $project->changeOrders->count() }}</span></h6>
                @can('contracts.create')<a href="{{ route('change_orders.create') }}" class="btn btn-sm" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-plus ms-1"></i> أمر تغيير</a>@endcan
            </div>
            @forelse ($project->changeOrders as $order)
                @php($coBadge = match($order->status) { 'approved'=>'success','pending'=>'warning','rejected'=>'danger', default=>'secondary' })
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <div><a href="{{ route('change_orders.show', $order) }}" class="fw-semibold text-decoration-none">{{ $order->co_number }}</a> <span class="text-muted small">· {{ $order->title }}</span></div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge text-bg-{{ $order->change_type === 'deduction' ? 'danger' : 'success' }}">{{ \App\Models\ChangeOrder::TYPES[$order->change_type] ?? $order->change_type }}</span>
                        <span class="{{ $order->change_type === 'deduction' ? 'text-danger' : '' }}">{{ number_format((float) $order->signedAmount(), 2) }} ج</span>
                        <span class="badge text-bg-{{ $coBadge }}">{{ \App\Models\ChangeOrder::STATUSES[$order->status] ?? $order->status }}</span>
                    </div>
                </div>
            @empty
                <div class="text-muted small py-2">لا توجد أوامر تغيير لهذا المشروع.</div>
            @endforelse
            <div class="alert alert-light border mt-3 mb-0 small"><i class="fa-solid fa-scale-balanced ms-1" style="color:#2b4c80"></i> قيمة العقد المعدّلة = الأصلي ({{ number_format((float) ($project->contract_value ?? 0), 2) }}) + صافي أوامر التغيير المعتمدة ({{ number_format((float) $coNetSigned, 2) }}) = <strong>{{ number_format((float) $revisedContractValue, 2) }} ج</strong></div>
        </div></div>
    @endcan

    {{-- قائمة العيوب --}}
    @can('projects.view')
        <div class="card mb-3"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0"><i class="fa-solid fa-clipboard-check ms-1" style="color:#2b4c80"></i> قائمة العيوب</h6>
                <div class="small text-muted">مفتوحة: {{ $project->snags->where('status', '!=', 'closed')->count() }} · مغلقة: {{ $project->snags->where('status', 'closed')->count() }}</div>
            </div>
            @forelse ($project->snags->take(8) as $snag)
                @php($statusBadge = match($snag->status) { 'open'=>'warning','in_progress'=>'info','closed'=>'success', default=>'secondary' })
                @php($priorityBadge = match($snag->priority) { 'high'=>'danger','medium'=>'warning','low'=>'secondary', default=>'secondary' })
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <div><a href="{{ route('snags.show', $snag) }}" class="fw-semibold text-decoration-none">{{ $snag->title }}</a>@if ($snag->location)<span class="text-muted small ms-1">· {{ $snag->location }}</span>@endif</div>
                    <div class="d-flex gap-2">
                        <span class="badge text-bg-{{ $priorityBadge }}">{{ \App\Models\Snag::PRIORITIES[$snag->priority] ?? $snag->priority }}</span>
                        <span class="badge text-bg-{{ $statusBadge }}">{{ \App\Models\Snag::STATUSES[$snag->status] ?? $snag->status }}</span>
                    </div>
                </div>
            @empty
                <div class="text-muted small">لا توجد ملاحظات لهذا المشروع.</div>
            @endforelse
        </div></div>
    @endcan

    {{-- طلبات الاستفسار (RFI) --}}
    @if ($project->rfis->isNotEmpty())
        <div class="card mb-3"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="m-0"><i class="fa-solid fa-circle-question ms-1" style="color:#2b4c80"></i> طلبات الاستفسار (RFI) <span class="badge text-bg-secondary">{{ $project->rfis->count() }}</span></h6>
                <a href="{{ route('rfis.index', ['project_id' => $project->id]) }}" class="small text-decoration-none">عرض الكل</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>الرقم</th><th>الموضوع</th><th>موعد الرد</th><th>الحالة</th></tr></thead>
                    <tbody>
                        @foreach ($project->rfis as $rfi)
                            @php($badge = match($rfi->status) { 'open'=>'warning','answered'=>'success','closed'=>'secondary', default=>'secondary' })
                            <tr @class(['table-warning' => $rfi->isOverdue()])>
                                <td class="fw-semibold"><a href="{{ route('rfis.show', $rfi) }}">{{ $rfi->rfi_number }}</a></td>
                                <td>{{ $rfi->subject }}</td>
                                <td>{{ $rfi->due_date?->format('Y-m-d') ?? '—' }}</td>
                                <td><span class="badge text-bg-{{ $badge }}">{{ \App\Models\Rfi::STATUSES[$rfi->status] ?? $rfi->status }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div></div>
    @endif

    {{-- الاعتمادات الفنية --}}
    @if ($project->submittals->isNotEmpty())
        <div class="card mb-3"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="m-0"><i class="fa-solid fa-stamp ms-1" style="color:#2b4c80"></i> الاعتمادات الفنية <span class="badge text-bg-secondary">{{ $project->submittals->count() }}</span></h6>
                <a href="{{ route('submittals.index', ['project_id' => $project->id]) }}" class="small text-decoration-none">عرض الكل</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>الرقم</th><th>العنوان</th><th>النوع</th><th>الحالة</th><th>موعد المراجعة</th></tr></thead>
                    <tbody>
                        @foreach ($project->submittals as $submittal)
                            @php($sBadge = match($submittal->status) { 'submitted'=>'primary','under_review'=>'warning','approved'=>'success','approved_as_noted'=>'info','rejected'=>'danger', default=>'secondary' })
                            <tr>
                                <td class="fw-semibold"><a href="{{ route('submittals.show', $submittal) }}">{{ $submittal->submittal_number }}</a></td>
                                <td>{{ $submittal->title }}</td>
                                <td>{{ \App\Models\Submittal::TYPES[$submittal->type] ?? $submittal->type }}</td>
                                <td><span class="badge text-bg-{{ $sBadge }}">{{ \App\Models\Submittal::STATUSES[$submittal->status] ?? $submittal->status }}</span></td>
                                <td>{{ $submittal->due_date?->format('Y-m-d') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div></div>
    @endif

    {{-- طلبات الفحص والمعاينة --}}
    @if ($project->inspectionRequests->isNotEmpty())
        <div class="card mb-3"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="m-0"><i class="fa-solid fa-clipboard-list ms-1" style="color:#2b4c80"></i> طلبات الفحص والمعاينة <span class="badge text-bg-secondary">{{ $project->inspectionRequests->count() }}</span></h6>
                <a href="{{ route('inspection_requests.index', ['project_id' => $project->id]) }}" class="small text-decoration-none">عرض الكل</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>الرقم</th><th>العنوان</th><th>النوع</th><th>الحالة</th><th>الموعد المجدول</th></tr></thead>
                    <tbody>
                        @foreach ($project->inspectionRequests as $ir)
                            @php($irBadge = match($ir->status) { 'pending'=>'warning','approved'=>'success','rejected'=>'danger','closed'=>'secondary', default=>'secondary' })
                            <tr @class(['table-warning' => $ir->isOverdue()])>
                                <td class="fw-semibold"><a href="{{ route('inspection_requests.show', $ir) }}">{{ $ir->ir_number }}</a></td>
                                <td>{{ $ir->title }}</td>
                                <td>{{ \App\Models\InspectionRequest::TYPES[$ir->type] ?? $ir->type }}</td>
                                <td><span class="badge text-bg-{{ $irBadge }}">{{ \App\Models\InspectionRequest::STATUSES[$ir->status] ?? $ir->status }}</span></td>
                                <td>{{ $ir->scheduled_date?->format('Y-m-d') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div></div>
    @endif

    {{-- محاضر الاجتماعات --}}
    @if ($project->meetings->isNotEmpty())
        <div class="card mb-3"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="m-0"><i class="fa-solid fa-users-rectangle ms-1" style="color:#2b4c80"></i> محاضر الاجتماعات <span class="badge text-bg-secondary">{{ $project->meetings->count() }}</span></h6>
                <a href="{{ route('meetings.index', ['project_id' => $project->id]) }}" class="small text-decoration-none">عرض الكل</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>الرقم</th><th>العنوان</th><th>التاريخ</th></tr></thead>
                    <tbody>
                        @foreach ($project->meetings->take(5) as $meeting)
                            <tr>
                                <td class="fw-semibold"><a href="{{ route('meetings.show', $meeting) }}">{{ $meeting->meeting_number }}</a></td>
                                <td>{{ $meeting->title }}</td>
                                <td>{{ $meeting->meeting_date?->format('Y-m-d') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div></div>
    @endif

    {{-- آخر يوميات الموقع --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="m-0"><i class="fa-solid fa-clipboard-list ms-1"></i> آخر يوميات الموقع</h6>
                <a href="{{ route('daily_site_reports.index', ['project_id' => $project->id]) }}" class="small text-decoration-none">عرض الكل</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>التاريخ</th><th>الطقس</th><th>العمالة</th><th>الأعمال المنفّذة</th></tr></thead>
                    <tbody>
                        @forelse ($project->dailySiteReports()->take(5)->get() as $dsr)
                            <tr>
                                <td><a href="{{ route('daily_site_reports.show', $dsr) }}">{{ $dsr->report_date?->format('Y-m-d') }}</a></td>
                                <td>{{ $dsr->weather ?: '—' }}</td>
                                <td><span class="badge text-bg-info">{{ $dsr->labor_count }}</span></td>
                                <td>{{ \Illuminate\Support\Str::limit($dsr->work_done, 50) ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">لا توجد يوميات لهذا المشروع.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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
                                        <form method="POST" action="{{ route('projectEmployees.destroy', $employee->pivot->id) }}" class="d-inline" data-confirm="حذف الموظف من المشروع؟">
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
                        <button class="btn w-100" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-plus"></i></button>
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
                                        <form method="POST" action="{{ route('projectMaterialConsumptions.destroy', $consumption) }}" class="d-inline" data-confirm="حذف سجل الاستهلاك؟">
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
                        <button class="btn w-100" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-plus"></i></button>
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
