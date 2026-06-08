@extends('layouts.app')

@section('title', 'بيانات الإيراد')

@php
    $statusColors = ['pending' => 'secondary', 'partial' => 'warning', 'collected' => 'success'];
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">{{ $revenue->description }}</h5>
        <div class="d-flex gap-2">
            @can('revenues.edit')
                <form method="POST" action="{{ route('revenues.confirm', $revenue) }}" class="d-inline">
                    @csrf
                    @if ($revenue->is_confirmed)
                        <button class="btn btn-sm btn-outline-warning"><i class="fa-solid fa-rotate-left ms-1"></i> إلغاء التأكيد</button>
                    @else
                        <button class="btn btn-sm" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-circle-check ms-1"></i> تأكيد الإيراد</button>
                    @endif
                </form>
                <a href="{{ route('revenues.edit', $revenue) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
            @endcan
            <a href="{{ route('revenues.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">البيان</div><div class="fw-semibold">{{ $revenue->description }}</div></div>
                <div class="col-md-4"><div class="text-muted small">المبلغ</div><div class="fw-bold text-success">{{ number_format($revenue->amount, 2) }} ج</div></div>
                <div class="col-md-4"><div class="text-muted small">التاريخ</div><div>{{ $revenue->revenue_date->format('Y-m-d') }}</div></div>
                <div class="col-md-4"><div class="text-muted small">المشروع</div><div>{{ $revenue->project?->name ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">مركز التكلفة</div><div>{{ $revenue->costCenter?->name ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">طريقة الاستلام</div><div>{{ \App\Models\Revenue::PAYMENT_METHODS[$revenue->payment_method] ?? $revenue->payment_method }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الحساب البنكي</div><div>{{ $revenue->bankAccount?->name ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ الاستحقاق</div><div>{{ $revenue->due_date?->format('Y-m-d') ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">رقم الشيك</div><div>{{ $revenue->check_number ?: '—' }} @if($revenue->deferred_check)<span class="badge bg-info">شيك آجل</span>@endif</div></div>
                <div class="col-md-4"><div class="text-muted small">سُجّل بواسطة</div><div>{{ $revenue->creator?->name ?: '—' }}</div></div>
                <div class="col-md-4">
                    <div class="text-muted small">حالة التأكيد</div>
                    <div>
                        @if ($revenue->is_confirmed)
                            <span class="badge bg-success">مؤكد</span>
                        @else
                            <span class="badge bg-secondary">قيد التأكيد</span>
                        @endif
                    </div>
                </div>
                @if ($revenue->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $revenue->notes }}</div></div>@endif
            </div>
            <hr>
            <div class="row g-3 text-center align-items-center">
                <div class="col-md-3">
                    <div class="text-muted small">حالة التحصيل</div>
                    <span class="badge bg-{{ $statusColors[$revenue->payment_status] ?? 'secondary' }}">{{ \App\Models\Revenue::PAYMENT_STATUSES[$revenue->payment_status] ?? $revenue->payment_status }}</span>
                </div>
                <div class="col-md-3"><div class="text-muted small">المبلغ</div><div class="fs-5 fw-bold">{{ number_format($revenue->amount, 2) }}</div></div>
                <div class="col-md-3"><div class="text-muted small">المحصّل</div><div class="fs-5 fw-bold text-success">{{ number_format($revenue->paid_amount, 2) }}</div></div>
                <div class="col-md-3"><div class="text-muted small">المتبقّي</div><div class="fs-5 fw-bold text-warning">{{ number_format($revenue->remaining(), 2) }}</div></div>
            </div>
        </div>
    </div>

    @if ($revenue->bank_account_id)
        <div class="alert alert-info"><i class="fa-solid fa-circle-info ms-1"></i> إيراد محصّل مباشرة في الحساب البنكي.</div>

        <div class="card">
            <div class="card-body">
                <h6 class="mb-3">الحساب البنكي المرتبط</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>الحساب</th><th>البنك</th><th>رقم الحساب</th></tr></thead>
                        <tbody>
                            <tr>
                                <td>{{ $revenue->bankAccount->name }}</td>
                                <td>{{ $revenue->bankAccount->bank_name ?: '—' }}</td>
                                <td dir="ltr" class="text-end">{{ $revenue->bankAccount->account_number ?: '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        @can('revenues.edit')
        <div class="card mb-3"><div class="card-body">
            <h6 class="mb-3">تسجيل تحصيل</h6>
            <form method="POST" action="{{ route('revenue_collections.store', $revenue) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-2">
                    <label class="form-label small">المبلغ <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $revenue->remaining()) }}" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">التاريخ <span class="text-danger">*</span></label>
                    <input type="date" name="collection_date" value="{{ old('collection_date', now()->toDateString()) }}" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">طريقة الاستلام</label>
                    <select name="payment_method" class="form-select">
                        @foreach (\App\Models\Revenue::PAYMENT_METHODS as $k => $label)
                            <option value="{{ $k }}" @selected(old('payment_method') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">إيداع في حساب</label>
                    <select name="bank_account_id" class="form-select">
                        <option value="">— بدون —</option>
                        @foreach ($accounts as $a)
                            <option value="{{ $a->id }}" @selected((int) old('bank_account_id') === $a->id)>{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">رقم الشيك</label>
                    <input type="text" name="check_number" value="{{ old('check_number') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <button class="btn w-100" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-plus ms-1"></i> تحصيل</button>
                </div>
                <div class="col-12">
                    <input type="text" name="notes" value="{{ old('notes') }}" class="form-control form-control-sm" placeholder="ملاحظات (اختياري)">
                </div>
            </form>
        </div></div>
        @endcan

        <div class="card">
            <div class="card-body">
                <h6 class="mb-3">التحصيلات</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light"><tr><th>التاريخ</th><th>المبلغ</th><th>الطريقة</th><th>الحساب البنكي</th><th>رقم الشيك</th><th>ملاحظات</th><th class="text-end"></th></tr></thead>
                        <tbody>
                            @forelse ($revenue->collections as $collection)
                                <tr>
                                    <td>{{ $collection->collection_date->format('Y-m-d') }}</td>
                                    <td class="fw-semibold text-success">{{ number_format($collection->amount, 2) }}</td>
                                    <td>{{ \App\Models\Revenue::PAYMENT_METHODS[$collection->payment_method] ?? $collection->payment_method }}</td>
                                    <td>{{ $collection->bankAccount?->name ?: '—' }}</td>
                                    <td>{{ $collection->check_number ?: '—' }}</td>
                                    <td>{{ $collection->notes ?: '—' }}</td>
                                    <td class="text-end">
                                        @can('revenues.edit')
                                            <form method="POST" action="{{ route('revenue_collections.destroy', $collection) }}" class="d-inline" data-confirm="حذف التحصيل؟">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-3">لا توجد تحصيلات بعد.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
