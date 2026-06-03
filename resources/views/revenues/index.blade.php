@extends('layouts.app')

@section('title', 'الإيرادات')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">إجمالي الإيرادات</div>
                <div class="fs-4 fw-bold text-success">{{ number_format((float) $stats['total'], 2) }} ج</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">إجمالي المحصّل</div>
                <div class="fs-4 fw-bold text-success">{{ number_format((float) $stats['collected'], 2) }} ج</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">المتبقّي</div>
                <div class="fs-4 fw-bold text-warning">{{ number_format((float) $stats['remaining'], 2) }} ج</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">عدد الإيرادات</div>
                <div class="fs-4 fw-bold">{{ $stats['count'] }}</div>
            </div></div>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">المشروع</label>
                <select name="project_id" class="form-select">
                    <option value="">كل المشاريع</option>
                    @foreach ($projects as $p)
                        <option value="{{ $p->id }}" @selected($projectId === (string) $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">حالة التحصيل</label>
                <select name="payment_status" class="form-select">
                    <option value="">كل الحالات</option>
                    @foreach (\App\Models\Revenue::PAYMENT_STATUSES as $k => $label)
                        <option value="{{ $k }}" @selected($paymentStatus === $k)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">من تاريخ</label>
                <input type="date" name="from" value="{{ $from }}" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label small">إلى تاريخ</label>
                <input type="date" name="to" value="{{ $to }}" class="form-control">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn flex-fill" style="background:#8b7355;color:#fff"><i class="fa-solid fa-filter ms-1"></i> تصفية</button>
                <a href="{{ route('revenues.index') }}" class="btn btn-light"><i class="fa-solid fa-rotate-right"></i></a>
            </div>
        </form>
    </div></div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end gap-2 mb-3">
                <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-success"><i class="fa-solid fa-file-csv ms-1"></i> تصدير CSV</a>
                @can('revenues.create')
                    <a href="{{ route('revenues.create') }}" class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-plus ms-1"></i> إيراد جديد</a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>التاريخ</th>
                            <th>البيان</th>
                            <th>المشروع</th>
                            <th>المبلغ</th>
                            <th>المحصّل</th>
                            <th>حالة التحصيل</th>
                            <th>الاستلام</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($revenues as $revenue)
                            <tr>
                                <td>{{ $revenue->revenue_date->format('Y-m-d') }}</td>
                                <td class="fw-semibold">{{ $revenue->description }}</td>
                                <td>{{ $revenue->project?->name ?? '—' }}</td>
                                <td class="fw-bold text-success">{{ number_format($revenue->amount, 2) }}</td>
                                <td>{{ number_format($revenue->paid_amount, 2) }}</td>
                                <td>
                                    @php $sc = ['pending' => 'secondary', 'partial' => 'warning', 'collected' => 'success']; @endphp
                                    <span class="badge bg-{{ $sc[$revenue->payment_status] ?? 'secondary' }}">{{ \App\Models\Revenue::PAYMENT_STATUSES[$revenue->payment_status] ?? $revenue->payment_status }}</span>
                                    @if ($revenue->is_confirmed)
                                        <span class="badge bg-success">مؤكد</span>
                                    @else
                                        <span class="badge bg-secondary">قيد التأكيد</span>
                                    @endif
                                </td>
                                <td>
                                    {{ \App\Models\Revenue::PAYMENT_METHODS[$revenue->payment_method] ?? $revenue->payment_method }}
                                    @if ($revenue->bankAccount)<i class="fa-solid fa-building-columns text-muted small" title="{{ $revenue->bankAccount->name }}"></i>@endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('revenues.show', $revenue) }}" class="btn btn-sm btn-outline-secondary" title="عرض"><i class="fa-solid fa-eye"></i></a>
                                    @can('revenues.edit')
                                        <a href="{{ route('revenues.edit', $revenue) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('revenues.delete')
                                        <form method="POST" action="{{ route('revenues.destroy', $revenue) }}" class="d-inline" onsubmit="return confirm('حذف الإيراد؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">لا توجد إيرادات بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $revenues->links() }}
        </div>
    </div>
@endsection
