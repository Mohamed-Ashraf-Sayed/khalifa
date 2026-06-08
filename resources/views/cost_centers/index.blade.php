@extends('layouts.app')

@section('title', 'مراكز التكلفة')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2" method="GET">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بحث بالاسم أو الكود">
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                <div class="d-flex gap-2">
                    @can('reports.view')
                        <a href="{{ route('cost_centers.report') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-chart-pie ms-1"></i> تقرير المراكز</a>
                    @endcan
                    @can('settings.edit')
                        <a href="{{ route('cost_centers.create') }}" class="btn" style="background:#8b7355;color:#fff">
                            <i class="fa-solid fa-plus ms-1"></i> مركز تكلفة جديد
                        </a>
                    @endcan
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>الاسم</th>
                            <th>الكود</th>
                            <th>الحالة</th>
                            <th>ملاحظات</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($costCenters as $center)
                            <tr>
                                <td class="fw-semibold">{{ $center->name }}</td>
                                <td><span class="badge text-bg-light">{{ $center->code }}</span></td>
                                <td>
                                    @if ($center->is_active)
                                        <span class="badge text-bg-success">نشط</span>
                                    @else
                                        <span class="badge text-bg-secondary">غير نشط</span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ \Illuminate\Support\Str::limit($center->notes, 50) ?: '—' }}</td>
                                <td class="text-end">
                                    @can('settings.edit')
                                        <a href="{{ route('cost_centers.edit', $center) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                        <form method="POST" action="{{ route('cost_centers.destroy', $center) }}" class="d-inline"
                                              data-confirm="متأكد من حذف مركز التكلفة؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">لا توجد مراكز تكلفة بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $costCenters->links() }}
        </div>
    </div>
@endsection
