@extends('layouts.app')

@section('title', 'عرض سعر ' . $quotation->quotation_number)

@section('content')
    @php($badge = match($quotation->status) { 'accepted'=>'success','sent'=>'info','rejected'=>'danger','expired'=>'secondary',default=>'light' })

    <div class="row g-3 mb-3">
        <div class="col-md-5">
            <div class="card h-100"><div class="card-body">
                <h5 class="mb-1">عرض سعر #{{ $quotation->quotation_number }}</h5>
                <div class="text-muted small mb-2">
                    <span class="badge text-bg-{{ $badge }}">{{ \App\Models\Quotation::STATUSES[$quotation->status] ?? $quotation->status }}</span>
                </div>
                <div>العميل: <strong>{{ $quotation->client?->name ?? '—' }}</strong></div>
                <div>المشروع: {{ $quotation->project?->name ?? '—' }}</div>
                <div>الإصدار: {{ $quotation->issue_date->format('Y-m-d') }} @if($quotation->valid_until) · صالح حتى: {{ $quotation->valid_until->format('Y-m-d') }} @endif</div>
                @if($quotation->notes)<div class="text-muted small mt-2">{{ $quotation->notes }}</div>@endif
            </div></div>
        </div>
        <div class="col-md-7">
            <div class="card h-100"><div class="card-body">
                <div class="row text-center">
                    <div class="col"><div class="text-muted small">المجموع الفرعي</div><div class="fs-5 fw-bold">{{ number_format($quotation->subtotal, 2) }}</div></div>
                    <div class="col"><div class="text-muted small">الضريبة ({{ rtrim(rtrim(number_format($quotation->tax_rate,2),'0'),'.') }}%)</div><div class="fs-5 fw-bold">{{ number_format($quotation->tax_amount, 2) }}</div></div>
                    <div class="col"><div class="text-muted small">الإجمالي</div><div class="fs-4 fw-bold text-success">{{ number_format($quotation->total_amount, 2) }}</div></div>
                </div>
            </div></div>
        </div>
    </div>

    @can('quotations.edit')
    <div class="card mb-3"><div class="card-body">
        <form method="POST" action="{{ route('quotation_items.store', $quotation) }}" class="row g-2 align-items-end">
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
            <h6 class="mb-3">بنود عرض السعر</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light"><tr><th>البند</th><th>الكمية</th><th>سعر الوحدة</th><th>الإجمالي</th><th class="text-end"></th></tr></thead>
                    <tbody>
                        @forelse ($quotation->items as $item)
                            <tr>
                                <td>{{ $item->description }}</td>
                                <td>{{ number_format($item->quantity, 2) }}</td>
                                <td>{{ number_format($item->unit_price, 2) }}</td>
                                <td class="fw-semibold">{{ number_format($item->total_price, 2) }}</td>
                                <td class="text-end">
                                    @can('quotations.edit')
                                        <form method="POST" action="{{ route('quotation_items.destroy', $item) }}" class="d-inline" data-confirm="حذف البند؟">
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

            <a href="{{ route('quotations.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع للعروض</a>

            @if($quotation->converted_invoice_id)
                <a href="{{ route('invoices.show', $quotation->converted_invoice_id) }}" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-file-invoice ms-1"></i> الفاتورة المرتبطة</a>
            @elseif($quotation->status === 'accepted')
                @can('quotations.edit')
                    <form method="POST" action="{{ route('quotations.convert', $quotation) }}" class="d-inline" data-confirm="تحويل عرض السعر إلى فاتورة؟">
                        @csrf
                        <button class="btn btn-sm" style="background:#8b7355;color:#fff"><i class="fa-solid fa-file-invoice ms-1"></i> تحويل إلى فاتورة</button>
                    </form>
                @endcan
            @endif

            <button onclick="window.print()" class="btn btn-sm btn-light"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
        </div>
    </div>
@endsection
