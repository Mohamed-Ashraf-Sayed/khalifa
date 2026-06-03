@extends('layouts.app')

@section('title', 'الشركاء')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2" method="GET">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بحث بالاسم أو الهاتف">
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                @can('partners.create')
                    <a href="{{ route('partners.create') }}" class="btn btn-brown" style="background:#8b7355;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> شريك جديد
                    </a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>الاسم</th>
                            <th>الهاتف</th>
                            <th>تاريخ الانضمام</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($partners as $partner)
                            <tr>
                                <td class="fw-semibold">{{ $partner->name }}</td>
                                <td>{{ $partner->phone ?: '—' }}</td>
                                <td>{{ optional($partner->join_date)->format('Y-m-d') ?: '—' }}</td>
                                <td><span class="badge text-bg-light">{{ \App\Models\Partner::STATUSES[$partner->status] ?? $partner->status }}</span></td>
                                <td class="text-end">
                                    @can('partners.edit')
                                        <a href="{{ route('partners.edit', $partner) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    @endcan
                                    @can('partners.delete')
                                        <form method="POST" action="{{ route('partners.destroy', $partner) }}" class="d-inline"
                                              onsubmit="return confirm('متأكد من حذف الشريك؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">لا يوجد شركاء بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $partners->links() }}
        </div>
    </div>
@endsection
