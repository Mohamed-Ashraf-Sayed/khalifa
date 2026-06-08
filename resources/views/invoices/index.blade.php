@extends('layouts.app')

@section('title', 'الفواتير')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2" method="GET">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بحث برقم الفاتورة">
                    <select name="status" class="form-select" style="min-width:160px" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\Invoice::STATUSES as $k => $label)
                            <option value="{{ $k }}" @selected($status === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                @can('invoices.create')
                    <a href="{{ route('invoices.create') }}" class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-plus ms-1"></i> فاتورة جديدة</a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>رقم الفاتورة</th><th>العميل</th><th>المشروع</th><th>التاريخ</th><th>الإجمالي</th><th>الحالة</th><th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoices as $invoice)
                            @php($badge = match($invoice->status) { 'paid'=>'success','partial'=>'info','sent'=>'primary','overdue'=>'danger','cancelled'=>'secondary',default=>'light' })
                            <tr>
                                <td class="fw-semibold">{{ $invoice->invoice_number }}</td>
                                <td>{{ $invoice->client?->name ?? '—' }}</td>
                                <td>{{ $invoice->project?->name ?? '—' }}</td>
                                <td>{{ $invoice->issue_date->format('Y-m-d') }}</td>
                                <td class="fw-bold">{{ number_format($invoice->total_amount, 2) }}</td>
                                <td><span class="badge text-bg-{{ $badge }}">{{ \App\Models\Invoice::STATUSES[$invoice->status] ?? $invoice->status }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-eye"></i></a>
                                    @can('invoices.edit')
                                        <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('invoices.delete')
                                        <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" class="d-inline" data-confirm="حذف الفاتورة؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">لا توجد فواتير بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $invoices->links() }}
        </div>
    </div>
@endsection
