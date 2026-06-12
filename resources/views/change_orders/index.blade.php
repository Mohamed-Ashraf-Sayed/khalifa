@extends('layouts.app')

@section('title', 'أوامر التغيير')

@section('content')
    <div class="row g-3 mb-3">
        @foreach ([
            ['عدد الأوامر', number_format($stats['count']), 'fa-clipboard-list', 'primary'],
            ['إجمالي الإضافات المعتمدة', number_format($stats['additions'], 0), 'fa-arrow-trend-up', 'success'],
            ['إجمالي الخصومات المعتمدة', number_format($stats['deductions'], 0), 'fa-arrow-trend-down', 'danger'],
            ['صافي المعتمد', number_format($stats['net'], 0), 'fa-scale-balanced', 'info'],
        ] as [$l, $v, $icon, $c])
        <div class="col-md-3 col-6">
            <div class="statcard sc-{{ $c }} h-100">
                <span class="sc-ic"><i class="fa-solid {{ $icon }}"></i></span>
                <span><span class="sc-v d-block">{{ $v }}</span><span class="sc-l d-block">{{ $l }}</span></span>
            </div>
        </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end gap-2 mb-3">
                @can('contracts.create')
                    <a href="{{ route('change_orders.create') }}" class="btn" style="background:#2b4c80;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> أمر تغيير جديد
                    </a>
                @endcan
            </div>

            <form method="GET" class="filter-bar row g-2 align-items-end mb-3">
                <div class="col-6 col-md-3">
                    <label class="form-label">بحث</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="رقم الأمر أو العنوان">
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
                <div class="col-6 col-md-3">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\ChangeOrder::STATUSES as $key => $label)
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
                            <th>رقم الأمر</th>
                            <th>العنوان</th>
                            <th>المشروع</th>
                            <th>النوع</th>
                            <th>القيمة</th>
                            <th>تاريخ الطلب</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($changeOrders as $order)
                            @php($badge = match($order->status) {
                                'approved' => 'success', 'pending' => 'warning',
                                'rejected' => 'danger', default => 'secondary' })
                            <tr>
                                <td class="fw-semibold">{{ $order->co_number }}</td>
                                <td>{{ $order->title }}</td>
                                <td>{{ $order->project?->name ?? '—' }}</td>
                                <td>
                                    <span class="badge text-bg-{{ $order->change_type === 'deduction' ? 'danger' : 'success' }}">
                                        {{ \App\Models\ChangeOrder::TYPES[$order->change_type] ?? $order->change_type }}
                                    </span>
                                </td>
                                <td class="{{ $order->change_type === 'deduction' ? 'text-danger' : '' }}">
                                    {{ number_format((float) $order->signedAmount(), 2) }} ج
                                </td>
                                <td>{{ $order->request_date?->format('Y-m-d') ?? '—' }}</td>
                                <td><span class="badge text-bg-{{ $badge }}">{{ \App\Models\ChangeOrder::STATUSES[$order->status] ?? $order->status }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('change_orders.show', $order) }}" class="btn btn-sm btn-outline-secondary" title="عرض">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @can('contracts.edit')
                                        <a href="{{ route('change_orders.edit', $order) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('contracts.delete')
                                        <form method="POST" action="{{ route('change_orders.destroy', $order) }}" class="d-inline"
                                              data-confirm="متأكد من حذف أمر التغيير؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">لا توجد أوامر تغيير بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $changeOrders->links() }}
        </div>
    </div>
@endsection
