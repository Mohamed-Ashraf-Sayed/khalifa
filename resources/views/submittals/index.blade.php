@extends('layouts.app')

@section('title', 'الاعتمادات الفنية')

@section('content')
    <div class="row g-3 mb-3">
        @foreach ([
            ['مقدّمة', number_format($stats['submitted']), 'fa-stamp', 'text-primary'],
            ['قيد المراجعة', number_format($stats['under_review']), 'fa-hourglass-half', 'text-warning'],
            ['معتمدة', number_format($stats['approved']), 'fa-circle-check', 'text-success'],
            ['متأخرة', number_format($stats['overdue']), 'fa-triangle-exclamation', 'text-danger'],
        ] as [$l, $v, $icon, $color])
        <div class="col-md-3 col-6"><div class="card h-100"><div class="card-body py-3">
            <i class="fa-solid {{ $icon }} {{ $color }}"></i>
            <div class="fs-4 fw-bold">{{ $v }}</div>
            <div class="small text-muted">{{ $l }}</div>
        </div></div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2 flex-wrap" method="GET">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بحث بالعنوان أو الرقم">
                    <select name="project_id" class="form-select" style="min-width:160px" onchange="this.form.submit()">
                        <option value="">كل المشاريع</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected((string) $projectId === (string) $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                    <select name="type" class="form-select" style="min-width:160px" onchange="this.form.submit()">
                        <option value="">كل الأنواع</option>
                        @foreach (\App\Models\Submittal::TYPES as $key => $label)
                            <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="form-select" style="min-width:160px" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\Submittal::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                <div class="d-flex gap-2">
                    @can('projects.create')
                        <a href="{{ route('submittals.create') }}" class="btn" style="background:#2b4c80;color:#fff">
                            <i class="fa-solid fa-plus ms-1"></i> اعتماد فني جديد
                        </a>
                    @endcan
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>الرقم</th>
                            <th>العنوان</th>
                            <th>النوع</th>
                            <th>المشروع</th>
                            <th>موجَّه إلى</th>
                            <th>موعد المراجعة</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($submittals as $submittal)
                            @php($badge = match($submittal->status) {
                                'submitted' => 'primary', 'under_review' => 'warning',
                                'approved' => 'success', 'approved_as_noted' => 'info',
                                'rejected' => 'danger', default => 'secondary' })
                            <tr>
                                <td class="fw-semibold">{{ $submittal->submittal_number }}</td>
                                <td>{{ $submittal->title }}</td>
                                <td>{{ \App\Models\Submittal::TYPES[$submittal->type] ?? $submittal->type }}</td>
                                <td>{{ $submittal->project?->name ?? '—' }}</td>
                                <td>{{ $submittal->submitted_to ?: '—' }}</td>
                                <td>
                                    {{ $submittal->due_date?->format('Y-m-d') ?? '—' }}
                                    @if ($submittal->isOverdue())
                                        <i class="fa-solid fa-triangle-exclamation text-danger ms-1" title="متأخر"></i>
                                    @endif
                                </td>
                                <td><span class="badge text-bg-{{ $badge }}">{{ \App\Models\Submittal::STATUSES[$submittal->status] ?? $submittal->status }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('submittals.show', $submittal) }}" class="btn btn-sm btn-outline-secondary" title="عرض">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @can('projects.edit')
                                        <a href="{{ route('submittals.edit', $submittal) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('projects.delete')
                                        <form method="POST" action="{{ route('submittals.destroy', $submittal) }}" class="d-inline"
                                              data-confirm="متأكد من حذف الاعتماد الفني؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">لا توجد اعتمادات فنية بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $submittals->links() }}
        </div>
    </div>
@endsection
