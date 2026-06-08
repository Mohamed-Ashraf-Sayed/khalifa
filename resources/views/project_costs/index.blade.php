@extends('layouts.app')

@section('title', 'تكاليف المشاريع')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-4 col-6"><div class="card h-100"><div class="card-body py-3"><i class="fa-solid fa-coins text-success"></i><div class="fs-4 fw-bold">{{ number_format((float) $stats['total'], 2) }}</div><div class="small text-muted">إجمالي التكاليف</div></div></div></div>
        <div class="col-md-4 col-6"><div class="card h-100"><div class="card-body py-3"><i class="fa-solid fa-list text-primary"></i><div class="fs-4 fw-bold">{{ number_format($stats['count']) }}</div><div class="small text-muted">عدد البنود</div></div></div></div>
        <div class="col-md-4 col-12"><div class="card h-100"><div class="card-body py-3"><i class="fa-solid fa-layer-group text-warning"></i><div class="fs-4 fw-bold">{{ number_format($stats['work_items']) }}</div><div class="small text-muted">عدد بنود الأعمال</div></div></div></div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2 flex-wrap" method="GET">
                    <select name="project_id" class="form-select" style="min-width:180px" onchange="this.form.submit()">
                        <option value="">كل المشاريع</option>
                        @foreach ($projects as $p)
                            <option value="{{ $p->id }}" @selected($projectId == $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="work_item" value="{{ $workItem }}" class="form-control" style="min-width:160px" placeholder="بند الأعمال">
                    <input type="text" name="contractor_supplier" value="{{ $contractorSupplier }}" class="form-control" style="min-width:160px" placeholder="الجهة">
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                <div class="d-flex gap-2">
                    <a href="{{ route('project_costs.report', ['project_id' => $projectId]) }}" class="btn btn-outline-secondary"><i class="fa-solid fa-chart-pie ms-1"></i> تقرير التكاليف</a>
                    @can('projects.create')
                        <a href="{{ route('project_costs.create') }}" class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-plus ms-1"></i> تكلفة جديدة</a>
                    @endcan
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>المشروع</th><th>بند الأعمال</th><th>الجهة</th><th>الكمية</th><th>سعر الوحدة</th><th>الإجمالي</th><th>التاريخ</th><th class="text-end">إجراءات</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($costs as $c)
                            <tr>
                                <td>{{ $c->project?->name ?? '—' }}</td>
                                <td class="fw-semibold">{{ $c->work_item }}</td>
                                <td>{{ $c->contractor_supplier ?? '—' }}</td>
                                <td>{{ rtrim(rtrim(number_format($c->quantity, 3), '0'), '.') }} {{ $c->unit }}</td>
                                <td>{{ number_format($c->unit_price, 2) }}</td>
                                <td class="fw-bold">{{ number_format($c->amount, 2) }}</td>
                                <td>{{ $c->cost_date?->format('Y-m-d') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('project_costs.show', $c) }}" class="btn btn-sm btn-outline-secondary" title="عرض"><i class="fa-solid fa-eye"></i></a>
                                    @can('projects.edit')
                                        <a href="{{ route('project_costs.edit', $c) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('projects.delete')
                                        <form method="POST" action="{{ route('project_costs.destroy', $c) }}" class="d-inline" data-confirm="حذف التكلفة؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">لا توجد تكاليف بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $costs->links() }}
        </div>
    </div>
@endsection
