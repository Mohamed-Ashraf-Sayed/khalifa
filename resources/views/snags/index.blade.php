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
        <div class="col-md-3 col-6"><div class="statcard {{ str_replace('text-','sc-',$color) }} h-100">
                <span class="sc-ic"><i class="fa-solid {{ $icon }}"></i></span>
                <span><span class="sc-v d-block">{{ $v }}</span><span class="sc-l d-block">{{ $l }}</span></span>
            </div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end gap-2 mb-3">
                @can('projects.create')
                    <a href="{{ route('snags.create') }}" class="btn" style="background:#2b4c80;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> ملاحظة جديدة
                    </a>
                @endcan
            </div>

            <form method="GET" class="filter-bar row g-2 align-items-end mb-3">
                <div class="col-6 col-md-3">
                    <label class="form-label">بحث</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control">
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
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\Snag::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">الأولوية</label>
                    <select name="priority" class="form-select" onchange="this.form.submit()">
                        <option value="">كل الأولويات</option>
                        @foreach (\App\Models\Snag::PRIORITIES as $key => $label)
                            <option value="{{ $key }}" @selected($priority === $key)>{{ $label }}</option>
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
