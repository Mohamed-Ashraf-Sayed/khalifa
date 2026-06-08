@extends('layouts.app')

@section('title', 'المستخدمون')

@php($roleLabels = ['admin' => 'مدير النظام', 'manager' => 'مدير', 'accountant' => 'محاسب', 'employee' => 'موظف'])

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2 flex-wrap" method="GET">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" style="min-width:180px" placeholder="بحث بالاسم أو البريد">
                    <select name="role" class="form-select" style="min-width:150px" onchange="this.form.submit()">
                        <option value="">كل الأدوار</option>
                        @foreach ($roles as $r)
                            <option value="{{ $r }}" @selected($role === $r)>{{ $roleLabels[$r] ?? $r }}</option>
                        @endforeach
                    </select>
                    <select name="is_active" class="form-select" style="min-width:140px" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        <option value="1" @selected($isActive === '1')>نشط</option>
                        <option value="0" @selected($isActive === '0')>معطّل</option>
                    </select>
                    <button type="submit" class="btn btn-outline-secondary" title="بحث"><i class="fa-solid fa-magnifying-glass"></i></button>
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary" title="مسح الفلاتر"><i class="fa-solid fa-xmark"></i></a>
                </form>
                @can('users.create')
                    <a href="{{ route('users.create') }}" class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-plus ms-1"></i> مستخدم جديد</a>
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
                            <th>آخر دخول</th>
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
                                <td class="small text-muted">{{ $user->last_login_at?->format('Y-m-d H:i') ?: '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-outline-secondary" title="عرض"><i class="fa-solid fa-eye"></i></a>
                                    @can('users.edit')
                                        <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('users.delete')
                                        @if ($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline" data-confirm="حذف المستخدم؟">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">لا يوجد مستخدمون.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $users->links() }}
        </div>
    </div>
@endsection
