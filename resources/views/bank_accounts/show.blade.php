@extends('layouts.app')

@section('title', 'كشف حساب — ' . $account->name)

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">الرصيد الحالي</div>
                <div class="fs-4 fw-bold">{{ number_format($account->current_balance, 2) }} {{ $account->currency }}</div>
                <div class="small text-muted mt-1">{{ $account->bank_name }} · افتتاحي: {{ number_format($account->opening_balance, 2) }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">إجمالي الإيداعات</div>
                <div class="fs-5 fw-bold text-success">{{ number_format((float) $totalDeposits, 2) }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">إجمالي السحوبات</div>
                <div class="fs-5 fw-bold text-danger">{{ number_format((float) $totalWithdrawals, 2) }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">صافي الحركة</div>
                <div class="fs-5 fw-bold">{{ number_format((float) $net, 2) }}</div>
            </div></div>
        </div>
    </div>

    @can('bank_accounts.edit')
    <div class="card mb-3">
        <div class="card-body">
            <h6 class="mb-3">إضافة حركة</h6>
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
                <div class="col-md-2">
                    <label class="form-label small">التاريخ</label>
                    <input type="date" name="transaction_date" value="{{ date('Y-m-d') }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">البيان</label>
                    <input type="text" name="description" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">التصنيف</label>
                    <select name="category" class="form-select">
                        @foreach (\App\Models\BankTransaction::CATEGORIES as $key => $label)
                            <option value="{{ $key }}" @selected($key === 'general')>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">المستفيد</label>
                    <input type="text" name="beneficiary" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">رقم الشيك</label>
                    <input type="text" name="check_number" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">تاريخ القيمة</label>
                    <input type="date" name="value_date" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">المرجع</label>
                    <input type="text" name="reference_number" class="form-control">
                </div>
                <div class="col-md-2">
                    <button class="btn w-100" style="background:#8b7355;color:#fff">إضافة</button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small">من تاريخ</label>
                    <input type="date" name="from" value="{{ $filters['from'] }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">إلى تاريخ</label>
                    <input type="date" name="to" value="{{ $filters['to'] }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">النوع</label>
                    <select name="type" class="form-select">
                        <option value="">الكل</option>
                        @foreach (\App\Models\BankTransaction::TYPES as $key => $label)
                            <option value="{{ $key }}" @selected($filters['type'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">المطابقة</label>
                    <select name="reconciled" class="form-select">
                        <option value="" @selected($filters['reconciled'] === '')>الكل</option>
                        <option value="reconciled" @selected($filters['reconciled'] === 'reconciled')>مطابَق</option>
                        <option value="unreconciled" @selected($filters['reconciled'] === 'unreconciled')>غير مطابَق</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-filter ms-1"></i> فلترة</button>
                    <a href="{{ route('bank_accounts.show', $account) }}" class="btn btn-light">إعادة ضبط</a>
                    <a href="{{ route('bank_accounts.show', array_merge(['bank_account' => $account], request()->query(), ['export' => 'csv'])) }}" class="btn btn-outline-success"><i class="fa-solid fa-file-csv ms-1"></i> CSV</a>
                    <button type="button" class="btn btn-outline-dark" onclick="window.print()"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
                </div>
            </form>
        </div>
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
                            <th>التصنيف</th>
                            <th>إيداع</th>
                            <th>سحب</th>
                            <th>الرصيد الجاري</th>
                            <th>المطابقة</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php($t = $row['txn'])
                            <tr>
                                <td>{{ $t->transaction_date->format('Y-m-d') }}</td>
                                <td>{{ $t->description }}</td>
                                <td><span class="badge text-bg-light">{{ $t->category ? (\App\Models\BankTransaction::CATEGORIES[$t->category] ?? $t->category) : '—' }}</span></td>
                                <td class="text-success">{{ $t->type === 'deposit' ? number_format($t->amount, 2) : '' }}</td>
                                <td class="text-danger">{{ $t->type === 'withdrawal' ? number_format($t->amount, 2) : '' }}</td>
                                <td class="fw-semibold">{{ number_format((float) $row['running'], 2) }}</td>
                                <td>
                                    @if ($t->is_reconciled)
                                        <span class="badge text-bg-success">تمت المطابقة</span>
                                    @else
                                        <span class="badge text-bg-secondary">غير مطابَق</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    @can('bank_accounts.edit')
                                        <form method="POST" action="{{ route('bank_transactions.reconcile', $t) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-{{ $t->is_reconciled ? 'secondary' : 'success' }}" title="{{ $t->is_reconciled ? 'إلغاء المطابقة' : 'تمت المطابقة' }}">
                                                <i class="fa-solid fa-{{ $t->is_reconciled ? 'rotate-left' : 'check' }}"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('bank_transactions.destroy', $t) }}" class="d-inline" onsubmit="return confirm('حذف الحركة؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">لا توجد حركات مطابقة للفلاتر.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <a href="{{ route('bank_accounts.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع للحسابات</a>
        </div>
    </div>
@endsection
