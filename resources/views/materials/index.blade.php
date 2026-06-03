@extends('layouts.app')

@section('title', 'المواد')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2" method="GET">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بحث باسم المادة">
                    <select name="category" class="form-select" style="min-width:160px" onchange="this.form.submit()">
                        <option value="">كل التصنيفات</option>
                        @foreach (\App\Models\Material::CATEGORIES as $key => $label)
                            <option value="{{ $key }}" @selected($category === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                @can('materials.create')
                    <a href="{{ route('materials.create') }}" class="btn" style="background:#8b7355;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> مادة جديدة
                    </a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>المادة</th>
                            <th>التصنيف</th>
                            <th>الوحدة</th>
                            <th>سعر الوحدة</th>
                            <th>المخزون الحالي</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($materials as $material)
                            <tr>
                                <td class="fw-semibold">{{ $material->name }}</td>
                                <td><span class="badge text-bg-secondary">{{ \App\Models\Material::CATEGORIES[$material->category] ?? $material->category }}</span></td>
                                <td>{{ $material->unit }}</td>
                                <td>{{ number_format($material->unit_price, 2) }} ج</td>
                                <td>
                                    {{ number_format($material->current_stock, 2) }}
                                    @if ($material->current_stock <= $material->min_stock)
                                        <span class="badge text-bg-danger ms-1">مخزون منخفض</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @can('materials.edit')
                                        <a href="{{ route('materials.edit', $material) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('materials.delete')
                                        <form method="POST" action="{{ route('materials.destroy', $material) }}" class="d-inline"
                                              onsubmit="return confirm('متأكد من حذف المادة؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">لا توجد مواد بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $materials->links() }}
        </div>
    </div>
@endsection
