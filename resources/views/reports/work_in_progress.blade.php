@extends('layouts.app')

@section('title', 'تقرير العمل تحت التنفيذ (WIP)')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-end mb-3 no-print">
        <a href="{{ request()->fullUrlWithQuery(['format' => 'xlsx']) }}" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-file-excel ms-1"></i> Excel</a>
        <button onclick="window.print()" class="btn btn-sm" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
        <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">إجمالي العقود المعدّلة</div><div class="fs-5 fw-bold">{{ number_format((float) $totals['revised'], 2) }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">القيمة المكتسبة</div><div class="fs-5 fw-bold text-info">{{ number_format((float) $totals['earned'], 2) }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">إجمالي المُفوتر</div><div class="fs-5 fw-bold">{{ number_format((float) $totals['invoiced'], 2) }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">الربح المقدّر</div><div class="fs-5 fw-bold {{ bccomp($totals['profit'], '0', 2) >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $totals['profit'], 2) }}</div></div></div></div>
    </div>

    <div class="card"><div class="card-body">
        <h6 class="mb-1"><i class="fa-solid fa-diagram-project ms-1" style="color:#2b4c80"></i> الموقف التنفيذي والمالي للمشاريع</h6>
        <p class="text-muted small mb-3">القيمة المكتسبة = العقد المعدّل × نسبة الإنجاز · فائض الفوترة = المُفوتر − القيمة المكتسبة · الربح المقدّر = القيمة المكتسبة − التكلفة الفعلية.</p>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light"><tr>
                    <th>المشروع</th><th>الحالة</th><th>العقد المعدّل</th><th>الإنجاز</th><th>القيمة المكتسبة</th><th>المُفوتر</th><th>المحصّل</th><th>التكلفة الفعلية</th><th>فائض/عجز الفوترة</th><th>الربح المقدّر</th>
                </tr></thead>
                <tbody>
                    @forelse ($rows as $r)
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('projects.show', $r['id']) }}" class="text-decoration-none">{{ $r['name'] }}</a></td>
                            <td><span class="badge text-bg-light">{{ $r['status'] }}</span></td>
                            <td>{{ number_format((float) $r['revised'], 2) }}</td>
                            <td style="min-width:120px">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:7px"><div class="progress-bar" style="width:{{ $r['percent'] }}%;background:#2b4c80"></div></div>
                                    <span class="small text-muted">{{ $r['percent'] }}%</span>
                                </div>
                            </td>
                            <td class="text-info">{{ number_format((float) $r['earned'], 2) }}</td>
                            <td>{{ number_format((float) $r['invoiced'], 2) }}</td>
                            <td>{{ number_format((float) $r['collected'], 2) }}</td>
                            <td>{{ number_format((float) $r['cost'], 2) }}</td>
                            <td class="{{ bccomp($r['over_under'], '0', 2) >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $r['over_under'], 2) }}</td>
                            <td class="fw-bold {{ bccomp($r['profit'], '0', 2) >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $r['profit'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-3">لا توجد مشاريع.</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="2">الإجمالي</td>
                        <td>{{ number_format((float) $totals['revised'], 2) }}</td>
                        <td>—</td>
                        <td>{{ number_format((float) $totals['earned'], 2) }}</td>
                        <td>{{ number_format((float) $totals['invoiced'], 2) }}</td>
                        <td>{{ number_format((float) $totals['collected'], 2) }}</td>
                        <td>{{ number_format((float) $totals['cost'], 2) }}</td>
                        <td class="{{ bccomp($totals['over_under'], '0', 2) >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $totals['over_under'], 2) }}</td>
                        <td class="{{ bccomp($totals['profit'], '0', 2) >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $totals['profit'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div></div>
@endsection
