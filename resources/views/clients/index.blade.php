@extends('layouts.app')

@section('title', 'العملاء')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2" method="GET">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بحث بالاسم أو الشركة أو الهاتف">
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                @can('clients.create')
                    <a href="{{ route('clients.create') }}" class="btn btn-brown" style="background:#8b7355;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> عميل جديد
                    </a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>الاسم</th>
                            <th>الشركة</th>
                            <th>الهاتف</th>
                            <th>المدينة</th>
                            <th>المشاريع</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clients as $client)
                            <tr>
                                <td class="fw-semibold">{{ $client->name }}</td>
                                <td>{{ $client->company_name ?: '—' }}</td>
                                <td>{{ $client->phone }}</td>
                                <td>{{ $client->city ?: '—' }}</td>
                                <td><span class="badge text-bg-light">{{ $client->projects_count }}</span></td>
                                <td class="text-end">
                                    @can('clients.edit')
                                        <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    @endcan
                                    @can('clients.delete')
                                        <form method="POST" action="{{ route('clients.destroy', $client) }}" class="d-inline"
                                              onsubmit="return confirm('متأكد من حذف العميل؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">لا يوجد عملاء بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $clients->links() }}
        </div>
    </div>
@endsection
