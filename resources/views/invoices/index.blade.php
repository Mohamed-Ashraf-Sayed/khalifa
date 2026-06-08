@extends('layouts.app')

@section('title', 'الفواتير')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6">
            <div class="card"><div class="card-body">
                <div class="text-muted small">إجمالي الفواتير</div>
                <div class="fs-4 fw-bold">{{ number_format($totalInvoiced, 2) }} ج</div>
            </div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card"><div class="card-body">
                <div class="text-muted small">إجمالي المُحصّل</div>
                <div class="fs-4 fw-bold text-success">{{ number_format($totalCollected, 2) }} ج</div>
            </div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card"><div class="card-body">
                <div class="text-muted small">المتبقّي</div>
                <div class="fs-4 fw-bold text-danger">{{ number_format($totalOutstanding, 2) }} ج</div>
            </div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card"><div class="card-body">
                <div class="text-muted small">فواتير متأخرة</div>
                <div class="fs-4 fw-bold text-warning">{{ number_format($overdueCount) }}</div>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end gap-2 mb-3">
                @can('invoices.create')
                    <a href="{{ route('invoices.create') }}" class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-plus ms-1"></i> فاتورة جديدة</a>
                @endcan
            </div>

            <form method="GET" class="filter-bar row g-2 align-items-end mb-3">
                <div class="col-6 col-md-3">
                    <label class="form-label">بحث</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="رقم الفاتورة أو العميل">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">العميل</label>
                    <select name="client_id" class="form-select" onchange="this.form.submit()">
                        <option value="">كل العملاء</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" @selected((string) $clientId === (string) $client->id)>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\Invoice::STATUSES as $k => $label)
                            <option value="{{ $k }}" @selected($status === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">من تاريخ</label>
                    <input type="date" name="from" value="{{ $from }}" class="form-control">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">إلى تاريخ</label>
                    <input type="date" name="to" value="{{ $to }}" class="form-control">
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
                        <tr>
                            <th>رقم الفاتورة</th><th>العميل</th><th>المشروع</th><th>التاريخ</th><th>الإجمالي</th><th>المدفوع</th><th>المتبقّي</th><th>الحالة</th><th class="text-end">إجراءات</th>
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
                                <td class="text-success">{{ number_format($invoice->paid_amount, 2) }}</td>
                                <td class="text-danger">{{ number_format($invoice->remaining(), 2) }}</td>
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
                            <tr><td colspan="9" class="text-center text-muted py-4">لا توجد فواتير بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $invoices->links() }}
        </div>
    </div>
@endsection
