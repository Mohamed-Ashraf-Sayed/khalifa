@extends('layouts.app')

@section('title', 'سجل الشيكات')

@section('content')
    @php
        $badges = [
            'pending' => 'secondary',
            'deposited' => 'info',
            'cleared' => 'success',
            'bounced' => 'danger',
            'cancelled' => 'dark',
        ];
    @endphp

    <div class="row g-2 mb-3">
        @foreach (\App\Models\Cheque::STATUSES as $key => $label)
            <div class="col-6 col-md">
                <div class="card text-center h-100 border-{{ $badges[$key] ?? 'secondary' }}">
                    <div class="card-body py-2">
                        <div class="text-muted small">{{ $label }}</div>
                        <div class="fw-bold fs-5">{{ $stats[$key]['count'] ?? 0 }}</div>
                        <div class="small text-muted" dir="ltr">{{ number_format((float) ($stats[$key]['sum'] ?? 0), 2) }} ج</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex flex-wrap gap-2" method="GET">
                    <select name="direction" class="form-select" style="min-width:140px" onchange="this.form.submit()">
                        <option value="">كل الأنواع</option>
                        @foreach (\App\Models\Cheque::DIRECTIONS as $key => $label)
                            <option value="{{ $key }}" @selected($direction === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="form-select" style="min-width:160px" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\Cheque::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control" style="min-width:150px">
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control" style="min-width:150px">
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                @can('bank_accounts.create')
                    <a href="{{ route('cheques.create') }}" class="btn" style="background:#2b4c80;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> شيك جديد
                    </a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>رقم الشيك</th>
                            <th>النوع</th>
                            <th>الطرف</th>
                            <th>الحساب البنكي</th>
                            <th>المبلغ</th>
                            <th>تاريخ الإصدار</th>
                            <th>تاريخ الاستحقاق</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cheques as $cheque)
                            <tr>
                                <td class="fw-semibold" dir="ltr">{{ $cheque->cheque_number }}</td>
                                <td><span class="badge text-bg-{{ $cheque->direction === 'incoming' ? 'success' : 'warning' }}">{{ \App\Models\Cheque::DIRECTIONS[$cheque->direction] ?? $cheque->direction }}</span></td>
                                <td>{{ $cheque->party_name }}</td>
                                <td>{{ $cheque->bankAccount?->name ?? '—' }}</td>
                                <td dir="ltr" class="text-end">{{ number_format($cheque->amount, 2) }} ج</td>
                                <td>{{ $cheque->issue_date?->format('Y-m-d') ?? '—' }}</td>
                                <td>{{ $cheque->due_date?->format('Y-m-d') ?? '—' }}</td>
                                <td><span class="badge text-bg-{{ $badges[$cheque->status] ?? 'secondary' }}">{{ \App\Models\Cheque::STATUSES[$cheque->status] ?? $cheque->status }}</span></td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('cheques.show', $cheque) }}" class="btn btn-sm btn-outline-secondary" title="عرض"><i class="fa-solid fa-eye"></i></a>
                                    @can('bank_accounts.edit')
                                        @if (in_array($cheque->status, ['pending', 'deposited', 'cleared'], true))
                                            <a href="{{ route('cheques.edit', $cheque) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                        @endif
                                    @endcan
                                    @can('bank_accounts.delete')
                                        <form method="POST" action="{{ route('cheques.destroy', $cheque) }}" class="d-inline"
                                              data-confirm="متأكد من حذف الشيك؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">لا توجد شيكات بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $cheques->links() }}
        </div>
    </div>
@endsection
