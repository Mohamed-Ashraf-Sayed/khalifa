@extends('layouts.app')

@section('title', 'مسيّر الرواتب الشهري')

@section('content')
    <div class="row g-3 mb-3">
        @foreach ([
            ['عدد المسيّرات', number_format($stats['count']), 'fa-money-check-dollar', 'text-primary'],
            ['مسودات', number_format($stats['drafts']), 'fa-pen-ruler', 'text-warning'],
            ['إجمالي المصروف', number_format((float) $stats['paid'], 2), 'fa-sack-dollar', 'text-success'],
        ] as [$l, $v, $icon, $color])
        <div class="col-md-4 col-6"><div class="card h-100"><div class="card-body py-3">
            <i class="fa-solid {{ $icon }} {{ $color }}"></i>
            <div class="fs-4 fw-bold">{{ $v }}</div>
            <div class="small text-muted">{{ $l }}</div>
        </div></div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h6 class="mb-0"><i class="fa-solid fa-money-check-dollar ms-1" style="color:#2b4c80"></i> مسيّرات الرواتب</h6>
                @can('employees.create')
                    <a href="{{ route('payroll.create') }}" class="btn" style="background:#2b4c80;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> مسيّر جديد
                    </a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>الرقم</th>
                            <th>الشهر</th>
                            <th>عدد البنود</th>
                            <th>إجمالي الصافي</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($runs as $run)
                            @php($badge = match($run->status) {
                                'draft' => 'secondary', 'approved' => 'info',
                                'paid' => 'success', default => 'secondary' })
                            <tr>
                                <td class="fw-semibold">{{ $run->run_number }}</td>
                                <td>{{ \App\Models\PayrollRun::MONTHS[$run->period_month] ?? $run->period_month }} {{ $run->period_year }}</td>
                                <td>{{ number_format($run->items_count ?? $run->items()->count()) }}</td>
                                <td>{{ number_format((float) $run->total_net, 2) }}</td>
                                <td><span class="badge text-bg-{{ $badge }}">{{ \App\Models\PayrollRun::STATUSES[$run->status] ?? $run->status }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('payroll.show', $run) }}" class="btn btn-sm btn-outline-secondary" title="عرض">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @can('employees.delete')
                                        @if ($run->status === 'draft')
                                            <form method="POST" action="{{ route('payroll.destroy', $run) }}" class="d-inline"
                                                  data-confirm="متأكد من حذف المسيّر؟">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">لا توجد مسيّرات رواتب بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $runs->links() }}
        </div>
    </div>
@endsection
