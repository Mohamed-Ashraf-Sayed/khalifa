@extends('layouts.app')

@section('title', 'بيانات الأصل')

@section('content')
    @php($badge = match($asset->status) {
        'active' => 'success', 'sold' => 'primary',
        'disposed' => 'danger', 'fully_depreciated' => 'warning', default => 'secondary' })
    @php($years = max(0, now()->diffInYears($asset->purchase_date)))
    @php($annualDepreciation = (float) $asset->purchase_value * ((float) $asset->depreciation_rate / 100))
    @php($accumulated = min((float) $asset->purchase_value, $annualDepreciation * $years))
    @php($bookValue = max(0, (float) $asset->purchase_value - $accumulated))

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">{{ $asset->asset_name }}</h5>
        <div class="d-flex gap-2">
            @can('assets.edit')
                <a href="{{ route('assets.edit', $asset) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
            @endcan
            <a href="{{ route('assets.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">كود الأصل</div><div class="fw-semibold">{{ $asset->asset_code }}</div></div>
                <div class="col-md-4"><div class="text-muted small">اسم الأصل</div><div>{{ $asset->asset_name }}</div></div>
                <div class="col-md-4"><div class="text-muted small">التصنيف</div><div>{{ $asset->category ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ الشراء</div><div>{{ $asset->purchase_date?->format('Y-m-d') ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">قيمة الشراء</div><div>{{ number_format($asset->purchase_value, 2) }} ج</div></div>
                <div class="col-md-4"><div class="text-muted small">معدل الإهلاك السنوي</div><div>{{ number_format($asset->depreciation_rate, 2) }}%</div></div>
                <div class="col-md-4"><div class="text-muted small">العمر الإنتاجي</div><div>{{ $asset->useful_life_years }} سنة</div></div>
                <div class="col-md-4"><div class="text-muted small">الموقع</div><div>{{ $asset->location ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الحالة</div><div><span class="badge text-bg-{{ $badge }}">{{ \App\Models\Asset::STATUSES[$asset->status] ?? $asset->status }}</span></div></div>
                @if ($asset->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $asset->notes }}</div></div>@endif
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h6 class="mb-3">الإهلاك والقيمة الدفترية</h6>
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">الإهلاك السنوي</div><div>{{ number_format($annualDepreciation, 2) }} ج</div></div>
                <div class="col-md-4"><div class="text-muted small">مجمع الإهلاك التقديري</div><div>{{ number_format($accumulated, 2) }} ج</div></div>
                <div class="col-md-4"><div class="text-muted small">القيمة الدفترية التقديرية</div><div class="fw-semibold">{{ number_format($bookValue, 2) }} ج</div></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><div class="text-muted small">أضيف بواسطة</div><div>{{ $asset->creator?->name ?: '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted small">تاريخ الإضافة</div><div>{{ $asset->created_at?->format('Y-m-d') ?: '—' }}</div></div>
            </div>
        </div>
    </div>
@endsection
