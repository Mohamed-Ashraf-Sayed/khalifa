@extends('layouts.app')

@section('title', 'خطابات الضمان')

@section('content')
    <div class="row g-3 mb-3">
        @foreach ([
            ['عدد الخطابات', number_format($stats['count']), 'fa-shield-halved', 'text-primary'],
            ['إجمالي قيمة السارية', number_format($stats['active_amount'], 0), 'fa-sack-dollar', 'text-success'],
            ['قاربت على الانتهاء', number_format($stats['expiring']), 'fa-clock', 'text-warning'],
            ['منتهية', number_format($stats['expired']), 'fa-triangle-exclamation', 'text-danger'],
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
                <form class="d-flex gap-2" method="GET">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بحث برقم الخطاب أو المستفيد">
                    <select name="status" class="form-select" style="min-width:160px" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Models\LetterOfGuarantee::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                <div class="d-flex gap-2">
                    @can('guarantees.create')
                        <a href="{{ route('guarantees.create') }}" class="btn" style="background:#2b4c80;color:#fff">
                            <i class="fa-solid fa-plus ms-1"></i> خطاب ضمان جديد
                        </a>
                    @endcan
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>رقم الخطاب</th>
                            <th>النوع</th>
                            <th>المستفيد</th>
                            <th>القيمة</th>
                            <th>تاريخ الانتهاء</th>
                            <th>المشروع</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($guarantees as $guarantee)
                            @php($badge = match($guarantee->status) {
                                'active' => 'success', 'released' => 'primary',
                                'expired' => 'danger', 'cancelled' => 'secondary', default => 'secondary' })
                            <tr>
                                <td class="fw-semibold">{{ $guarantee->lg_number }}</td>
                                <td>{{ \App\Models\LetterOfGuarantee::TYPES[$guarantee->type] ?? $guarantee->type }}</td>
                                <td>{{ $guarantee->beneficiary }}</td>
                                <td>{{ number_format((float) $guarantee->amount, 2) }} ج</td>
                                <td>
                                    {{ $guarantee->expiry_date?->format('Y-m-d') ?? '—' }}
                                    @if ($guarantee->isExpiringSoon())
                                        <i class="fa-solid fa-clock text-warning ms-1" title="قاربت على الانتهاء"></i>
                                    @endif
                                </td>
                                <td>{{ $guarantee->project?->name ?? '—' }}</td>
                                <td><span class="badge text-bg-{{ $badge }}">{{ \App\Models\LetterOfGuarantee::STATUSES[$guarantee->status] ?? $guarantee->status }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('guarantees.show', $guarantee) }}" class="btn btn-sm btn-outline-secondary" title="عرض">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @can('guarantees.edit')
                                        <a href="{{ route('guarantees.edit', $guarantee) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('guarantees.delete')
                                        <form method="POST" action="{{ route('guarantees.destroy', $guarantee) }}" class="d-inline"
                                              data-confirm="متأكد من حذف خطاب الضمان؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">لا توجد خطابات ضمان بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $guarantees->links() }}
        </div>
    </div>
@endsection
