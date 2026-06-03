@extends('layouts.app')

@section('title', 'الأصول الثابتة')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2" method="GET">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بحث بالاسم أو الكود">
                    <select name="status" class="form-select" style="min-width:160px" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\Asset::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                @can('assets.create')
                    <a href="{{ route('assets.create') }}" class="btn" style="background:#8b7355;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> أصل جديد
                    </a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>كود الأصل</th>
                            <th>اسم الأصل</th>
                            <th>التصنيف</th>
                            <th>قيمة الشراء</th>
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
                                              onsubmit="return confirm('متأكد من حذف الأصل؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">لا توجد أصول بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $assets->links() }}
        </div>
    </div>
@endsection
