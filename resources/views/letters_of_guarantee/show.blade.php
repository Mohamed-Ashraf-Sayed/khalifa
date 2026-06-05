@extends('layouts.app')

@section('title', 'خطاب ضمان: ' . $guarantee->lg_number)

@section('content')
    @php($badge = match($guarantee->status) { 'active'=>'success','released'=>'primary','expired'=>'danger','cancelled'=>'secondary', default=>'secondary' })

    <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
        @can('guarantees.edit')
            @if ($guarantee->status === 'active')
                <form method="POST" action="{{ route('guarantees.release', $guarantee) }}" class="d-inline" onsubmit="return confirm('تأكيد الإفراج عن خطاب الضمان؟')">
                    @csrf
                    <button class="btn btn-sm btn-outline-success"><i class="fa-solid fa-unlock ms-1"></i> إفراج</button>
                </form>
            @endif
            <a href="{{ route('guarantees.edit', $guarantee) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
        @endcan
        <a href="{{ route('guarantees.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
    </div>

    @if ($guarantee->isExpiringSoon())
        <div class="alert alert-warning"><i class="fa-solid fa-clock ms-1"></i> هذا الخطاب قارب على الانتهاء — تاريخ الانتهاء {{ $guarantee->expiry_date->format('Y-m-d') }}.</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
                <span class="entity-avatar"><i class="fa-solid fa-shield-halved"></i></span>
                <div class="flex-grow-1">
                    <div class="h5 mb-1">{{ $guarantee->lg_number }}</div>
                    <div class="text-muted small">{{ \App\Models\LetterOfGuarantee::TYPES[$guarantee->type] ?? $guarantee->type }} · {{ $guarantee->beneficiary }}</div>
                    <div class="mt-2">
                        <span class="badge text-bg-{{ $badge }}">{{ \App\Models\LetterOfGuarantee::STATUSES[$guarantee->status] ?? $guarantee->status }}</span>
                    </div>
                </div>
            </div></div>
        </div>
        <div class="col-lg-7">
            <div class="row g-3 h-100">
                <div class="col-6"><div class="stat-box accent"><div class="sl">قيمة الخطاب</div><div class="sv text-success">{{ number_format((float) $guarantee->amount, 2) }}</div></div></div>
                <div class="col-6"><div class="stat-box"><div class="sl">تاريخ الانتهاء</div><div class="sv {{ $guarantee->isExpiringSoon() ? 'text-warning' : '' }}">{{ $guarantee->expiry_date?->format('Y-m-d') ?? '—' }}</div></div></div>
            </div>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <h6 class="mb-3"><i class="fa-solid fa-circle-info ms-1" style="color:#8b7355"></i> بيانات الخطاب</h6>
        <div class="info-list">
            <div class="il"><span class="k">النوع</span><span class="v">{{ \App\Models\LetterOfGuarantee::TYPES[$guarantee->type] ?? $guarantee->type }}</span></div>
            <div class="il"><span class="k">المستفيد</span><span class="v">{{ $guarantee->beneficiary }}</span></div>
            <div class="il"><span class="k">البنك</span><span class="v">{{ $guarantee->bank_name ?: ($guarantee->bankAccount?->bank_name ?? '—') }}</span></div>
            <div class="il"><span class="k">الحساب البنكي</span><span class="v">{{ $guarantee->bankAccount?->name ?? '—' }}</span></div>
            <div class="il"><span class="k">تاريخ الإصدار</span><span class="v">{{ $guarantee->issue_date?->format('Y-m-d') ?? '—' }}</span></div>
            <div class="il"><span class="k">تاريخ الانتهاء</span><span class="v">{{ $guarantee->expiry_date?->format('Y-m-d') ?? '—' }}</span></div>
            <div class="il"><span class="k">المشروع</span><span class="v">{{ $guarantee->project?->name ?? '—' }}</span></div>
            <div class="il"><span class="k">أُنشئ بواسطة</span><span class="v">{{ $guarantee->creator?->name ?? '—' }}</span></div>
            @if ($guarantee->notes)<div class="il" style="grid-column:1/-1"><span class="k">ملاحظات</span><span class="v">{{ $guarantee->notes }}</span></div>@endif
        </div>
    </div></div>
@endsection
