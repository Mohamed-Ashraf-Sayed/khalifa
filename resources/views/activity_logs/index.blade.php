@extends('layouts.app')

@section('title', 'سجل النشاطات')

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('activity_logs.index') }}" class="filter-bar row g-2 align-items-end mb-3">
                <div class="col-6 col-md-3">
                    <label class="form-label">المستخدم</label>
                    <select name="user_id" class="form-select">
                        <option value="">الكل</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">الإجراء</label>
                    <input type="text" name="action" value="{{ request('action') }}" class="form-control" list="actionsList">
                    <datalist id="actionsList">
                        @foreach ($actions as $a)
                            <option value="{{ $a }}">
                        @endforeach
                    </datalist>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">نوع العنصر</label>
                    <input type="text" name="model_type" value="{{ request('model_type') }}" class="form-control">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">من تاريخ</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="form-control">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">إلى تاريخ</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-control">
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
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light"><tr><th>الوقت</th><th>المستخدم</th><th>الإجراء</th><th>العنصر</th><th>التغييرات</th><th>IP</th></tr></thead>
                    <tbody>
                        @forelse ($logs as $log)
                            @php($badge = match($log->action) { 'created'=>'success','updated'=>'primary','deleted'=>'danger',default=>'secondary' })
                            <tr>
                                <td class="small">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $log->user?->name ?? '—' }}</td>
                                <td><span class="badge text-bg-{{ $badge }}">{{ \App\Models\ActivityLog::ACTIONS[$log->action] ?? $log->action }}</span></td>
                                <td>{{ $log->description }}</td>
                                <td>
                                    @if (! empty($log->changes))
                                        <details>
                                            <summary class="small text-primary" style="cursor:pointer">
                                                {{ count($log->changes) }} حقل
                                            </summary>
                                            <div class="small mt-1">
                                                @if ($log->action === 'updated')
                                                    @foreach ($log->changes as $field => $diff)
                                                        <div class="mb-1">
                                                            <span class="fw-semibold">{{ $field }}:</span>
                                                            <span class="text-danger text-decoration-line-through">{{ \Illuminate\Support\Str::limit((string) ($diff['old'] ?? '—'), 40) ?: '—' }}</span>
                                                            <i class="fa-solid fa-arrow-left-long text-muted mx-1"></i>
                                                            <span class="text-success">{{ \Illuminate\Support\Str::limit((string) ($diff['new'] ?? '—'), 40) ?: '—' }}</span>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    @foreach ($log->changes as $field => $value)
                                                        <div class="mb-1">
                                                            <span class="fw-semibold">{{ $field }}:</span>
                                                            <span class="text-muted">{{ \Illuminate\Support\Str::limit(is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE), 40) ?: '—' }}</span>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </details>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="small text-muted" dir="ltr">{{ $log->ip_address ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">لا يوجد نشاط مُسجّل بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $logs->links() }}
        </div>
    </div>
@endsection
