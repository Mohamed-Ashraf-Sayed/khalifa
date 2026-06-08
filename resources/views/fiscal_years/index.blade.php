@extends('layouts.app')

@section('title', 'السنوات المالية')

@section('content')
    <div class="d-flex justify-content-end mb-3">
        @can('accounting.create')
            <a href="{{ route('fiscal_years.create') }}" class="btn btn-sm" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-plus ms-1"></i> سنة مالية جديدة</a>
        @endcan
    </div>

    <div class="card"><div class="card-body">
        <h6 class="mb-3"><i class="fa-solid fa-calendar-days ms-1" style="color:#2b4c80"></i> السنوات والفترات المالية</h6>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light"><tr><th>السنة</th><th>من</th><th>إلى</th><th>الفترات المقفلة</th><th>الحالة</th><th class="text-end">إجراءات</th></tr></thead>
                <tbody>
                    @forelse ($years as $fy)
                        @php($closedCount = $fy->periods->where('status', 'closed')->count())
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('fiscal_years.show', $fy) }}" class="text-decoration-none">{{ $fy->name }}</a></td>
                            <td>{{ $fy->start_date->format('Y-m-d') }}</td>
                            <td>{{ $fy->end_date->format('Y-m-d') }}</td>
                            <td>{{ $closedCount }} / {{ $fy->periods->count() }}</td>
                            <td><span class="badge text-bg-{{ $fy->status === 'closed' ? 'secondary' : 'success' }}">{{ \App\Models\FiscalYear::STATUSES[$fy->status] ?? $fy->status }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('fiscal_years.show', $fy) }}" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-eye"></i></a>
                                @can('accounting.delete')
                                    @if ($fy->status === 'open')
                                        <form method="POST" action="{{ route('fiscal_years.destroy', $fy) }}" class="d-inline" data-confirm="حذف السنة المالية؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">لا توجد سنوات مالية. أنشئ سنة لتفعيل قفل الفترات والإقفال السنوي.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div></div>
@endsection
