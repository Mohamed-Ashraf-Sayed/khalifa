@extends('layouts.app')

@section('title', 'المواد')

@section('content')
    <div class="row g-3 mb-3">
        @foreach ([
            ['عدد الأصناف', number_format($stats['count']), 'fa-boxes-stacked', 'text-primary'],
            ['قيمة المخزون', number_format($stats['value'], 2).' ج', 'fa-sack-dollar', 'text-success'],
            ['أصناف تحت الحد', number_format($stats['low_stock']), 'fa-triangle-exclamation', 'text-danger'],
        ] as [$label, $val, $icon, $color])
        <div class="col-md-4 col-6">
            <div class="card h-100"><div class="card-body py-3">
                <i class="fa-solid {{ $icon }} {{ $color }}"></i>
                <div class="fs-4 fw-bold">{{ $val }}</div>
                <div class="small text-muted">{{ $label }}</div>
            </div></div>
        </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end gap-2 mb-3">
                <a href="{{ route('materials.report') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-chart-pie ms-1"></i> تقرير المخزون</a>
                @can('materials.create')
                    <a href="{{ route('materials.create') }}" class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-plus ms-1"></i> مادة جديدة</a>
                @endcan
            </div>
            <form method="GET" class="filter-bar row g-2 align-items-end mb-3">
                <div class="col-6 col-md-3">
                    <label class="form-label">بحث</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">الفئة</label>
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">كل التصنيفات</option>
                        @foreach (\App\Models\Material::CATEGORIES as $key => $label)
                            <option value="{{ $key }}" @selected($category === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">المورد</label>
                    <select name="supplier_id" class="form-select" onchange="this.form.submit()">
                        <option value="">كل المورّدين</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}" @selected($supplierId === (string) $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">المشروع</label>
                    <select name="project_id" class="form-select" onchange="this.form.submit()">
                        <option value="">كل المشاريع</option>
                        @foreach ($projects as $p)
                            <option value="{{ $p->id }}" @selected($projectId === (string) $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">تحت الحد فقط</label>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="low_stock" name="low_stock" value="1" @checked($lowStock === '1') onchange="this.form.submit()">
                        <label class="form-check-label" for="low_stock">عرض الأصناف تحت الحد</label>
                    </div>
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
                            <tr @class(['table-danger' => $material->isLowStock()])>
                                <td class="fw-semibold">{{ $material->name }}</td>
                                <td><span class="badge text-bg-secondary">{{ \App\Models\Material::CATEGORIES[$material->category] ?? $material->category }}</span></td>
                                <td>{{ $material->unit }}</td>
                                <td>{{ number_format($material->unit_price, 2) }} ج</td>
                                <td>
                                    {{ number_format($material->current_stock, 2) }}
                                    @if ($material->isLowStock())
                                        <span class="badge text-bg-danger ms-1">مخزون منخفض</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('materials.show', $material) }}" class="btn btn-sm btn-outline-secondary" title="عرض"><i class="fa-solid fa-eye"></i></a>
                                    @can('materials.edit')
                                        <a href="{{ route('materials.edit', $material) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('materials.delete')
                                        <form method="POST" action="{{ route('materials.destroy', $material) }}" class="d-inline"
                                              data-confirm="متأكد من حذف المادة؟">
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
