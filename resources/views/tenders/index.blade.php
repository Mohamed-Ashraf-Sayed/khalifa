@extends('layouts.app')

@section('title', 'المناقصات والعطاءات')

@section('content')
    <div class="row g-3 mb-3">
        @foreach ([
            ['عدد المناقصات', number_format($stats['count']), 'fa-gavel', 'text-primary'],
            ['مناقصات مقدّمة', number_format($stats['submitted']), 'fa-paper-plane', 'text-info'],
            ['مناقصات فائزة', number_format($stats['won']), 'fa-trophy', 'text-success'],
            ['نسبة الفوز', $stats['win_rate'] . '%', 'fa-percent', 'text-secondary'],
            ['إجمالي القيمة التقديرية', number_format($stats['estimated'], 0), 'fa-sack-dollar', 'text-warning'],
        ] as [$l, $v, $icon, $color])
        <div class="col-md col-6"><div class="card h-100"><div class="card-body py-3">
            <i class="fa-solid {{ $icon }} {{ $color }}"></i>
            <div class="fs-4 fw-bold">{{ $v }}</div>
            <div class="small text-muted">{{ $l }}</div>
        </div></div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end gap-2 mb-3">
                @can('tenders.create')
                    <a href="{{ route('tenders.create') }}" class="btn" style="background:#2b4c80;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> مناقصة جديدة
                    </a>
                @endcan
            </div>

            <form method="GET" class="filter-bar row g-2 align-items-end mb-3">
                <div class="col-6 col-md-3">
                    <label class="form-label">بحث</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بالعنوان أو الرقم">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\Tender::STATUSES as $key => $label)
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
                            <th>رقم المناقصة</th>
                            <th>العنوان</th>
                            <th>العميل</th>
                            <th>القيمة التقديرية</th>
                            <th>قيمة العطاء</th>
                            <th>تاريخ التقديم</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tenders as $tender)
                            @php($badge = match($tender->status) {
                                'won' => 'success', 'submitted' => 'info',
                                'lost' => 'danger', 'cancelled' => 'secondary', default => 'warning' })
                            <tr>
                                <td class="fw-semibold">{{ $tender->tender_number }}</td>
                                <td>{{ $tender->title }}</td>
                                <td>{{ $tender->client?->name ?? '—' }}</td>
                                <td>{{ $tender->estimated_value !== null ? number_format($tender->estimated_value, 2) : '—' }}</td>
                                <td>{{ $tender->bid_value !== null ? number_format($tender->bid_value, 2) : '—' }}</td>
                                <td>{{ $tender->submission_date?->format('Y-m-d') ?? '—' }}</td>
                                <td>
                                    <span class="badge text-bg-{{ $badge }}">{{ \App\Models\Tender::STATUSES[$tender->status] ?? $tender->status }}</span>
                                    @if ($tender->project_id)
                                        <span class="badge text-bg-light ms-1"><i class="fa-solid fa-diagram-project"></i> مشروع</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('tenders.show', $tender) }}" class="btn btn-sm btn-outline-secondary" title="عرض">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @can('tenders.edit')
                                        <a href="{{ route('tenders.edit', $tender) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('tenders.delete')
                                        <form method="POST" action="{{ route('tenders.destroy', $tender) }}" class="d-inline"
                                              data-confirm="متأكد من حذف المناقصة؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">لا توجد مناقصات بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $tenders->links() }}
        </div>
    </div>
@endsection
