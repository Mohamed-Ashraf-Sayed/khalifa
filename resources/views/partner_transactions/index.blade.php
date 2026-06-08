@extends('layouts.app')

@section('title', 'حركات الشركاء')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form method="GET">
                    <select name="type" class="form-select" style="min-width:180px" onchange="this.form.submit()">
                        <option value="">كل الأنواع</option>
                        @foreach (\App\Models\PartnerTransaction::TYPES as $k => $label)
                            <option value="{{ $k }}" @selected($type === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
                @can('partners.create')
                    <a href="{{ route('partner_transactions.create') }}" class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-plus ms-1"></i> حركة جديدة</a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>التاريخ</th>
                            <th>الشريك</th>
                            <th>النوع</th>
                            <th>المبلغ</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->transaction_date->format('Y-m-d') }}</td>
                                <td class="fw-semibold">{{ $transaction->partner?->name ?? '—' }}</td>
                                <td><span class="badge text-bg-light">{{ \App\Models\PartnerTransaction::TYPES[$transaction->type] ?? $transaction->type }}</span></td>
                                <td class="fw-bold">{{ number_format($transaction->amount, 2) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('partner_transactions.show', $transaction) }}" class="btn btn-sm btn-outline-secondary" title="عرض"><i class="fa-solid fa-eye"></i></a>
                                    @can('partners.edit')
                                        <a href="{{ route('partner_transactions.edit', $transaction) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('partners.delete')
                                        <form method="POST" action="{{ route('partner_transactions.destroy', $transaction) }}" class="d-inline" data-confirm="حذف الحركة؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">لا توجد حركات بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $transactions->links() }}
        </div>
    </div>
@endsection
