@extends('layouts.app')

@section('title', 'بيانات العميل')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">{{ $client->name }}</h5>
        <div class="d-flex gap-2">
            <a href="{{ route('clients.statement', $client) }}" class="btn btn-sm" style="background:#8b7355;color:#fff"><i class="fa-solid fa-file-invoice-dollar ms-1"></i> كشف حساب</a>
            @can('clients.edit')
                <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
            @endcan
            <a href="{{ route('clients.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">الاسم</div><div class="fw-semibold">{{ $client->name }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الشركة</div><div>{{ $client->company_name ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الهاتف</div><div dir="ltr" class="text-end">{{ $client->phone }}</div></div>
                <div class="col-md-4"><div class="text-muted small">هاتف آخر</div><div dir="ltr" class="text-end">{{ $client->phone2 ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">البريد</div><div dir="ltr" class="text-end">{{ $client->email ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">المدينة</div><div>{{ $client->city ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الرقم الضريبي</div><div>{{ $client->tax_number ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">السجل التجاري</div><div>{{ $client->commercial_register ?: '—' }}</div></div>
                <div class="col-md-12"><div class="text-muted small">العنوان</div><div>{{ $client->address ?: '—' }}</div></div>
                @if ($client->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $client->notes }}</div></div>@endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">مشاريع العميل <span class="badge text-bg-light">{{ $client->projects->count() }}</span></h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>المشروع</th><th>قيمة العقد</th><th>الحالة</th></tr></thead>
                    <tbody>
                        @forelse ($client->projects as $p)
                            <tr>
                                <td>{{ $p->name }}</td>
                                <td>{{ number_format($p->contract_value, 2) }}</td>
                                <td><span class="badge text-bg-light">{{ \App\Models\Project::STATUSES[$p->status] ?? $p->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">لا توجد مشاريع لهذا العميل.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
