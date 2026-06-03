@extends('layouts.app')

@section('title', 'بيانات المشروع')

@section('content')
    @php($badge = match($project->status) {
        'completed' => 'success', 'in_progress' => 'primary',
        'on_hold' => 'warning', 'cancelled' => 'danger', default => 'secondary' })

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">{{ $project->name }}</h5>
        <div class="d-flex gap-2">
            @can('projects.edit')
                <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
            @endcan
            <a href="{{ route('projects.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">اسم المشروع</div><div class="fw-semibold">{{ $project->name }}</div></div>
                <div class="col-md-4"><div class="text-muted small">العميل</div><div>{{ $project->client?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">النوع</div><div>{{ \App\Models\Project::TYPES[$project->project_type] ?? $project->project_type }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الحالة</div><div><span class="badge text-bg-{{ $badge }}">{{ \App\Models\Project::STATUSES[$project->status] ?? $project->status }}</span></div></div>
                <div class="col-md-4"><div class="text-muted small">قيمة العقد</div><div class="fw-semibold">{{ number_format($project->contract_value, 2) }} ج</div></div>
                <div class="col-md-4"><div class="text-muted small">مدير المشروع</div><div>{{ $project->manager?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الموقع</div><div>{{ $project->location ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ البداية</div><div>{{ $project->start_date?->format('Y-m-d') ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ النهاية</div><div>{{ $project->end_date?->format('Y-m-d') ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ النهاية الفعلي</div><div>{{ $project->actual_end_date?->format('Y-m-d') ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">أنشئ بواسطة</div><div>{{ $project->creator?->name ?? '—' }}</div></div>
                @if ($project->description)<div class="col-12"><div class="text-muted small">الوصف</div><div>{{ $project->description }}</div></div>@endif
                @if ($project->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $project->notes }}</div></div>@endif
            </div>
        </div>
    </div>

    @if ($project->client)
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="mb-3">بيانات العميل</h6>
                <div class="row g-3">
                    <div class="col-md-4"><div class="text-muted small">الاسم</div><div class="fw-semibold">{{ $project->client->name }}</div></div>
                    <div class="col-md-4"><div class="text-muted small">الشركة</div><div>{{ $project->client->company_name ?: '—' }}</div></div>
                    <div class="col-md-4"><div class="text-muted small">الهاتف</div><div dir="ltr" class="text-end">{{ $project->client->phone ?: '—' }}</div></div>
                </div>
            </div>
        </div>
    @endif

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

    <div class="card">
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
@endsection
