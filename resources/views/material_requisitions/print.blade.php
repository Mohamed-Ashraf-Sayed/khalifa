@extends('layouts.app')

@section('title', 'إذن صرف ' . $requisition->requisition_number)

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

    @php($badge = match($requisition->status) {
        'approved' => 'primary', 'issued' => 'success',
        'rejected' => 'danger', 'pending' => 'warning', default => 'secondary' })

    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="m-0">طباعة إذن صرف #{{ $requisition->requisition_number }}</h5>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
            <a href="{{ route('material_requisitions.show', $requisition) }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
                <div>
                    <h3 class="m-0" style="color:#2b4c80">{{ \App\Models\Setting::get('company_name', 'القروانة') }}</h3>
                    @if ($addr = \App\Models\Setting::get('company_address'))<div class="text-muted small">{{ $addr }}</div>@endif
                    @if ($phone = \App\Models\Setting::get('company_phone'))<div class="text-muted small">هاتف: {{ $phone }}</div>@endif
                </div>
                <div class="text-start">
                    <h4 class="m-0">إذن صرف مواد</h4>
                    <div class="fw-bold">#{{ $requisition->requisition_number }}</div>
                    <span class="badge bg-{{ $badge }}">{{ \App\Models\MaterialRequisition::STATUSES[$requisition->status] ?? $requisition->status }}</span>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div class="text-muted small">المشروع</div>
                    <div class="fw-bold">{{ $requisition->project?->name ?? 'عام' }}</div>
                    <div class="text-muted small">أنشأه: {{ $requisition->creator?->name ?? '—' }}</div>
                </div>
                <div class="col-6 text-start">
                    <div>تاريخ الطلب: <strong>{{ $requisition->request_date?->format('Y-m-d') ?? '—' }}</strong></div>
                    @if ($requisition->approver)
                        <div>اعتمده: <strong>{{ $requisition->approver->name }}</strong></div>
                        <div class="text-muted small">{{ $requisition->approved_at?->format('Y-m-d') }}</div>
                    @endif
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>الصنف</th>
                            <th>الوحدة</th>
                            <th class="text-end">الكمية المطلوبة</th>
                            <th class="text-end">الكمية المصروفة</th>
                            <th>ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requisition->items as $i => $item)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $item->material?->name ?? '—' }}</td>
                                <td>{{ $item->material?->unit ?? '—' }}</td>
                                <td class="text-end">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
                                <td class="text-end fw-semibold">{{ rtrim(rtrim(number_format($item->issued_quantity, 2), '0'), '.') }}</td>
                                <td>{{ $item->notes ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">لا توجد أصناف.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($requisition->notes)
                <div class="border-top pt-3 mt-2">
                    <div class="text-muted small">ملاحظات</div>
                    <div>{{ $requisition->notes }}</div>
                </div>
            @endif

            <div class="row mt-5 pt-4">
                <div class="col-4 text-center">
                    <div class="border-top pt-2 mx-3">توقيع المستلِم</div>
                </div>
                <div class="col-4 text-center">
                    <div class="border-top pt-2 mx-3">توقيع أمين المخزن</div>
                </div>
                <div class="col-4 text-center">
                    <div class="border-top pt-2 mx-3">توقيع المعتمِد</div>
                </div>
            </div>
        </div>
    </div>
@endsection
