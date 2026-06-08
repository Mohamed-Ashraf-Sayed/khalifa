@extends('layouts.app')

@section('title', 'الأصل: ' . $asset->asset_name)

@section('content')
    @php($badge = match($asset->status) { 'active'=>'success','sold'=>'primary','disposed'=>'danger','fully_depreciated'=>'warning', default=>'secondary' })

    <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
        @can('assets.edit')
            <a href="{{ route('assets.edit', $asset) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
        @endcan
        <a href="{{ route('assets.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
    </div>

    {{-- بطاقة الأصل + مؤشرات الإهلاك --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
                <span class="entity-avatar"><i class="fa-solid fa-warehouse"></i></span>
                <div class="flex-grow-1">
                    <div class="h5 mb-1">{{ $asset->asset_name }}</div>
                    <div class="text-muted small">{{ $asset->category ?: 'أصل ثابت' }} · {{ $asset->asset_code }}</div>
                    <div class="mt-2">
                        <span class="badge text-bg-{{ $badge }}">{{ \App\Models\Asset::STATUSES[$asset->status] ?? $asset->status }}</span>
                        <span class="badge text-bg-light ms-1">{{ \App\Models\Asset::METHODS[$asset->depreciation_method] ?? '—' }}</span>
                    </div>
                </div>
            </div></div>
        </div>
        <div class="col-lg-7">
            <div class="row g-3 h-100">
                <div class="col-6"><div class="stat-box"><div class="sl">التكلفة (الشراء)</div><div class="sv">{{ number_format((float) $asset->purchase_value, 2) }}</div></div></div>
                <div class="col-6"><div class="stat-box"><div class="sl">مجمّع الإهلاك حتى الآن</div><div class="sv text-danger">{{ number_format((float) $asset->accumulatedDepreciation(), 2) }}</div></div></div>
                <div class="col-6"><div class="stat-box accent"><div class="sl">القيمة الدفترية الحالية</div><div class="sv text-success">{{ number_format((float) $asset->bookValue(), 2) }}</div></div></div>
                <div class="col-6"><div class="stat-box"><div class="sl">قسط الإهلاك (سنوي / شهري)</div><div class="sv">{{ number_format((float) $asset->annualDepreciation(), 0) }} <span class="text-muted" style="font-size:.8rem">/ {{ number_format((float) $asset->monthlyDepreciation(), 0) }}</span></div></div></div>
            </div>
        </div>
    </div>

    {{-- بيانات الأصل --}}
    <div class="card mb-3"><div class="card-body">
        <h6 class="mb-3"><i class="fa-solid fa-circle-info ms-1" style="color:#2b4c80"></i> بيانات الأصل</h6>
        <div class="info-list">
            <div class="il"><span class="k">تاريخ الشراء</span><span class="v">{{ $asset->purchase_date?->format('Y-m-d') ?? '—' }}</span></div>
            <div class="il"><span class="k">قيمة الخردة</span><span class="v">{{ number_format((float) ($asset->salvage_value ?? 0), 2) }}</span></div>
            <div class="il"><span class="k">العمر الإنتاجي</span><span class="v">{{ $asset->useful_life_years }} سنة</span></div>
            <div class="il"><span class="k">نسبة الإهلاك</span><span class="v">{{ rtrim(rtrim(number_format($asset->depreciation_rate, 2), '0'), '.') }}%</span></div>
            <div class="il"><span class="k">مدة الخدمة</span><span class="v">{{ intdiv($asset->monthsInService(), 12) }} سنة و{{ $asset->monthsInService() % 12 }} شهر</span></div>
            <div class="il"><span class="k">الموقع</span><span class="v">{{ $asset->location ?: '—' }}</span></div>
            <div class="il"><span class="k">أضيف بواسطة</span><span class="v">{{ $asset->creator?->name ?? '—' }} · {{ $asset->created_at?->format('Y-m-d') ?? '—' }}</span></div>
            @if ($asset->disposal_date)
                <div class="il"><span class="k">تاريخ الاستبعاد</span><span class="v">{{ $asset->disposal_date->format('Y-m-d') }}</span></div>
                <div class="il"><span class="k">قيمة البيع/الاستبعاد</span><span class="v">{{ number_format((float) $asset->disposal_value, 2) }}</span></div>
                <div class="il"><span class="k">ربح/خسارة الاستبعاد</span><span class="v {{ (float) $asset->disposalGainLoss() >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $asset->disposalGainLoss(), 2) }}</span></div>
            @endif
            @if ($asset->notes)<div class="il" style="grid-column:1/-1"><span class="k">ملاحظات</span><span class="v">{{ $asset->notes }}</span></div>@endif
        </div>
    </div></div>

    {{-- جدول الإهلاك السنوي --}}
    <div class="card mb-3"><div class="card-body">
        <h6 class="mb-3"><i class="fa-solid fa-table-list ms-1" style="color:#2b4c80"></i> جدول الإهلاك السنوي</h6>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>السنة</th><th>رصيد أول المدة</th><th>قسط الإهلاك</th><th>مجمّع الإهلاك</th><th>رصيد آخر المدة (الدفتري)</th></tr></thead>
                <tbody>
                    @foreach ($asset->depreciationSchedule() as $row)
                        <tr>
                            <td class="fw-semibold">سنة {{ $row['year'] }}</td>
                            <td>{{ number_format((float) $row['opening'], 2) }}</td>
                            <td class="text-danger">{{ number_format((float) $row['depreciation'], 2) }}</td>
                            <td>{{ number_format((float) $row['accumulated'], 2) }}</td>
                            <td class="fw-semibold">{{ number_format((float) $row['closing'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div></div>

    {{-- استبعاد/بيع الأصل --}}
    @can('assets.edit')
        @if (! in_array($asset->status, ['sold', 'disposed']))
            <div class="card"><div class="card-body">
                <h6 class="mb-3"><i class="fa-solid fa-box-archive ms-1" style="color:#2b4c80"></i> استبعاد / بيع الأصل</h6>
                <form method="POST" action="{{ route('assets.dispose', $asset) }}" class="row g-2 align-items-end" data-confirm="تأكيد استبعاد/بيع الأصل؟">
                    @csrf
                    <div class="col-md-3"><label class="form-label small">النوع</label>
                        <select name="status" class="form-select"><option value="sold">بيع</option><option value="disposed">استبعاد</option></select>
                    </div>
                    <div class="col-md-3"><label class="form-label small">التاريخ</label><input type="date" name="disposal_date" value="{{ now()->toDateString() }}" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label small">قيمة البيع/الاستبعاد</label><input type="number" step="0.01" min="0" name="disposal_value" value="0" class="form-control" required></div>
                    <div class="col-md-3"><button class="btn w-100" style="background:#2b4c80;color:#fff">تسجيل الاستبعاد</button></div>
                </form>
                <div class="text-muted small mt-2">القيمة الدفترية الحالية {{ number_format((float) $asset->bookValue(), 2) }} — الفرق عن قيمة البيع يُحسب كربح/خسارة استبعاد.</div>
            </div></div>
        @endif
    @endcan

    {{-- سجل تشغيل / صيانة المعدة --}}
    <div class="card mt-3"><div class="card-body">
        <h6 class="mb-3"><i class="fa-solid fa-screwdriver-wrench ms-1" style="color:#2b4c80"></i> سجل التشغيل والصيانة</h6>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>النوع</th><th>التاريخ</th><th>ساعات التشغيل</th><th>التكلفة</th><th>الصيانة القادمة</th><th>الوصف</th><th></th></tr></thead>
                <tbody>
                    @forelse ($asset->logs as $log)
                        @php($lbadge = $log->log_type === 'maintenance' ? 'warning' : 'info')
                        <tr>
                            <td><span class="badge text-bg-{{ $lbadge }}">{{ \App\Models\EquipmentLog::LOG_TYPES[$log->log_type] ?? $log->log_type }}</span></td>
                            <td>{{ $log->log_date?->format('Y-m-d') ?? '—' }}</td>
                            <td>{{ $log->operating_hours !== null ? number_format((float) $log->operating_hours, 2) : '—' }}</td>
                            <td>{{ $log->cost !== null ? number_format((float) $log->cost, 2) : '—' }}</td>
                            <td>{{ $log->next_service_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="text-muted small">{{ $log->description ?: '—' }}</td>
                            <td class="text-end">
                                @can('assets.edit')
                                    <form method="POST" action="{{ route('equipment_logs.destroy', $log) }}" class="d-inline" data-confirm="متأكد من حذف السجل؟">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-3">لا توجد سجلات بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @can('assets.edit')
            <form method="POST" action="{{ route('equipment_logs.store') }}" class="row g-2 align-items-end mt-3">
                @csrf
                <input type="hidden" name="asset_id" value="{{ $asset->id }}">
                <div class="col-md-2"><label class="form-label small">النوع</label>
                    <select name="log_type" class="form-select">
                        @foreach (\App\Models\EquipmentLog::LOG_TYPES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label small">التاريخ</label><input type="date" name="log_date" value="{{ now()->toDateString() }}" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label small">ساعات التشغيل</label><input type="number" step="0.01" min="0" name="operating_hours" class="form-control"></div>
                <div class="col-md-2"><label class="form-label small">التكلفة</label><input type="number" step="0.01" min="0" name="cost" class="form-control"></div>
                <div class="col-md-2"><label class="form-label small">الصيانة القادمة</label><input type="date" name="next_service_date" class="form-control"></div>
                <div class="col-md-2"><label class="form-label small">الوصف</label><input type="text" name="description" class="form-control"></div>
                <div class="col-12"><button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-plus ms-1"></i> إضافة سجل</button></div>
            </form>
        @endcan
    </div></div>
@endsection
