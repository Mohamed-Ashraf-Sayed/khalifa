@extends('layouts.app')

@section('title', 'تقرير المخزون')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h4 class="mb-0">تقييم المخزون</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('materials.report', ['export' => 'csv']) }}" class="btn btn-outline-success"><i class="fa-solid fa-file-csv ms-1"></i> تصدير CSV</a>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
            <a href="{{ route('materials.index') }}" class="btn btn-light">رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h6 class="text-muted mb-3">تقييم المخزون حسب الصنف</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>المادة</th>
                            <th>المخزون الحالي</th>
                            <th>سعر الوحدة</th>
                            <th>قيمة المخزون</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($materials as $material)
                            <tr @class(['table-danger' => $material->isLowStock()])>
                                <td class="fw-semibold">{{ $material->name }}</td>
                                <td>{{ number_format($material->current_stock, 2) }} {{ $material->unit }}</td>
                                <td>{{ number_format($material->unit_price, 2) }} ج</td>
                                <td class="fw-bold">{{ number_format($material->stockValue(), 2) }} ج</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">لا توجد مواد.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th colspan="3" class="text-end">الإجمالي</th>
                            <th class="fw-bold">{{ number_format($totalValue, 2) }} ج</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="text-danger mb-3"><i class="fa-solid fa-triangle-exclamation ms-1"></i> أصناف تحت الحد الأدنى</h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>المادة</th>
                            <th>المخزون الحالي</th>
                            <th>الحد الأدنى</th>
                            <th>الوحدة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lowStockMaterials as $material)
                            <tr>
                                <td class="fw-semibold">{{ $material->name }}</td>
                                <td class="text-danger fw-bold">{{ number_format($material->current_stock, 2) }}</td>
                                <td>{{ number_format($material->min_stock, 2) }}</td>
                                <td>{{ $material->unit }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">لا توجد أصناف تحت الحد الأدنى.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
