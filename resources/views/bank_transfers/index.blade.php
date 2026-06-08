@extends('layouts.app')

@section('title', 'التحويلات البنكية')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end gap-2 mb-3">
                @can('bank_accounts.edit')
                    <a href="{{ route('bank_transfers.create') }}" class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-plus ms-1"></i> تحويل جديد</a>
                @endcan
            </div>

            <form method="GET" class="filter-bar row g-2 align-items-end mb-3">
                <div class="col-6 col-md-3">
                    <label class="form-label">من حساب</label>
                    <select name="from_account_id" class="form-select" onchange="this.form.submit()">
                        <option value="">كل الحسابات</option>
                        @foreach ($accounts as $a)
                            <option value="{{ $a->id }}" @selected($fromAccountId == $a->id)>{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">إلى حساب</label>
                    <select name="to_account_id" class="form-select" onchange="this.form.submit()">
                        <option value="">كل الحسابات</option>
                        @foreach ($accounts as $a)
                            <option value="{{ $a->id }}" @selected($toAccountId == $a->id)>{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">من تاريخ</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control" onchange="this.form.submit()">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">إلى تاريخ</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control" onchange="this.form.submit()">
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
                        <tr><th>التاريخ</th><th>من</th><th>إلى</th><th>المبلغ</th><th>الرسوم</th><th>البيان</th><th class="text-end"></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($transfers as $t)
                            <tr>
                                <td>{{ $t->transfer_date->format('Y-m-d') }}</td>
                                <td>{{ $t->fromAccount?->name ?? '—' }}</td>
                                <td>{{ $t->toAccount?->name ?? '—' }}</td>
                                <td class="fw-bold">{{ number_format($t->amount, 2) }}</td>
                                <td>{{ number_format($t->fees, 2) }}</td>
                                <td>{{ $t->description ?: '—' }}</td>
                                <td class="text-end">
                                    @can('bank_accounts.edit')
                                        <form method="POST" action="{{ route('bank_transfers.destroy', $t) }}" class="d-inline" data-confirm="التراجع عن التحويل؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-rotate-left"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">لا توجد تحويلات بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $transfers->links() }}
        </div>
    </div>
@endsection
