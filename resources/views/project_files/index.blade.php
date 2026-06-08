@extends('layouts.app')

@section('title', 'ملفات المشاريع')

@section('content')
    @can('projects.edit')
    <div class="card mb-3"><div class="card-body">
        <form method="POST" action="{{ route('project_files.store') }}" enctype="multipart/form-data" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-4">
                <label class="form-label small">المشروع</label>
                <select name="project_id" class="form-select" required>
                    <option value="">— اختر —</option>
                    @foreach ($projects as $p)
                        <option value="{{ $p->id }}" @selected($selectedProject === $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small">الملف <span class="text-muted">(pdf, word, excel, صور — حتى 10MB)</span></label>
                <input type="file" name="file" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label small">وصف</label>
                <input type="text" name="description" class="form-control">
            </div>
            <div class="col-md-2"><button class="btn w-100" style="background:#2b4c80;color:#fff">رفع</button></div>
        </form>
    </div></div>
    @endcan

    <div class="card">
        <div class="card-body">
            <form method="GET" class="mb-3" style="max-width:300px">
                <select name="project_id" class="form-select" onchange="this.form.submit()">
                    <option value="">كل المشاريع</option>
                    @foreach ($projects as $p)
                        <option value="{{ $p->id }}" @selected($selectedProject === $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </form>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>الملف</th><th>المشروع</th><th>الحجم</th><th>رفعه</th><th>التاريخ</th><th class="text-end"></th></tr></thead>
                    <tbody>
                        @forelse ($files as $f)
                            <tr>
                                <td><i class="fa-solid fa-paperclip text-muted ms-1"></i> {{ $f->original_name }}@if($f->description)<div class="small text-muted">{{ $f->description }}</div>@endif</td>
                                <td>{{ $f->project?->name ?? '—' }}</td>
                                <td>{{ number_format($f->size / 1024, 1) }} KB</td>
                                <td>{{ $f->uploader?->name ?? '—' }}</td>
                                <td>{{ $f->created_at->format('Y-m-d') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('project_files.download', $f) }}" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-download"></i></a>
                                    @can('projects.edit')
                                        <form method="POST" action="{{ route('project_files.destroy', $f) }}" class="d-inline" data-confirm="حذف الملف؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">لا توجد ملفات بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $files->links() }}
        </div>
    </div>
@endsection
