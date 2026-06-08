@extends('layouts.app')

@section('title', 'الأصول الثابتة')

@section('content')
    <div class="row g-3 mb-3">
        @foreach ([
            ['عدد الأصول', number_format($stats['count']), 'fa-warehouse', 'text-primary'],
            ['إجمالي التكلفة', number_format($stats['cost'], 0), 'fa-sack-dollar', 'text-secondary'],
            ['مجمّع الإهلاك', number_format($stats['accumulated'], 0), 'fa-arrow-trend-down', 'text-danger'],
            ['صافي القيمة الدفترية', number_format($stats['book'], 0), 'fa-scale-balanced', 'text-success'],
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
            <div class="d-flex justify-content-end gap-2 mb-3">
                <a href="{{ route('assets.report') }}" class="btn btn-light"><i class="fa-solid fa-chart-pie ms-1"></i> تقرير الإهلاك</a>
                @can('assets.create')
                    <a href="{{ route('assets.create') }}" class="btn" style="background:#2b4c80;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> أصل جديد
                    </a>
                @endcan
            </div>

            <form method="GET" class="filter-bar row g-2 align-items-end mb-3">
                <div class="col-6 col-md-3">
                    <label class="form-label">بحث</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\Asset::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-auto">
                    <div class="filter-actions">
                        <button class="btn btn-primary"><i class="fa-solid fa-magnifying-glass ms-1"></i> بحث</button>
                        @if (request()->query())
                            <a href="{{ url()->current() }}" class="btn btn-light">مسح</a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>كود الأصل</th>
                            <th>اسم الأصل</th>
                            <th>التصنيف</th>
                            <th>قيمة الشراء</th>
                            <th>القيمة الدفترية</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assets as $asset)
                            @php($badge = match($asset->status) {
                                'active' => 'success', 'sold' => 'primary',
                                'disposed' => 'danger', 'fully_depreciated' => 'warning', default => 'secondary' })
                            <tr>
                                <td class="fw-semibold">{{ $asset->asset_code }}</td>
                                <td>{{ $asset->asset_name }}</td>
                                <td>{{ $asset->category ?? '—' }}</td>
                                <td>{{ number_format($asset->purchase_value, 2) }} ج</td>
                                <td class="fw-semibold text-success">{{ number_format((float) $asset->bookValue(), 2) }}</td>
                                <td><span class="badge text-bg-{{ $badge }}">{{ \App\Models\Asset::STATUSES[$asset->status] ?? $asset->status }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('assets.show', $asset) }}" class="btn btn-sm btn-outline-secondary" title="عرض">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @can('assets.edit')
                                        <a href="{{ route('assets.edit', $asset) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('assets.delete')
                                        <form method="POST" action="{{ route('assets.destroy', $asset) }}" class="d-inline"
                                              data-confirm="متأكد من حذف الأصل؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">لا توجد أصول بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $assets->links() }}
        </div>
    </div>
@endsection
