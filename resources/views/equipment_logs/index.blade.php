@extends('layouts.app')

@section('title', 'سجل تشغيل/صيانة المعدات')

@section('content')
    <div class="row g-3 mb-3">
        @foreach ([
            ['عدد السجلات', number_format($logs->total()), 'fa-screwdriver-wrench', 'text-primary'],
            ['إجمالي تكلفة الصيانة', number_format($maintenanceCost, 0), 'fa-coins', 'text-danger'],
        ] as [$l, $v, $icon, $color])
        <div class="col-md-3 col-6"><div class="card h-100"><div class="card-body py-3">
            <i class="fa-solid {{ $icon }} {{ $color }}"></i>
            <div class="fs-4 fw-bold">{{ $v }}</div>
            <div class="small text-muted">{{ $l }}</div>
        </div></div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2 flex-wrap" method="GET">
                    <select name="asset_id" class="form-select" style="min-width:200px" onchange="this.form.submit()">
                        <option value="">كل المعدات</option>
                        @foreach ($assets as $a)
                            <option value="{{ $a->id }}" @selected($assetId == $a->id)>{{ $a->asset_name }}</option>
                        @endforeach
                    </select>
                    <select name="log_type" class="form-select" style="min-width:160px" onchange="this.form.submit()">
                        <option value="">كل الأنواع</option>
                        @foreach (\App\Models\EquipmentLog::LOG_TYPES as $key => $label)
                            <option value="{{ $key }}" @selected($logType === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                @can('assets.edit')
                    <button class="btn" style="background:#2b4c80;color:#fff" type="button" data-bs-toggle="collapse" data-bs-target="#newLogForm">
                        <i class="fa-solid fa-plus ms-1"></i> سجل جديد
                    </button>
                @endcan
            </div>

            @can('assets.edit')
                <div class="collapse mb-3" id="newLogForm">
                    <div class="border rounded p-3 bg-light">
                        <form method="POST" action="{{ route('equipment_logs.store') }}" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-md-3"><label class="form-label small">المعدة</label>
                                <select name="asset_id" class="form-select" required>
                                    <option value="">— اختر المعدة —</option>
                                    @foreach ($assets as $a)
                                        <option value="{{ $a->id }}" @selected($assetId == $a->id)>{{ $a->asset_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2"><label class="form-label small">النوع</label>
                                <select name="log_type" class="form-select">
                                    @foreach (\App\Models\EquipmentLog::LOG_TYPES as $key => $label)
                                        <option value="{{ $key }}" @selected($logType === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2"><label class="form-label small">التاريخ</label><input type="date" name="log_date" value="{{ now()->toDateString() }}" class="form-control" required></div>
                            <div class="col-md-2"><label class="form-label small">ساعات التشغيل</label><input type="number" step="0.01" min="0" name="operating_hours" class="form-control"></div>
                            <div class="col-md-2"><label class="form-label small">التكلفة</label><input type="number" step="0.01" min="0" name="cost" class="form-control"></div>
                            <div class="col-md-3"><label class="form-label small">الصيانة القادمة</label><input type="date" name="next_service_date" class="form-control"></div>
                            <div class="col-md-6"><label class="form-label small">الوصف</label><input type="text" name="description" class="form-control"></div>
                            <div class="col-md-3"><button class="btn w-100" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-plus ms-1"></i> إضافة سجل</button></div>
                        </form>
                    </div>
                </div>
            @endcan

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>المعدة</th>
                            <th>النوع</th>
                            <th>التاريخ</th>
                            <th>ساعات التشغيل</th>
                            <th>التكلفة</th>
                            <th>الصيانة القادمة</th>
                            <th>الوصف</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            @php($badge = $log->log_type === 'maintenance' ? 'warning' : 'info')
                            <tr>
                                <td class="fw-semibold">{{ $log->asset?->asset_name ?? '—' }}</td>
                                <td><span class="badge text-bg-{{ $badge }}">{{ \App\Models\EquipmentLog::LOG_TYPES[$log->log_type] ?? $log->log_type }}</span></td>
                                <td>{{ $log->log_date?->format('Y-m-d') ?? '—' }}</td>
                                <td>{{ $log->operating_hours !== null ? number_format((float) $log->operating_hours, 2) : '—' }}</td>
                                <td>{{ $log->cost !== null ? number_format((float) $log->cost, 2) : '—' }}</td>
                                <td>{{ $log->next_service_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="text-muted small">{{ \Illuminate\Support\Str::limit($log->description, 40) ?: '—' }}</td>
                                <td class="text-end">
                                    @can('assets.edit')
                                        <form method="POST" action="{{ route('equipment_logs.destroy', $log) }}" class="d-inline"
                                              data-confirm="متأكد من حذف السجل؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">لا توجد سجلات بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $logs->links() }}
        </div>
    </div>
@endsection
