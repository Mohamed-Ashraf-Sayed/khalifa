@extends('layouts.app')

@section('title', 'الإشعارات')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="fa-solid fa-bell"></i> الإشعارات</h5>
        @if ($unreadCount > 0)
            <form method="POST" action="{{ route('notifications.read_all') }}">
                @csrf
                <button class="btn btn-sm" style="background:#2b4c80;color:#fff">
                    <i class="fa-solid fa-check-double"></i> تعليم الكل كمقروء
                    <span class="badge text-bg-light ms-1">{{ $unreadCount }}</span>
                </button>
            </form>
        @endif
    </div>

    <div class="card">
        <div class="list-group list-group-flush">
            @forelse ($notifications as $notification)
                @php($data = $notification->data)
                @php($items = $data['items'] ?? [])
                @php($isUnread = is_null($notification->read_at))
                <div class="list-group-item {{ $isUnread ? 'bg-light' : '' }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                @if ($isUnread)
                                    <span class="badge text-bg-danger rounded-pill">جديد</span>
                                @endif
                                <span class="fw-semibold">{{ $data['title'] ?? 'إشعار' }}</span>
                                @isset($data['total'])
                                    <span class="badge text-bg-secondary">{{ $data['total'] }}</span>
                                @endisset
                            </div>
                            @if (! empty($items))
                                <ul class="list-unstyled mb-1 small">
                                    @foreach ($items as $item)
                                        <li class="mb-1">
                                            <a href="{{ $item['url'] ?? '#' }}" class="text-decoration-none">
                                                <i class="fa-solid fa-angle-left text-muted"></i>
                                                {{ $item['label'] ?? '' }}
                                                <span class="badge text-bg-warning">{{ $item['count'] ?? 0 }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            <span class="text-muted small">{{ $notification->created_at?->format('Y-m-d H:i') }}</span>
                        </div>
                        @if ($isUnread)
                            <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="ms-2">
                                @csrf
                                <button class="btn btn-sm btn-light" title="فتح وتعليم كمقروء">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="list-group-item text-center text-muted py-4">لا توجد إشعارات.</div>
            @endforelse
        </div>
    </div>

    <div class="mt-3">{{ $notifications->links() }}</div>
@endsection
