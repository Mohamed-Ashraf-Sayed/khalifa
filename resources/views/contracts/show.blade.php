@extends('layouts.app')

@section('title', 'بيانات العقد')

@section('content')
    @php($badge = match($contract->status) {
        'completed' => 'success', 'active' => 'primary',
        'suspended' => 'warning', 'cancelled' => 'danger', default => 'secondary' })

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">{{ $contract->title }}</h5>
        <div class="d-flex gap-2">
            @can('contracts.edit')
                <a href="{{ route('contracts.edit', $contract) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
            @endcan
            <a href="{{ route('contracts.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">رقم العقد</div><div class="fw-semibold">{{ $contract->contract_number }}</div></div>
                <div class="col-md-4"><div class="text-muted small">العنوان</div><div>{{ $contract->title }}</div></div>
                <div class="col-md-4"><div class="text-muted small">المشروع</div><div>{{ $contract->project?->name ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">نوع العقد</div><div>{{ \App\Models\ProjectContract::TYPES[$contract->contract_type] ?? $contract->contract_type }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الحالة</div><div><span class="badge text-bg-{{ $badge }}">{{ \App\Models\ProjectContract::STATUSES[$contract->status] ?? $contract->status }}</span></div></div>
                <div class="col-md-4"><div class="text-muted small">قيمة العقد</div><div class="fw-semibold">{{ number_format($contract->contract_value, 2) }} ج</div></div>
                <div class="col-md-4"><div class="text-muted small">الطرف الأول</div><div>{{ $contract->first_party }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الطرف الثاني</div><div>{{ $contract->second_party }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ التوقيع</div><div>{{ $contract->signing_date?->format('Y-m-d') ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ البدء</div><div>{{ $contract->start_date?->format('Y-m-d') ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ الانتهاء</div><div>{{ $contract->end_date?->format('Y-m-d') ?? '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">أُنشئ بواسطة</div><div>{{ $contract->creator?->name ?? '—' }}</div></div>
                @if ($contract->description)<div class="col-12"><div class="text-muted small">الوصف</div><div>{{ $contract->description }}</div></div>@endif
                @if ($contract->notes)<div class="col-12"><div class="text-muted small">ملاحظات</div><div>{{ $contract->notes }}</div></div>@endif
            </div>
        </div>
    </div>

    @if ($contract->project)
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3">المشروع المرتبط</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>المشروع</th><th>قيمة العقد</th><th>الحالة</th></tr></thead>
                        <tbody>
                            <tr>
                                <td>{{ $contract->project->name }}</td>
                                <td>{{ number_format($contract->project->contract_value, 2) }}</td>
                                <td><span class="badge text-bg-light">{{ \App\Models\Project::STATUSES[$contract->project->status] ?? $contract->project->status }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
