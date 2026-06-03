@extends('layouts.app')

@section('title', 'كشف حساب — ' . $account->name)

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <div class="text-muted small">الرصيد الحالي</div>
                <div class="fs-4 fw-bold">{{ number_format($account->current_balance, 2) }} {{ $account->currency }}</div>
                <div class="small text-muted mt-1">{{ $account->bank_name }} · افتتاحي: {{ number_format($account->opening_balance, 2) }}</div>
            </div></div>
        </div>
        @can('bank_accounts.edit')
        <div class="col-md-8">
            <div class="card"><div class="card-body">
                <form method="POST" action="{{ route('bank_transactions.store', $account) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-2">
                        <label class="form-label small">النوع</label>
                        <select name="type" class="form-select">
                            <option value="deposit">إيداع</option>
                            <option value="withdrawal">سحب</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">المبلغ</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">التاريخ</label>
                        <input type="date" name="transaction_date" value="{{ date('Y-m-d') }}" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">البيان</label>
                        <input type="text" name="description" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <button class="btn w-100" style="background:#8b7355;color:#fff">إضافة</button>
                    </div>
                </form>
            </div></div>
        </div>
        @endcan
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">الحركات <span class="text-muted small">(مرتّبة بالتاريخ — الرصيد الجاري محسوب لحظياً)</span></h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>التاريخ</th>
                            <th>البيان</th>
                            <th>إيداع</th>
                            <th>سحب</th>
                            <th>الرصيد الجاري</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php($t = $row['txn'])
                            <tr>
                                <td>{{ $t->transaction_date->format('Y-m-d') }}</td>
                                <td>{{ $t->description }}</td>
                                <td class="text-success">{{ $t->type === 'deposit' ? number_format($t->amount, 2) : '' }}</td>
                                <td class="text-danger">{{ $t->type === 'withdrawal' ? number_format($t->amount, 2) : '' }}</td>
                                <td class="fw-semibold">{{ number_format((float) $row['running'], 2) }}</td>
                                <td class="text-end">
                                    @can('bank_accounts.edit')
                                        <form method="POST" action="{{ route('bank_transactions.destroy', $t) }}" class="d-inline" onsubmit="return confirm('حذف الحركة؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">لا توجد حركات بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <a href="{{ route('bank_accounts.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع للحسابات</a>
        </div>
    </div>
@endsection
