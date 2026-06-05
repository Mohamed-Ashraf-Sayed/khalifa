@extends('layouts.app')

@section('title', 'أوامر التغيير')

@section('content')
    <div class="row g-3 mb-3">
        @foreach ([
            ['عدد الأوامر', number_format($stats['count']), 'fa-file-pen', 'text-primary'],
            ['إجمالي الإضافات المعتمدة', number_format($stats['additions'], 0), 'fa-circle-plus', 'text-success'],
            ['إجمالي الخصومات المعتمدة', number_format($stats['deductions'], 0), 'fa-circle-minus', 'text-danger'],
            ['صافي المعتمد', number_format($stats['net'], 0), 'fa-scale-balanced', 'text-info'],
        ] as [$l, $v, $icon, $color])
        <div class="col-md-3 col-6"><div class="card h-100"><div class="card-body py-3">
            <i class="fa-solid {{ $icon }} {{ $color }}"></i>
            <div class="fs-4 fw-bold">{{ $v }}</div>
            <div class="small text-muted">{{ $l }}</div>
        </div></div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2 flex-wrap" method="GET">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بحث برقم الأمر أو العنوان">
                    <select name="project_id" class="form-select" style="min-width:160px" onchange="this.form.submit()">
                        <option value="">كل المشاريع</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected((string) $projectId === (string) $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="form-select" style="min-width:160px" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\ChangeOrder::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                <div class="d-flex gap-2">
                    @can('contracts.create')
                        <a href="{{ route('change_orders.create') }}" class="btn" style="background:#8b7355;color:#fff">
                            <i class="fa-solid fa-plus ms-1"></i> أمر تغيير جديد
                        </a>
                    @endcan
                </div>
            </div>

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
                                              onsubmit="return confirm('متأكد من حذف أمر التغيير؟')">
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
