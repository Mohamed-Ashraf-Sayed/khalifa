@extends('layouts.app')

@section('title', 'الإيرادات')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <div class="text-muted small">إجمالي الإيرادات</div>
                <div class="fs-4 fw-bold text-success">{{ number_format($total, 2) }} ج</div>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end mb-3">
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
