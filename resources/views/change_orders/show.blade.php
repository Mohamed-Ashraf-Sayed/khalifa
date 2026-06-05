@extends('layouts.app')

@section('title', 'أمر تغيير: ' . $changeOrder->co_number)

@section('content')
    @php($badge = match($changeOrder->status) { 'approved'=>'success','pending'=>'warning','rejected'=>'danger', default=>'secondary' })

    <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
        @can('contracts.edit')
            @if ($changeOrder->status === 'pending')
                <form method="POST" action="{{ route('change_orders.approve', $changeOrder) }}" class="d-inline" onsubmit="return confirm('تأكيد اعتماد أمر التغيير؟')">
                    @csrf
                    <button class="btn btn-sm btn-outline-success"><i class="fa-solid fa-check ms-1"></i> اعتماد</button>
                </form>
                <form method="POST" action="{{ route('change_orders.reject', $changeOrder) }}" class="d-inline" onsubmit="return confirm('تأكيد رفض أمر التغيير؟')">
                    @csrf
                    <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-xmark ms-1"></i> رفض</button>
                </form>
            @endif
            <a href="{{ route('change_orders.edit', $changeOrder) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
        @endcan
        <a href="{{ route('change_orders.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
                <span class="entity-avatar"><i class="fa-solid fa-file-pen"></i></span>
                <div class="flex-grow-1">
                    <div class="h5 mb-1">{{ $changeOrder->co_number }}</div>
                    <div class="text-muted small">{{ \App\Models\ChangeOrder::TYPES[$changeOrder->change_type] ?? $changeOrder->change_type }} · {{ $changeOrder->title }}</div>
                    <div class="mt-2">
                        <span class="badge text-bg-{{ $badge }}">{{ \App\Models\ChangeOrder::STATUSES[$changeOrder->status] ?? $changeOrder->status }}</span>
                    </div>
                </div>
            </div></div>
        </div>
        <div class="col-lg-7">
            <div class="row g-3 h-100">
                <div class="col-6"><div class="stat-box accent"><div class="sl">قيمة الأمر</div><div class="sv {{ $changeOrder->change_type === 'deduction' ? 'text-danger' : 'text-success' }}">{{ number_format((float) $changeOrder->signedAmount(), 2) }}</div></div></div>
                <div class="col-6"><div class="stat-box"><div class="sl">تاريخ الطلب</div><div class="sv">{{ $changeOrder->request_date?->format('Y-m-d') ?? '—' }}</div></div></div>
            </div>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <h6 class="mb-3"><i class="fa-solid fa-circle-info ms-1" style="color:#8b7355"></i> بيانات الأمر</h6>
        <div class="info-list">
            <div class="il"><span class="k">النوع</span><span class="v">{{ \App\Models\ChangeOrder::TYPES[$changeOrder->change_type] ?? $changeOrder->change_type }}</span></div>
            <div class="il"><span class="k">المشروع</span><span class="v">{{ $changeOrder->project?->name ?? '—' }}</span></div>
            <div class="il"><span class="k">العقد</span><span class="v">{{ $changeOrder->contract?->contract_number ?? '—' }}</span></div>
            <div class="il"><span class="k">القيمة</span><span class="v">{{ number_format((float) $changeOrder->amount, 2) }} ج</span></div>
            <div class="il"><span class="k">تاريخ الطلب</span><span class="v">{{ $changeOrder->request_date?->format('Y-m-d') ?? '—' }}</span></div>
            <div class="il"><span class="k">اعتمده</span><span class="v">{{ $changeOrder->approver?->name ?? '—' }}{{ $changeOrder->approved_at ? ' · '.$changeOrder->approved_at->format('Y-m-d') : '' }}</span></div>
            <div class="il"><span class="k">أُنشئ بواسطة</span><span class="v">{{ $changeOrder->creator?->name ?? '—' }}</span></div>
            @if ($changeOrder->description)<div class="il" style="grid-column:1/-1"><span class="k">الوصف</span><span class="v">{{ $changeOrder->description }}</span></div>@endif
        </div>
    </div></div>
@endsection
