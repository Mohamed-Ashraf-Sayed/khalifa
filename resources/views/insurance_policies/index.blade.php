@extends('layouts.app')

@section('title', 'وثائق التأمين')

@section('content')
    <div class="row g-3 mb-3">
        @foreach ([
            ['عدد الوثائق', number_format($stats['count']), 'fa-file-shield', 'text-primary'],
            ['إجمالي التغطية (السارية)', number_format($stats['coverage'], 0), 'fa-shield-halved', 'text-success'],
            ['قاربت على الانتهاء', number_format($stats['expiring']), 'fa-triangle-exclamation', 'text-warning'],
        ] as [$l, $v, $icon, $color])
        <div class="col-md-4 col-6"><div class="statcard {{ str_replace('text-','sc-',$color) }} h-100">
                <span class="sc-ic"><i class="fa-solid {{ $icon }}"></i></span>
                <span><span class="sc-v d-block">{{ $v }}</span><span class="sc-l d-block">{{ $l }}</span></span>
            </div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end gap-2 mb-3">
                @can('guarantees.create')
                    <a href="{{ route('insurance.create') }}" class="btn" style="background:#2b4c80;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> وثيقة جديدة
                    </a>
                @endcan
            </div>

            <form method="GET" class="filter-bar row g-2 align-items-end mb-3">
                <div class="col-6 col-md-3">
                    <label class="form-label">بحث</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="رقم الوثيقة أو جهة التأمين">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\InsurancePolicy::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
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
                            <th>رقم الوثيقة</th>
                            <th>النوع</th>
                            <th>جهة التأمين</th>
                            <th>مبلغ التغطية</th>
                            <th>تاريخ الانتهاء</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($policies as $policy)
                            @php($badge = match($policy->status) {
                                'active' => 'success', 'expired' => 'danger', 'cancelled' => 'secondary', default => 'secondary' })
                            <tr>
                                <td class="fw-semibold">{{ $policy->policy_number }}</td>
                                <td>{{ \App\Models\InsurancePolicy::TYPES[$policy->type] ?? $policy->type }}</td>
                                <td>{{ $policy->provider }}</td>
                                <td>{{ number_format((float) $policy->coverage_amount, 2) }} ج</td>
                                <td>
                                    {{ $policy->expiry_date?->format('Y-m-d') ?? '—' }}
                                    @if ($policy->isExpiringSoon(30))
                                        <i class="fa-solid fa-triangle-exclamation text-warning ms-1" title="قاربت على الانتهاء"></i>
                                    @endif
                                </td>
                                <td><span class="badge text-bg-{{ $badge }}">{{ \App\Models\InsurancePolicy::STATUSES[$policy->status] ?? $policy->status }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('insurance.show', $policy) }}" class="btn btn-sm btn-outline-secondary" title="عرض">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @can('guarantees.edit')
                                        <a href="{{ route('insurance.edit', $policy) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('guarantees.delete')
                                        <form method="POST" action="{{ route('insurance.destroy', $policy) }}" class="d-inline"
                                              data-confirm="متأكد من حذف الوثيقة؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">لا توجد وثائق تأمين بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $policies->links() }}
        </div>
    </div>
@endsection
