@extends('layouts.app')

@section('title', 'المستخدمون')

@php($roleLabels = ['admin' => 'مدير النظام', 'manager' => 'مدير', 'accountant' => 'محاسب', 'employee' => 'موظف'])

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end mb-3">
                @can('users.create')
                    <a href="{{ route('users.create') }}" class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-plus ms-1"></i> مستخدم جديد</a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>الاسم</th>
                            <th>البريد</th>
                            <th>الدور</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            @php($role = $user->getRoleNames()->first())
                            <tr>
                                <td class="fw-semibold">{{ $user->name }}</td>
                                <td dir="ltr" class="text-start">{{ $user->email }}</td>
                                <td><span class="badge text-bg-secondary">{{ $roleLabels[$role] ?? $role ?? '—' }}</span></td>
                                <td><span class="badge text-bg-{{ $user->is_active ? 'success' : 'secondary' }}">{{ $user->is_active ? 'نشط' : 'معطّل' }}</span></td>
                                <td class="text-end">
                                    @can('users.edit')
                                        <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('users.delete')
                                        @if ($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline" onsubmit="return confirm('حذف المستخدم؟')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">لا يوجد مستخدمون.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $users->links() }}
        </div>
    </div>
@endsection
