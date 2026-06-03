@extends('layouts.app')

@section('title', 'سجل النشاطات')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light"><tr><th>الوقت</th><th>المستخدم</th><th>الإجراء</th><th>العنصر</th><th>IP</th></tr></thead>
                    <tbody>
                        @forelse ($logs as $log)
                            @php($badge = match($log->action) { 'created'=>'success','updated'=>'primary','deleted'=>'danger',default=>'secondary' })
                            <tr>
                                <td class="small">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $log->user?->name ?? '—' }}</td>
                                <td><span class="badge text-bg-{{ $badge }}">{{ \App\Models\ActivityLog::ACTIONS[$log->action] ?? $log->action }}</span></td>
                                <td>{{ $log->description }}</td>
                                <td class="small text-muted" dir="ltr">{{ $log->ip_address ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">لا يوجد نشاط مُسجّل بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $logs->links() }}
        </div>
    </div>
@endsection
