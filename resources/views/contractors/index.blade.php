@extends('layouts.app')

@section('title', 'المقاولون')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2" method="GET">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بحث بالاسم أو الشركة أو الهاتف أو الكود">
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                @can('contractors.create')
                    <a href="{{ route('contractors.create') }}" class="btn btn-brown" style="background:#2b4c80;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> مقاول جديد
                    </a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>الكود</th>
                            <th>الاسم</th>
                            <th>التخصص</th>
                            <th>الهاتف</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($contractors as $contractor)
                            <tr>
                                <td class="fw-semibold">{{ $contractor->contractor_code }}</td>
                                <td>{{ $contractor->name }}</td>
                                <td>{{ $contractor->specialty ?: '—' }}</td>
                                <td>{{ $contractor->phone ?: '—' }}</td>
                                <td>
                                    @if ($contractor->is_active)
                                        <span class="badge text-bg-success">نشط</span>
                                    @else
                                        <span class="badge text-bg-secondary">غير نشط</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('contractors.show', $contractor) }}" class="btn btn-sm btn-outline-secondary" title="عرض">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @can('contractors.edit')
                                        <a href="{{ route('contractors.edit', $contractor) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    @endcan
                                    @can('contractors.delete')
                                        <form method="POST" action="{{ route('contractors.destroy', $contractor) }}" class="d-inline"
                                              data-confirm="متأكد من حذف المقاول؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">لا يوجد مقاولون بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $contractors->links() }}
        </div>
    </div>
@endsection
