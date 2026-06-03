@extends('layouts.app')

@section('title', 'فاتورة ' . $invoice->invoice_number)

@section('content')
    <style>
        @media print {
            .no-print { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            nav, .navbar, .sidebar, footer { display: none !important; }
            body { background: #fff !important; }
        }
        @page { size: A4; margin: 12mm; }
    </style>

    @php
        $statusColors = [
            'draft' => 'secondary', 'sent' => 'info', 'partial' => 'warning',
            'paid' => 'success', 'overdue' => 'danger', 'cancelled' => 'dark',
        ];
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="m-0">طباعة فاتورة #{{ $invoice->invoice_number }}</h5>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm" style="background:#8b7355;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
            <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
                <div>
                    <h3 class="m-0" style="color:#8b7355">{{ \App\Models\Setting::get('company_name', 'القروانة') }}</h3>
                    @if ($addr = \App\Models\Setting::get('company_address'))<div class="text-muted small">{{ $addr }}</div>@endif
                    @if ($phone = \App\Models\Setting::get('company_phone'))<div class="text-muted small">هاتف: {{ $phone }}</div>@endif
                </div>
                <div class="text-start">
                    <h4 class="m-0">فاتورة</h4>
                    <div class="fw-bold">#{{ $invoice->invoice_number }}</div>
                    <span class="badge bg-{{ $statusColors[$invoice->status] ?? 'secondary' }}">{{ \App\Models\Invoice::STATUSES[$invoice->status] ?? $invoice->status }}</span>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div class="text-muted small">العميل</div>
                    <div class="fw-bold">{{ $invoice->client?->name ?? '—' }}</div>
                    @if ($invoice->project)<div class="text-muted small">المشروع: {{ $invoice->project->name }}</div>@endif
                </div>
                <div class="col-6 text-start">
                    <div>تاريخ الإصدار: <strong>{{ $invoice->issue_date->format('Y-m-d') }}</strong></div>
                    @if ($invoice->due_date)<div>تاريخ الاستحقاق: <strong>{{ $invoice->due_date->format('Y-m-d') }}</strong></div>@endif
                    <div class="text-muted small">{{ \App\Models\Invoice::TYPES[$invoice->invoice_type] ?? '' }}</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>البند</th>
                            <th class="text-end">الكمية</th>
                            <th class="text-end">سعر الوحدة</th>
                            <th class="text-end">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoice->items as $i => $item)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $item->description }}</td>
                                <td class="text-end">{{ number_format($item->quantity, 2) }}</td>
                                <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end fw-semibold">{{ number_format($item->total_price, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">لا توجد بنود.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="row justify-content-end mt-3">
                <div class="col-md-5">
                    <table class="table table-sm">
                        <tr><td class="text-muted">المجموع الفرعي</td><td class="text-end fw-semibold">{{ number_format($invoice->subtotal, 2) }}</td></tr>
                        <tr><td class="text-muted">الضريبة ({{ rtrim(rtrim(number_format($invoice->tax_rate, 2), '0'), '.') }}%)</td><td class="text-end fw-semibold">{{ number_format($invoice->tax_amount, 2) }}</td></tr>
                        <tr class="border-top"><td class="fw-bold">الإجمالي</td><td class="text-end fw-bold fs-5 text-success">{{ number_format($invoice->total_amount, 2) }}</td></tr>
                        <tr><td class="text-muted">المدفوع</td><td class="text-end fw-semibold text-success">{{ number_format($invoice->paid_amount, 2) }}</td></tr>
                        <tr><td class="text-muted">المتبقّي</td><td class="text-end fw-bold text-warning">{{ number_format($invoice->remaining(), 2) }}</td></tr>
                    </table>
                </div>
            </div>

            @if ($invoice->notes)
                <div class="border-top pt-3 mt-2">
                    <div class="text-muted small">ملاحظات</div>
                    <div>{{ $invoice->notes }}</div>
                </div>
            @endif
        </div>
    </div>
@endsection
