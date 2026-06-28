@extends('layouts.app')

@section('title', 'الخزنة النقدية')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-4 col-6">
            <div class="statcard sc-success h-100">
                <span class="sc-ic"><i class="fa-solid fa-vault"></i></span>
                <span><span class="sc-v d-block">{{ number_format((float) $total, 0) }} ج</span><span class="sc-l d-block">إجمالي رصيد الخزائن</span></span>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="statcard sc-primary h-100">
                <span class="sc-ic"><i class="fa-solid fa-boxes-stacked"></i></span>
                <span><span class="sc-v d-block">{{ number_format($treasuries->count()) }}</span><span class="sc-l d-block">عدد الخزائن</span></span>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h6 class="mb-0"><i class="fa-solid fa-vault ms-1" style="color:#2b4c80"></i> الخزائن النقدية</h6>
                @can('bank_accounts.create')
                    <a href="{{ route('bank_accounts.create', ['type' => 'cash']) }}" class="btn" style="background:#2b4c80;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> خزنة جديدة
                    </a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>اسم الخزنة</th>
                            <th>الرصيد الحالي</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($treasuries as $t)
                            <tr role="button" style="cursor:pointer" onclick="window.location='{{ route('bank_accounts.show', $t) }}'">
                                <td class="fw-semibold"><i class="fa-solid fa-vault text-muted ms-1"></i> {{ $t->name }}</td>
                                <td class="fw-bold {{ (float) $t->current_balance < 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float) $t->current_balance, 2) }} ج</td>
                                <td>
                                    @if ($t->is_active)<span class="badge text-bg-success">نشطة</span>@else<span class="badge text-bg-secondary">غير نشطة</span>@endif
                                </td>
                                <td class="text-end" onclick="event.stopPropagation()">
                                    <a href="{{ route('bank_accounts.show', $t) }}" class="btn btn-sm btn-outline-secondary" title="عرض الحركات والمسحوبات"><i class="fa-solid fa-eye"></i></a>
                                    <a href="{{ route('bank_accounts.show', ['bank_account' => $t, 'type' => 'withdrawal']) }}" class="btn btn-sm btn-outline-danger" title="المسحوبات فقط"><i class="fa-solid fa-money-bill-transfer"></i></a>
                                    @can('bank_accounts.edit')
                                        <a href="{{ route('bank_accounts.edit', $t) }}" class="btn btn-sm btn-outline-primary" title="تعديل"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('bank_accounts.delete')
                                        <form method="POST" action="{{ route('bank_accounts.destroy', $t) }}" class="d-inline" data-confirm="حذف الخزنة «{{ $t->name }}»؟ (لا يمكن حذف خزنة بها حركات)">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">
                                لا توجد خزائن بعد.
                                @can('bank_accounts.create')<a href="{{ route('bank_accounts.create', ['type' => 'cash']) }}">أضف خزنة الشركة</a>@endcan
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="small text-muted mt-2">
                <i class="fa-solid fa-circle-info ms-1"></i> اضغط على الخزنة لفتح كشف الحركات وتسجيل <strong>إيداع</strong> أو <strong>سحب</strong>. لتحويل فلوس من البنك للخزنة استخدم «التحويلات البنكية».
            </div>
        </div>
    </div>
@endsection
