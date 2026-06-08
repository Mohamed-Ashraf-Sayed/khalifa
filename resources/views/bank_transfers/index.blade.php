@extends('layouts.app')

@section('title', 'التحويلات البنكية')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2 flex-wrap" method="GET">
                    <select name="from_account_id" class="form-select" style="min-width:170px" onchange="this.form.submit()">
                        <option value="">من: كل الحسابات</option>
                        @foreach ($accounts as $a)
                            <option value="{{ $a->id }}" @selected($fromAccountId == $a->id)>{{ $a->name }}</option>
                        @endforeach
                    </select>
                    <select name="to_account_id" class="form-select" style="min-width:170px" onchange="this.form.submit()">
                        <option value="">إلى: كل الحسابات</option>
                        @foreach ($accounts as $a)
                            <option value="{{ $a->id }}" @selected($toAccountId == $a->id)>{{ $a->name }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control" style="min-width:150px" title="من تاريخ" onchange="this.form.submit()">
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control" style="min-width:150px" title="إلى تاريخ" onchange="this.form.submit()">
                    <a href="{{ route('bank_transfers.index') }}" class="btn btn-outline-secondary" title="مسح الفلاتر"><i class="fa-solid fa-xmark"></i></a>
                </form>
                @can('bank_accounts.edit')
                    <a href="{{ route('bank_transfers.create') }}" class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-plus ms-1"></i> تحويل جديد</a>
                @endcan
            </div>
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
