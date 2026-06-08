@extends('layouts.app')

@section('title', 'يومية الموقع')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2 flex-wrap" method="GET">
                    <select name="project_id" class="form-select" style="min-width:180px" onchange="this.form.submit()">
                        <option value="">كل المشاريع</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected((string) $projectId === (string) $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="from" value="{{ $from }}" class="form-control" style="min-width:150px" title="من تاريخ">
                    <input type="date" name="to" value="{{ $to }}" class="form-control" style="min-width:150px" title="إلى تاريخ">
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                @can('projects.create')
                    <a href="{{ route('daily_site_reports.create') }}" class="btn" style="background:#2b4c80;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> يومية جديدة
                    </a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>المشروع</th>
                            <th>التاريخ</th>
                            <th>الطقس</th>
                            <th>عدد العمالة</th>
                            <th>الأعمال المنفّذة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reports as $report)
                            <tr>
                                <td class="fw-semibold">{{ $report->project?->name ?? '—' }}</td>
                                <td>{{ $report->report_date?->format('Y-m-d') }}</td>
                                <td>{{ $report->weather ?: '—' }}</td>
                                <td><span class="badge text-bg-info">{{ $report->labor_count }}</span></td>
                                <td>{{ \Illuminate\Support\Str::limit($report->work_done, 60) ?: '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('daily_site_reports.show', $report) }}" class="btn btn-sm btn-outline-secondary" title="عرض">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @can('projects.edit')
                                        <a href="{{ route('daily_site_reports.edit', $report) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('projects.delete')
                                        <form method="POST" action="{{ route('daily_site_reports.destroy', $report) }}" class="d-inline"
                                              data-confirm="متأكد من حذف اليومية؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">لا توجد يوميات بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $reports->links() }}
        </div>
    </div>
@endsection
