@extends('layouts.app')

@section('title', 'المشاريع')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2" method="GET">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بحث باسم المشروع">
                    <select name="status" class="form-select" style="min-width:160px" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\Project::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                @can('projects.create')
                    <a href="{{ route('projects.create') }}" class="btn" style="background:#8b7355;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> مشروع جديد
                    </a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>المشروع</th>
                            <th>العميل</th>
                            <th>قيمة العقد</th>
                            <th>الحالة</th>
                            <th>البداية</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($projects as $project)
                            @php($badge = match($project->status) {
                                'completed' => 'success', 'in_progress' => 'primary',
                                'on_hold' => 'warning', 'cancelled' => 'danger', default => 'secondary' })
                            <tr>
                                <td class="fw-semibold">{{ $project->name }}</td>
                                <td>{{ $project->client?->name ?? '—' }}</td>
                                <td>{{ number_format($project->contract_value, 2) }} ج</td>
                                <td><span class="badge text-bg-{{ $badge }}">{{ \App\Models\Project::STATUSES[$project->status] ?? $project->status }}</span></td>
                                <td>{{ $project->start_date?->format('Y-m-d') }}</td>
                                <td class="text-end">
                                    @can('projects.edit')
                                        <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('projects.delete')
                                        <form method="POST" action="{{ route('projects.destroy', $project) }}" class="d-inline"
                                              onsubmit="return confirm('متأكد من حذف المشروع؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">لا توجد مشاريع بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $projects->links() }}
        </div>
    </div>
@endsection
