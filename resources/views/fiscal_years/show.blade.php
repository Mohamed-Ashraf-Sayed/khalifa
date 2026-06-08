@extends('layouts.app')

@section('title', 'السنة المالية ' . $fy->name)

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <div>
            <span class="h5 mb-0">السنة المالية {{ $fy->name }}</span>
            <span class="badge text-bg-{{ $fy->status === 'closed' ? 'secondary' : 'success' }} ms-2">{{ \App\Models\FiscalYear::STATUSES[$fy->status] ?? $fy->status }}</span>
        </div>
        <div class="d-flex gap-2">
            @can('accounting.edit')
                @if ($fy->status === 'open')
                    <form method="POST" action="{{ route('fiscal_years.close', $fy) }}" data-confirm="إقفال السنة سيولّد قيد إقفال (تصفير الإيرادات/المصروفات للأرباح المرحّلة) ويقفل كل الفترات. متابعة؟">
                        @csrf
                        <button class="btn btn-sm" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-lock ms-1"></i> إقفال السنة</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('fiscal_years.reopen', $fy) }}" data-confirm="إعادة الفتح ستحذف قيد الإقفال وتفتح كل الفترات. متابعة؟">
                        @csrf
                        <button class="btn btn-sm btn-outline-warning"><i class="fa-solid fa-lock-open ms-1"></i> إعادة فتح السنة</button>
                    </form>
                @endif
            @endcan
            <a href="{{ route('fiscal_years.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="stat-box"><div class="sl">من</div><div class="sv">{{ $fy->start_date->format('Y-m-d') }}</div></div></div>
        <div class="col-md-3"><div class="stat-box"><div class="sl">إلى</div><div class="sv">{{ $fy->end_date->format('Y-m-d') }}</div></div></div>
        <div class="col-md-3"><div class="stat-box"><div class="sl">الفترات المقفلة</div><div class="sv">{{ $fy->periods->where('status','closed')->count() }} / {{ $fy->periods->count() }}</div></div></div>
        <div class="col-md-3"><div class="stat-box accent"><div class="sl">قيد الإقفال</div><div class="sv">@if ($fy->closingEntry)<a href="{{ route('journal_entries.show', $fy->closingEntry) }}">{{ $fy->closingEntry->entry_number }}</a>@else <span class="text-muted">—</span> @endif</div></div></div>
    </div>

    <div class="card"><div class="card-body">
        <h6 class="mb-3"><i class="fa-solid fa-calendar-days ms-1" style="color:#2b4c80"></i> الفترات الشهرية</h6>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>الفترة</th><th>من</th><th>إلى</th><th>الحالة</th><th class="text-end">إجراء</th></tr></thead>
                <tbody>
                    @foreach ($fy->periods as $p)
                        <tr>
                            <td class="fw-semibold">{{ $p->name }}</td>
                            <td>{{ $p->start_date->format('Y-m-d') }}</td>
                            <td>{{ $p->end_date->format('Y-m-d') }}</td>
                            <td><span class="badge text-bg-{{ $p->status === 'closed' ? 'secondary' : 'success' }}">{{ \App\Models\FiscalPeriod::STATUSES[$p->status] ?? $p->status }}</span></td>
                            <td class="text-end">
                                @can('accounting.edit')
                                    @if ($p->status === 'open')
                                        <form method="POST" action="{{ route('fiscal_periods.close', $p) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-lock ms-1"></i> قفل</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('fiscal_periods.open', $p) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-warning"><i class="fa-solid fa-lock-open ms-1"></i> فتح</button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="alert alert-info border mt-3 mb-0 small"><i class="fa-solid fa-circle-info ms-1"></i> قفل الفترة يمنع أي ترحيل (يدوي أو تلقائي) لقيود تواريخها داخل الفترة. إقفال السنة يولّد قيد إقفال يصفّر الإيرادات والمصروفات إلى «الأرباح المرحّلة».</div>
    </div></div>
@endsection
