@extends('layouts.app')

@section('title', 'بيانات الشيك')

@section('content')
    @php
        $badges = [
            'pending' => 'secondary',
            'deposited' => 'info',
            'cleared' => 'success',
            'bounced' => 'danger',
            'cancelled' => 'dark',
        ];
        $badge = $badges[$cheque->status] ?? 'secondary';
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">شيك رقم <span dir="ltr">{{ $cheque->cheque_number }}</span></h5>
        <div class="d-flex gap-2">
            @can('bank_accounts.edit')
                @if (in_array($cheque->status, ['pending', 'deposited', 'cleared'], true))
                    <a href="{{ route('cheques.edit', $cheque) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
                @endif
            @endcan
            <a href="{{ route('cheques.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">رقم الشيك</div><div class="fw-semibold" dir="ltr">{{ $cheque->cheque_number }}</div></div>
                <div class="col-md-4"><div class="text-muted small">النوع</div><div><span class="badge text-bg-{{ $cheque->direction === 'incoming' ? 'success' : 'warning' }}">{{ \App\Models\Cheque::DIRECTIONS[$cheque->direction] ?? $cheque->direction }}</span></div></div>
                <div class="col-md-4"><div class="text-muted small">الحالة</div><div><span class="badge text-bg-{{ $badge }}">{{ \App\Models\Cheque::STATUSES[$cheque->status] ?? $cheque->status }}</span></div></div>
                <div class="col-md-4"><div class="text-muted small">اسم الطرف</div><div class="fw-semibold">{{ $cheque->party_name }}</div></div>
                <div class="col-md-4"><div class="text-muted small">المبلغ</div><div class="fw-bold">{{ number_format($cheque->amount, 2) }} ج</div></div>
                <div class="col-md-4"><div class="text-muted small">الحساب البنكي</div><div>{{ $cheque->bankAccount?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ الإصدار</div><div>{{ $cheque->issue_date?->format('Y-m-d') ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ الاستحقاق</div><div>{{ $cheque->due_date?->format('Y-m-d') ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">أُنشئ بواسطة</div><div>{{ $cheque->creator?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ الإنشاء</div><div>{{ $cheque->created_at?->format('Y-m-d') ?? '—' }}</div></div>
                @if ($cheque->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $cheque->notes }}</div></div>@endif
            </div>
        </div>
    </div>

    @can('bank_accounts.edit')
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3"><i class="fa-solid fa-arrows-rotate ms-1"></i> دورة حياة الشيك</h6>
                <div class="d-flex flex-wrap gap-2">
                    @if ($cheque->status === 'pending')
                        <form method="POST" action="{{ route('cheques.deposited', $cheque) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-info"><i class="fa-solid fa-building-columns ms-1"></i> تعليم كمودع</button>
                        </form>
                    @endif

                    @if (in_array($cheque->status, ['pending', 'deposited'], true))
                        <form method="POST" action="{{ route('cheques.cleared', $cheque) }}" class="d-inline"
                              onsubmit="return confirm('تأكيد تحصيل الشيك؟ سيتم تسجيل قيد بنكي إن وُجد حساب.')">
                            @csrf
                            <button class="btn btn-sm btn-outline-success"><i class="fa-solid fa-circle-check ms-1"></i> تحصيل</button>
                        </form>
                    @endif

                    @if (in_array($cheque->status, ['pending', 'deposited', 'cleared'], true))
                        <form method="POST" action="{{ route('cheques.bounced', $cheque) }}" class="d-inline"
                              onsubmit="return confirm('تأكيد ارتداد الشيك؟ سيتم حذف القيد البنكي المرتبط إن وُجد.')">
                            @csrf
                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-circle-xmark ms-1"></i> ارتداد</button>
                        </form>
                    @endif

                    @if (! in_array($cheque->status, ['pending', 'deposited', 'cleared'], true))
                        <span class="text-muted small align-self-center">لا توجد إجراءات متاحة لهذه الحالة.</span>
                    @endif
                </div>
            </div>
        </div>
    @endcan
@endsection
