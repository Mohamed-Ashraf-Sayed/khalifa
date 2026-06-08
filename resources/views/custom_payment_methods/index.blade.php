@extends('layouts.app')

@section('title', 'طرق الدفع')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2" method="GET">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="بحث باسم الطريقة">
                    <button class="btn btn-outline-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
                @can('bank_accounts.create')
                    <a href="{{ route('payment_methods.create') }}" class="btn" style="background:#8b7355;color:#fff">
                        <i class="fa-solid fa-plus ms-1"></i> طريقة دفع جديدة
                    </a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>الاسم</th>
                            <th>الكود</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($methods as $method)
                            <tr>
                                <td class="fw-semibold">{{ $method->name }}</td>
                                <td>{{ $method->code ?: '—' }}</td>
                                <td><span class="badge text-bg-{{ $method->is_active ? 'success' : 'secondary' }}">{{ $method->is_active ? 'نشط' : 'معطّل' }}</span></td>
                                <td class="text-end">
                                    @can('bank_accounts.edit')
                                        <a href="{{ route('payment_methods.edit', $method) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('bank_accounts.delete')
                                        <form method="POST" action="{{ route('payment_methods.destroy', $method) }}" class="d-inline"
                                              data-confirm="متأكد من حذف طريقة الدفع؟">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">لا توجد طرق دفع بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $methods->links() }}
        </div>
    </div>
@endsection
