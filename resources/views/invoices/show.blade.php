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
            <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="btn btn-sm" style="background:#8b7355;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</a>

            @can('invoices.view')
                @if($invoice->client?->email)
                    <form method="POST" action="{{ route('invoices.email', $invoice) }}" class="d-inline" onsubmit="return confirm('إرسال الفاتورة إلى بريد العميل؟')">
                        @csrf
                        <button class="btn btn-sm btn-primary"><i class="fa-solid fa-envelope ms-1"></i> إرسال بالبريد</button>
                    </form>
                @endif
            @endcan

            @if($invoice->client?->phone)
                @php
                    $waPhone = preg_replace('/\D+/', '', $invoice->client->phone);
                    if (str_starts_with($waPhone, '0')) {
                        $waPhone = '20' . substr($waPhone, 1);
                    }
                    $waCompany = \App\Models\Setting::get('company_name', 'القروانة');
                    $waText = 'فاتورة رقم ' . $invoice->invoice_number . ' بقيمة ' . number_format($invoice->total_amount, 2) . ' ج.م من شركة ' . $waCompany . '. المتبقّي: ' . number_format($invoice->remaining(), 2) . ' ج.م';
                @endphp
                <a href="https://wa.me/{{ $waPhone }}?text={{ urlencode($waText) }}" target="_blank" class="btn btn-sm btn-success"><i class="fa-brands fa-whatsapp ms-1"></i> واتساب</a>
            @endif
        </div>
    </div>

    @php
        $statusColors = [
            'draft' => 'secondary', 'sent' => 'info', 'partial' => 'warning',
            'paid' => 'success', 'overdue' => 'danger', 'cancelled' => 'dark',
        ];
    @endphp

    <div class="card mt-3"><div class="card-body">
        <h6 class="mb-3">الدفعات والتحصيل</h6>

        <div class="row text-center mb-3">
            <div class="col"><div class="text-muted small">الإجمالي</div><div class="fs-5 fw-bold">{{ number_format($invoice->total_amount, 2) }}</div></div>
            <div class="col"><div class="text-muted small">المدفوع</div><div class="fs-5 fw-bold text-success">{{ number_format($invoice->paid_amount, 2) }}</div></div>
            <div class="col"><div class="text-muted small">المتبقّي</div><div class="fs-5 fw-bold text-warning">{{ number_format($invoice->remaining(), 2) }}</div></div>
            <div class="col"><div class="text-muted small">الحالة</div><div><span class="badge bg-{{ $statusColors[$invoice->status] ?? 'secondary' }}">{{ \App\Models\Invoice::STATUSES[$invoice->status] ?? $invoice->status }}</span></div></div>
        </div>

        @can('invoices.edit')
        <form method="POST" action="{{ route('invoice_payments.store', $invoice) }}" class="row g-2 align-items-end border-top pt-3">
            @csrf
            <div class="col-md-2"><label class="form-label small">المبلغ <span class="text-danger">*</span></label><input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" class="form-control" required></div>
            <div class="col-md-2"><label class="form-label small">التاريخ <span class="text-danger">*</span></label><input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" class="form-control" required></div>
            <div class="col-md-2">
                <label class="form-label small">طريقة الدفع</label>
                <select name="payment_method" class="form-select">
                    @foreach (\App\Models\Invoice::PAYMENT_METHODS as $k => $label)
                        <option value="{{ $k }}" @selected(old('payment_method') === $k)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">حساب بنكي</label>
                <select name="bank_account_id" class="form-select">
                    <option value="">— بدون —</option>
                    @foreach ($accounts as $acc)
                        <option value="{{ $acc->id }}" @selected((int) old('bank_account_id') === $acc->id)>{{ $acc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><label class="form-label small">المرجع</label><input type="text" name="reference_number" value="{{ old('reference_number') }}" class="form-control"></div>
            <div class="col-md-2"><button class="btn w-100" style="background:#8b7355;color:#fff"><i class="fa-solid fa-plus ms-1"></i> تسجيل دفعة</button></div>
            <div class="col-12"><input type="text" name="notes" value="{{ old('notes') }}" class="form-control" placeholder="ملاحظات (اختياري)"></div>
        </form>
        @endcan

        <div class="table-responsive mt-3">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light"><tr><th>التاريخ</th><th>المبلغ</th><th>طريقة الدفع</th><th>الحساب البنكي</th><th>المرجع</th><th>ملاحظات</th><th class="text-end"></th></tr></thead>
                <tbody>
                    @forelse ($invoice->payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date?->format('Y-m-d') }}</td>
                            <td class="fw-semibold">{{ number_format($payment->amount, 2) }}</td>
                            <td>{{ \App\Models\Invoice::PAYMENT_METHODS[$payment->payment_method] ?? $payment->payment_method }}</td>
                            <td>{{ $payment->bankAccount?->name ?? '—' }}</td>
                            <td>{{ $payment->reference_number ?? '—' }}</td>
                            <td>{{ $payment->notes ?? '—' }}</td>
                            <td class="text-end">
                                @can('invoices.edit')
                                    <form method="POST" action="{{ route('invoice_payments.destroy', $payment) }}" class="d-inline" onsubmit="return confirm('حذف الدفعة؟')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-3">لا توجد دفعات بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div></div>

    @include('partials.attachments', ['model' => $invoice])
@endsection
