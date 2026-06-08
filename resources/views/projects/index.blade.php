@extends('layouts.app')

@section('title', 'المشاريع')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end gap-2 mb-3">
                @can('projects.create')
                    <a href="{{ route('projects.create') }}" class="btn" style="background:#2b4c80;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> مشروع جديد
                    </a>
                @endcan
            </div>

            <form method="GET" class="filter-bar row g-2 align-items-end mb-3">
                <div class="col-6 col-md-3">
                    <label class="form-label">بحث</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\Project::STATUSES as $key => $label)
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
                                    <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline-secondary" title="عرض"><i class="fa-solid fa-eye"></i></a>
                                    @can('projects.edit')
                                        <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('projects.delete')
                                        <form method="POST" action="{{ route('projects.destroy', $project) }}" class="d-inline"
                                              data-confirm="متأكد من حذف المشروع؟">
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
