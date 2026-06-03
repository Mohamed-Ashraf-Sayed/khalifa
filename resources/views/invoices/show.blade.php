@extends('layouts.app')

@section('title', 'فاتورة ' . $invoice->invoice_number)

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-5">
            <div class="card h-100"><div class="card-body">
                <h5 class="mb-1">فاتورة #{{ $invoice->invoice_number }}</h5>
                <div class="text-muted small mb-2">{{ \App\Models\Invoice::TYPES[$invoice->invoice_type] ?? '' }} · {{ \App\Models\Invoice::STATUSES[$invoice->status] ?? '' }}</div>
                <div>العميل: <strong>{{ $invoice->client?->name ?? '—' }}</strong></div>
                <div>المشروع: {{ $invoice->project?->name ?? '—' }}</div>
                <div>الإصدار: {{ $invoice->issue_date->format('Y-m-d') }} @if($invoice->due_date) · الاستحقاق: {{ $invoice->due_date->format('Y-m-d') }} @endif</div>
            </div></div>
        </div>
        <div class="col-md-7">
            <div class="card h-100"><div class="card-body">
                <div class="row text-center">
                    <div class="col"><div class="text-muted small">المجموع الفرعي</div><div class="fs-5 fw-bold">{{ number_format($invoice->subtotal, 2) }}</div></div>
                    <div class="col"><div class="text-muted small">الضريبة ({{ rtrim(rtrim(number_format($invoice->tax_rate,2),'0'),'.') }}%)</div><div class="fs-5 fw-bold">{{ number_format($invoice->tax_amount, 2) }}</div></div>
                    <div class="col"><div class="text-muted small">الإجمالي</div><div class="fs-4 fw-bold text-success">{{ number_format($invoice->total_amount, 2) }}</div></div>
                </div>
            </div></div>
        </div>
    </div>

    @can('invoices.edit')
    <div class="card mb-3"><div class="card-body">
        <form method="POST" action="{{ route('invoice_items.store', $invoice) }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-5"><label class="form-label small">البند</label><input type="text" name="description" class="form-control" required></div>
            <div class="col-md-2"><label class="form-label small">الكمية</label><input type="number" step="0.01" min="0.01" name="quantity" value="1" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label small">سعر الوحدة</label><input type="number" step="0.01" min="0" name="unit_price" class="form-control" required></div>
            <div class="col-md-2"><button class="btn w-100" style="background:#8b7355;color:#fff">إضافة بند</button></div>
        </form>
    </div></div>
    @endcan

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">بنود الفاتورة</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light"><tr><th>البند</th><th>الكمية</th><th>سعر الوحدة</th><th>الإجمالي</th><th class="text-end"></th></tr></thead>
                    <tbody>
                        @forelse ($invoice->items as $item)
                            <tr>
                                <td>{{ $item->description }}</td>
                                <td>{{ number_format($item->quantity, 2) }}</td>
                                <td>{{ number_format($item->unit_price, 2) }}</td>
                                <td class="fw-semibold">{{ number_format($item->total_price, 2) }}</td>
                                <td class="text-end">
                                    @can('invoices.edit')
                                        <form method="POST" action="{{ route('invoice_items.destroy', $item) }}" class="d-inline" onsubmit="return confirm('حذف البند؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">لا توجد بنود. أضِف بنداً من الأعلى.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <a href="{{ route('invoices.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع للفواتير</a>
        </div>
    </div>
@endsection
