@extends('layouts.app')

@section('title', 'قائمة العيوب')

@section('content')
    <div class="row g-3 mb-3">
        @foreach ([
            ['ملاحظات مفتوحة', number_format($stats['open']), 'fa-folder-open', 'text-warning'],
            ['عالية الأولوية مفتوحة', number_format($stats['high_open']), 'fa-triangle-exclamation', 'text-danger'],
            ['قيد المعالجة', number_format($stats['in_progress']), 'fa-spinner', 'text-info'],
            ['مغلقة', number_format($stats['closed']), 'fa-circle-check', 'text-success'],
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
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بحث بعنوان الملاحظة">
                    <select name="project_id" class="form-select" style="min-width:160px" onchange="this.form.submit()">
                        <option value="">كل المشاريع</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected((string) $projectId === (string) $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="form-select" style="min-width:140px" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\Snag::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="priority" class="form-select" style="min-width:140px" onchange="this.form.submit()">
                        <option value="">كل الأولويات</option>
                        @foreach (\App\Models\Snag::PRIORITIES as $key => $label)
                            <option value="{{ $key }}" @selected($priority === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                <div class="d-flex gap-2">
                    @can('projects.create')
                        <a href="{{ route('snags.create') }}" class="btn" style="background:#2b4c80;color:#fff">
                            <i class="fa-solid fa-plus ms-1"></i> ملاحظة جديدة
                        </a>
                    @endcan
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>العنوان</th>
                            <th>المشروع</th>
                            <th>المكان</th>
                            <th>المسؤول</th>
                            <th>الأولوية</th>
                            <th>الاستحقاق</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($snags as $snag)
                            @php($statusBadge = match($snag->status) {
                                'open' => 'warning', 'in_progress' => 'info', 'closed' => 'success', default => 'secondary' })
                            @php($priorityBadge = match($snag->priority) {
                                'high' => 'danger', 'medium' => 'warning', 'low' => 'secondary', default => 'secondary' })
                            <tr>
                                <td class="fw-semibold">{{ $snag->title }}</td>
                                <td>{{ $snag->project?->name ?? '—' }}</td>
                                <td>{{ $snag->location ?: '—' }}</td>
                                <td>{{ $snag->assignedEmployee?->name ?? ($snag->responsible ?: '—') }}</td>
                                <td><span class="badge text-bg-{{ $priorityBadge }}">{{ \App\Models\Snag::PRIORITIES[$snag->priority] ?? $snag->priority }}</span></td>
                                <td>
                                    {{ $snag->due_date?->format('Y-m-d') ?? '—' }}
                                    @if ($snag->isOverdue())
                                        <i class="fa-solid fa-triangle-exclamation text-danger ms-1" title="متأخرة"></i>
                                    @endif
                                </td>
                                <td><span class="badge text-bg-{{ $statusBadge }}">{{ \App\Models\Snag::STATUSES[$snag->status] ?? $snag->status }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('snags.show', $snag) }}" class="btn btn-sm btn-outline-secondary" title="عرض">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @can('projects.edit')
                                        @if ($snag->status !== 'closed')
                                            <form method="POST" action="{{ route('snags.close', $snag) }}" class="d-inline"
                                                  data-confirm="تأكيد إغلاق الملاحظة؟">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-success" title="إغلاق"><i class="fa-solid fa-circle-check"></i></button>
                                            </form>
                                        @endif
                                        <a href="{{ route('snags.edit', $snag) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('projects.delete')
                                        <form method="POST" action="{{ route('snags.destroy', $snag) }}" class="d-inline"
                                              data-confirm="متأكد من حذف الملاحظة؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">لا توجد ملاحظات بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $snags->links() }}
        </div>
    </div>
@endsection
