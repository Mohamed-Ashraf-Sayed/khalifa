@extends('layouts.app')

@section('title', 'طلبات الاستفسار (RFI)')

@section('content')
    <div class="row g-3 mb-3">
        @foreach ([
            ['طلبات مفتوحة', number_format($stats['open']), 'fa-circle-question', 'text-primary'],
            ['متأخرة', number_format($stats['overdue']), 'fa-triangle-exclamation', 'text-danger'],
            ['تمت الإجابة', number_format($stats['answered']), 'fa-circle-check', 'text-success'],
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
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2 flex-wrap" method="GET">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بحث بالموضوع أو الرقم">
                    <select name="project_id" class="form-select" style="min-width:160px" onchange="this.form.submit()">
                        <option value="">كل المشاريع</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected((string) $projectId === (string) $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="form-select" style="min-width:160px" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\Rfi::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                <div class="d-flex gap-2">
                    @can('projects.create')
                        <a href="{{ route('rfis.create') }}" class="btn" style="background:#8b7355;color:#fff">
                            <i class="fa-solid fa-plus ms-1"></i> طلب استفسار جديد
                        </a>
                    @endcan
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>الرقم</th>
                            <th>الموضوع</th>
                            <th>المشروع</th>
                            <th>موجَّه إلى</th>
                            <th>موعد الرد</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rfis as $rfi)
                            @php($badge = match($rfi->status) {
                                'open' => 'warning', 'answered' => 'success',
                                'closed' => 'secondary', default => 'secondary' })
                            <tr @class(['table-warning' => $rfi->isOverdue()])>
                                <td class="fw-semibold">{{ $rfi->rfi_number }}</td>
                                <td>{{ $rfi->subject }}</td>
                                <td>{{ $rfi->project?->name ?? '—' }}</td>
                                <td>{{ $rfi->raised_to ?: '—' }}</td>
                                <td>
                                    {{ $rfi->due_date?->format('Y-m-d') ?? '—' }}
                                    @if ($rfi->isOverdue())
                                        <i class="fa-solid fa-triangle-exclamation text-danger ms-1" title="متأخر"></i>
                                    @endif
                                </td>
                                <td><span class="badge text-bg-{{ $badge }}">{{ \App\Models\Rfi::STATUSES[$rfi->status] ?? $rfi->status }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('rfis.show', $rfi) }}" class="btn btn-sm btn-outline-secondary" title="عرض">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @can('projects.edit')
                                        <a href="{{ route('rfis.edit', $rfi) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('projects.delete')
                                        <form method="POST" action="{{ route('rfis.destroy', $rfi) }}" class="d-inline"
                                              onsubmit="return confirm('متأكد من حذف طلب الاستفسار؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">لا توجد طلبات استفسار بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $rfis->links() }}
        </div>
    </div>
@endsection
