@extends('layouts.app')

@section('title', 'تقرير التكاليف')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <form class="d-flex gap-2" method="GET">
            <select name="project_id" class="form-select" style="min-width:220px" onchange="this.form.submit()">
                <option value="">— اختر مشروعاً —</option>
                @foreach ($projects as $p)
                    <option value="{{ $p->id }}" @selected($project && $project->id == $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
            <noscript><button class="btn btn-outline-secondary">عرض</button></noscript>
        </form>
        <div class="d-flex gap-2">
            @if ($project)
                <a href="{{ route('project_costs.report', ['project_id' => $project->id, 'export' => 'csv']) }}" class="btn btn-outline-success"><i class="fa-solid fa-file-csv ms-1"></i> تصدير CSV</a>
            @endif
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
            <a href="{{ route('project_costs.index') }}" class="btn btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    @unless ($project)
        <div class="alert alert-info">اختر مشروعاً لعرض تقرير التكاليف.</div>
    @else
        <div class="row g-3 mb-3">
            <div class="col-md-4 col-12"><div class="card h-100"><div class="card-body py-3"><i class="fa-solid fa-coins text-success"></i><div class="fs-4 fw-bold">{{ number_format((float) $totalCost, 2) }}</div><div class="small text-muted">إجمالي التكاليف — {{ $project->name }}</div></div></div></div>
            @if (! is_null($contractValue))
                <div class="col-md-4 col-6"><div class="card h-100"><div class="card-body py-3"><i class="fa-solid fa-file-contract text-primary"></i><div class="fs-4 fw-bold">{{ number_format((float) $contractValue, 2) }}</div><div class="small text-muted">قيمة العقد</div></div></div></div>
                <div class="col-md-4 col-6"><div class="card h-100"><div class="card-body py-3"><i class="fa-solid fa-equals {{ bccomp($variance, '0', 2) < 0 ? 'text-danger' : 'text-warning' }}"></i><div class="fs-4 fw-bold {{ bccomp($variance, '0', 2) < 0 ? 'text-danger' : '' }}">{{ number_format((float) $variance, 2) }}</div><div class="small text-muted">الفرق (قيمة العقد − التكاليف)</div></div></div></div>
            @endif
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-white fw-semibold">التكلفة حسب بند الأعمال</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light"><tr><th>بند الأعمال</th><th class="text-end">الإجمالي</th></tr></thead>
                                <tbody>
                                    @forelse ($byWorkItem as $item => $amount)
                                        <tr><td>{{ $item }}</td><td class="text-end fw-semibold">{{ number_format((float) $amount, 2) }}</td></tr>
                                    @empty
                                        <tr><td colspan="2" class="text-center text-muted py-3">لا توجد تكاليف.</td></tr>
                                    @endforelse
                                </tbody>
                                <tfoot><tr class="table-light"><th>الإجمالي</th><th class="text-end">{{ number_format((float) $totalCost, 2) }}</th></tr></tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-white fw-semibold">التكلفة حسب الجهة</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light"><tr><th>الجهة (مقاول/مورد)</th><th class="text-end">الإجمالي</th></tr></thead>
                                <tbody>
                                    @forelse ($byContractor as $name => $amount)
                                        <tr><td>{{ $name }}</td><td class="text-end fw-semibold">{{ number_format((float) $amount, 2) }}</td></tr>
                                    @empty
                                        <tr><td colspan="2" class="text-center text-muted py-3">لا توجد تكاليف.</td></tr>
                                    @endforelse
                                </tbody>
                                <tfoot><tr class="table-light"><th>الإجمالي</th><th class="text-end">{{ number_format((float) $totalCost, 2) }}</th></tr></tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endunless
@endsection
