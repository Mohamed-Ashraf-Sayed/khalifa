@extends('layouts.app')

@section('title', 'عروض الأسعار')

@section('content')
    <div class="row g-3 mb-3">
        @foreach ([
            ['عدد العروض', number_format($stats['count']), 'fa-file-invoice-dollar', 'text-primary'],
            ['إجمالي القيمة', number_format($stats['value'], 0), 'fa-sack-dollar', 'text-secondary'],
            ['العروض المقبولة', number_format($stats['accepted']), 'fa-circle-check', 'text-success'],
        ] as [$l, $v, $icon, $color])
        <div class="col-md-4 col-6"><div class="card h-100"><div class="card-body py-3">
            <i class="fa-solid {{ $icon }} {{ $color }}"></i>
            <div class="fs-4 fw-bold">{{ $v }}</div>
            <div class="small text-muted">{{ $l }}</div>
        </div></div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2" method="GET">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بحث برقم العرض">
                    <select name="status" class="form-select" style="min-width:160px" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\Quotation::STATUSES as $k => $label)
                            <option value="{{ $k }}" @selected($status === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                @can('quotations.create')
                    <a href="{{ route('quotations.create') }}" class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-plus ms-1"></i> عرض سعر جديد</a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>رقم العرض</th><th>العميل</th><th>المشروع</th><th>التاريخ</th><th>الإجمالي</th><th>الحالة</th><th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($quotations as $quotation)
                            @php($badge = match($quotation->status) { 'accepted'=>'success','sent'=>'info','rejected'=>'danger','expired'=>'secondary',default=>'light' })
                            <tr>
                                <td class="fw-semibold">{{ $quotation->quotation_number }}</td>
                                <td>{{ $quotation->client?->name ?? '—' }}</td>
                                <td>{{ $quotation->project?->name ?? '—' }}</td>
                                <td>{{ $quotation->issue_date->format('Y-m-d') }}</td>
                                <td class="fw-bold">{{ number_format($quotation->total_amount, 2) }}</td>
                                <td><span class="badge text-bg-{{ $badge }}">{{ \App\Models\Quotation::STATUSES[$quotation->status] ?? $quotation->status }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('quotations.show', $quotation) }}" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-eye"></i></a>
                                    @can('quotations.edit')
                                        <a href="{{ route('quotations.edit', $quotation) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('quotations.delete')
                                        <form method="POST" action="{{ route('quotations.destroy', $quotation) }}" class="d-inline" data-confirm="حذف عرض السعر؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">لا توجد عروض أسعار بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $quotations->links() }}
        </div>
    </div>
@endsection
