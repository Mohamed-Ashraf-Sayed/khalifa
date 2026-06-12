@extends('layouts.app')

@section('title', 'محاضر الاجتماعات')

@section('content')
    <div class="row g-3 mb-3">
        @foreach ([
            ['إجمالي المحاضر', number_format($stats['total']), 'fa-users-rectangle', 'text-primary'],
            ['اجتماعات هذا الشهر', number_format($stats['this_month']), 'fa-calendar-day', 'text-success'],
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
                @can('projects.create')
                    <a href="{{ route('meetings.create') }}" class="btn" style="background:#2b4c80;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> محضر اجتماع جديد
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
                    <label class="form-label">من تاريخ</label>
                    <input type="date" name="from" value="{{ $from }}" class="form-control" onchange="this.form.submit()">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">إلى تاريخ</label>
                    <input type="date" name="to" value="{{ $to }}" class="form-control" onchange="this.form.submit()">
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
                            <th>التاريخ</th>
                            <th>المكان</th>
                            <th>الاجتماع القادم</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($meetings as $meeting)
                            <tr>
                                <td class="fw-semibold">{{ $meeting->meeting_number }}</td>
                                <td>{{ $meeting->title }}</td>
                                <td>{{ $meeting->project?->name ?? '—' }}</td>
                                <td>{{ $meeting->meeting_date?->format('Y-m-d') ?? '—' }}</td>
                                <td>{{ $meeting->location ?: '—' }}</td>
                                <td>{{ $meeting->next_meeting_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('meetings.show', $meeting) }}" class="btn btn-sm btn-outline-secondary" title="عرض">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @can('projects.edit')
                                        <a href="{{ route('meetings.edit', $meeting) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('projects.delete')
                                        <form method="POST" action="{{ route('meetings.destroy', $meeting) }}" class="d-inline"
                                              data-confirm="متأكد من حذف محضر الاجتماع؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">لا توجد محاضر اجتماعات بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $meetings->links() }}
        </div>
    </div>
@endsection
