@extends('layouts.app')

@section('title', 'الحسابات البنكية')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="statcard sc-primary h-100"><span class="sc-ic"><i class="fa-solid fa-building-columns"></i></span><span><span class="sc-v d-block">{{ number_format($total, 2) }} ج</span><span class="sc-l d-block">إجمالي أرصدة الحسابات النشطة</span></span></div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="filter-bar row g-2 align-items-end mb-3">
                <div class="col-6 col-md-3">
                    <label class="form-label">بحث</label>
                    <input type="text" name="search" value="{{ $filters['search'] }}" class="form-control">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select">
                        <option value="" @selected($filters['status'] === '')>الكل</option>
                        <option value="active" @selected($filters['status'] === 'active')>نشط</option>
                        <option value="inactive" @selected($filters['status'] === 'inactive')>معطّل</option>
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
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end mb-3">
                @can('bank_accounts.create')
                    <a href="{{ route('bank_accounts.create') }}" class="btn" style="background:#2b4c80;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> حساب جديد
                    </a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>الحساب</th>
                            <th>البنك</th>
                            <th>رقم الحساب</th>
                            <th>نوع الحساب</th>
                            <th>الرصيد الحالي</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($accounts as $account)
                            <tr>
                                <td class="fw-semibold">
                                    @can('bank_accounts.view')
                                        <a href="{{ route('bank_accounts.show', $account) }}" class="text-decoration-none">{{ $account->name }}</a>
                                    @else
                                        {{ $account->name }}
                                    @endcan
                                </td>
                                <td>{{ $account->bank_name }}</td>
                                <td>{{ $account->account_number ?: '—' }}</td>
                                <td>{{ $account->account_type ? (\App\Models\BankAccount::ACCOUNT_TYPES[$account->account_type] ?? $account->account_type) : '—' }}</td>
                                <td class="fw-bold">{{ number_format($account->current_balance, 2) }} {{ $account->currency }}</td>
                                <td><span class="badge text-bg-{{ $account->is_active ? 'success' : 'secondary' }}">{{ $account->is_active ? 'نشط' : 'معطّل' }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('bank_accounts.show', $account) }}" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-list ms-1"></i> كشف حساب</a>
                                    @can('bank_accounts.edit')
                                        <a href="{{ route('bank_accounts.edit', $account) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('bank_accounts.delete')
                                        <form method="POST" action="{{ route('bank_accounts.destroy', $account) }}" class="d-inline" data-confirm="متأكد من حذف الحساب؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">لا توجد حسابات بنكية بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $accounts->links() }}
        </div>
    </div>
@endsection
