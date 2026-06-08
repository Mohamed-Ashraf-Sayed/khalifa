@extends('layouts.app')

@section('title', 'بيانات الحركة')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">حركة شريك <span class="badge text-bg-light">{{ \App\Models\PartnerTransaction::TYPES[$transaction->type] ?? $transaction->type }}</span></h5>
        <div class="d-flex gap-2">
            @can('partners.edit')
                <a href="{{ route('partner_transactions.edit', $transaction) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
            @endcan
            <a href="{{ route('partner_transactions.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">الشريك</div><div class="fw-semibold">{{ $transaction->partner?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">النوع</div><div>{{ \App\Models\PartnerTransaction::TYPES[$transaction->type] ?? $transaction->type }}</div></div>
                <div class="col-md-4"><div class="text-muted small">المبلغ</div><div class="fw-bold">{{ number_format($transaction->amount, 2) }}</div></div>
                <div class="col-md-4"><div class="text-muted small">التاريخ</div><div>{{ $transaction->transaction_date->format('Y-m-d') }}</div></div>
                @php($paymentMethods = ['cash' => 'نقدي', 'bank' => 'تحويل بنكي', 'check' => 'شيك'])
                <div class="col-md-4"><div class="text-muted small">طريقة الدفع</div><div>{{ $paymentMethods[$transaction->payment_method] ?? ($transaction->payment_method ?: '—') }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الحساب البنكي</div><div>{{ $transaction->bankAccount?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">رقم الشيك</div><div>{{ $transaction->check_number ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">أضيفت بواسطة</div><div>{{ $transaction->creator?->name ?? '—' }}</div></div>
                <div class="col-md-12"><div class="text-muted small">الوصف</div><div>{{ $transaction->description ?: '—' }}</div></div>
                @if ($transaction->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $transaction->notes }}</div></div>@endif
            </div>
        </div>
    </div>

    @if ($transaction->partner)
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3">بيانات الشريك</h6>
                <div class="row g-3">
                    <div class="col-md-4"><div class="text-muted small">الاسم</div><div class="fw-semibold">{{ $transaction->partner->name }}</div></div>
                    <div class="col-md-4"><div class="text-muted small">الهاتف</div><div dir="ltr" class="text-end">{{ $transaction->partner->phone ?: '—' }}</div></div>
                    <div class="col-md-4"><div class="text-muted small">الحالة</div><div><span class="badge text-bg-light">{{ \App\Models\Partner::STATUSES[$transaction->partner->status] ?? $transaction->partner->status }}</span></div></div>
                    <div class="col-md-4"><div class="text-muted small">إجمالي رأس المال</div><div>{{ number_format($transaction->partner->totalCapital(), 2) }}</div></div>
                    <div class="col-md-4"><div class="text-muted small">الرصيد الحالي</div><div class="fw-bold">{{ number_format($transaction->partner->currentBalance(), 2) }}</div></div>
                </div>
            </div>
        </div>
    @endif
@endsection
