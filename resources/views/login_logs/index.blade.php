@extends('layouts.app')

@section('title', 'سجل الدخول')

@section('content')
    {{-- بطاقات الإحصائيات --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">إجمالي المحاولات</div>
                        <div class="h4 mb-0">{{ number_format($stats['total']) }}</div>
                    </div>
                    <i class="fa-solid fa-right-to-bracket fa-2x" style="color:#2b4c80"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">دخول ناجح</div>
                        <div class="h4 mb-0 text-success">{{ number_format($stats['successful']) }}</div>
                    </div>
                    <i class="fa-solid fa-circle-check fa-2x text-success"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">محاولات فاشلة</div>
                        <div class="h4 mb-0 text-danger">{{ number_format($stats['failed']) }}</div>
                    </div>
                    <i class="fa-solid fa-circle-xmark fa-2x text-danger"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">عناوين IP مختلفة</div>
                        <div class="h4 mb-0">{{ number_format($stats['distinct_ips']) }}</div>
                    </div>
                    <i class="fa-solid fa-network-wired fa-2x" style="color:#2b4c80"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- نموذج الفلترة --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('login_logs.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">البريد الإلكتروني</label>
                    <input type="text" name="email" value="{{ request('email') }}" class="form-control form-control-sm" dir="ltr">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">عنوان IP</label>
                    <input type="text" name="ip_address" value="{{ request('ip_address') }}" class="form-control form-control-sm" dir="ltr">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">الحالة</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        <option value="success" @selected(request('status') === 'success')>ناجح</option>
                        <option value="fail" @selected(request('status') === 'fail')>فاشل</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">من تاريخ</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">إلى تاريخ</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button class="btn btn-sm" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-magnifying-glass"></i></button>
                    <a href="{{ route('login_logs.index') }}" class="btn btn-sm btn-light" title="مسح"><i class="fa-solid fa-rotate-left"></i></a>
                </div>
            </form>
        </div>
    </div>

    {{-- الجدول --}}
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>الوقت</th><th>البريد</th><th>المستخدم</th><th>IP</th><th>المتصفح</th><th>الحالة</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td class="small">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                                <td dir="ltr" class="text-start small">{{ $log->email }}</td>
                                <td>{{ $log->user?->name ?? '—' }}</td>
                                <td class="small text-muted" dir="ltr">{{ $log->ip_address ?: '—' }}</td>
                                <td class="small text-muted text-truncate" style="max-width:220px" title="{{ $log->user_agent }}" dir="ltr">{{ $log->user_agent ?: '—' }}</td>
                                <td>
                                    @if ($log->successful)
                                        <span class="badge text-bg-success"><i class="fa-solid fa-check ms-1"></i> ناجح</span>
                                    @else
                                        <span class="badge text-bg-danger"><i class="fa-solid fa-xmark ms-1"></i> فاشل</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">لا توجد محاولات دخول مُسجّلة.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $logs->links() }}
        </div>
    </div>
@endsection
