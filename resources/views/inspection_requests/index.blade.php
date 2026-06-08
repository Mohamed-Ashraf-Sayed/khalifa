@extends('layouts.app')

@section('title', 'طلبات الفحص والمعاينة')

@section('content')
    <div class="row g-3 mb-3">
        @foreach ([
            ['بانتظار الفحص', number_format($stats['pending']), 'fa-clipboard-list', 'text-primary'],
            ['متأخرة', number_format($stats['overdue']), 'fa-triangle-exclamation', 'text-danger'],
            ['مقبولة', number_format($stats['approved']), 'fa-circle-check', 'text-success'],
        ] as [$l, $v, $icon, $color])
        <div class="col-md-4 col-6"><div class="card h-100"><div class="card-body py-3">
            <i class="fa-solid {{ $icon }} {{ $color }}"></i>
            <div class="fs-4 fw-bold">{{ $v }}</div>
            <div class="small text-muted">{{ $l }}</div>
        </div></div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end gap-2 mb-3">
                @can('projects.create')
                    <a href="{{ route('inspection_requests.create') }}" class="btn" style="background:#2b4c80;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> طلب فحص جديد
                    </a>
                @endcan
            </div>

            <form method="GET" class="filter-bar row g-2 align-items-end mb-3">
                <div class="col-6 col-md-3">
                    <label class="form-label">بحث</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بحث بالعنوان أو الرقم">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">المشروع</label>
                    <select name="project_id" class="form-select" onchange="this.form.submit()">
                        <option value="">كل المشاريع</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected((string) $projectId === (string) $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">النوع</label>
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="">كل الأنواع</option>
                        @foreach (\App\Models\InspectionRequest::TYPES as $key => $label)
                            <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\InspectionRequest::STATUSES as $key => $label)
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
                            <th>الرقم</th>
                            <th>العنوان</th>
                            <th>المشروع</th>
                            <th>النوع</th>
                            <th>الموعد المجدول</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inspectionRequests as $inspectionRequest)
                            @php($badge = match($inspectionRequest->status) {
                                'pending' => 'warning', 'approved' => 'success',
                                'rejected' => 'danger', 'closed' => 'secondary', default => 'secondary' })
                            <tr>
                                <td class="fw-semibold">{{ $inspectionRequest->ir_number }}</td>
                                <td>{{ $inspectionRequest->title }}</td>
                                <td>{{ $inspectionRequest->project?->name ?? '—' }}</td>
                                <td>{{ \App\Models\InspectionRequest::TYPES[$inspectionRequest->type] ?? $inspectionRequest->type }}</td>
                                <td>
                                    {{ $inspectionRequest->scheduled_date?->format('Y-m-d') ?? '—' }}
                                    @if ($inspectionRequest->isOverdue())
                                        <i class="fa-solid fa-triangle-exclamation text-danger ms-1" title="متأخر"></i>
                                    @endif
                                </td>
                                <td><span class="badge text-bg-{{ $badge }}">{{ \App\Models\InspectionRequest::STATUSES[$inspectionRequest->status] ?? $inspectionRequest->status }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('inspection_requests.show', $inspectionRequest) }}" class="btn btn-sm btn-outline-secondary" title="عرض">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @can('projects.edit')
                                        <a href="{{ route('inspection_requests.edit', $inspectionRequest) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('projects.delete')
                                        <form method="POST" action="{{ route('inspection_requests.destroy', $inspectionRequest) }}" class="d-inline"
                                              data-confirm="متأكد من حذف طلب الفحص؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">لا توجد طلبات فحص بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $inspectionRequests->links() }}
        </div>
    </div>
@endsection
