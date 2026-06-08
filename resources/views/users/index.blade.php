@extends('layouts.app')

@section('title', 'المستخدمون')

@php($roleLabels = ['admin' => 'مدير النظام', 'manager' => 'مدير', 'accountant' => 'محاسب', 'employee' => 'موظف'])

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end gap-2 mb-3">
                @can('users.create')
                    <a href="{{ route('users.create') }}" class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-plus ms-1"></i> مستخدم جديد</a>
                @endcan
            </div>

            <form method="GET" class="filter-bar row g-2 align-items-end mb-3">
                <div class="col-6 col-md-3">
                    <label class="form-label">بحث</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">الدور</label>
                    <select name="role" class="form-select" onchange="this.form.submit()">
                        <option value="">كل الأدوار</option>
                        @foreach ($roles as $r)
                            <option value="{{ $r }}" @selected($role === $r)>{{ $roleLabels[$r] ?? $r }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">الحالة</label>
                    <select name="is_active" class="form-select" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        <option value="1" @selected($isActive === '1')>نشط</option>
                        <option value="0" @selected($isActive === '0')>معطّل</option>
                    </select>
                </div>
                <div class="col-12 col-md-auto">
                    <div class="filter-actions">
                        <button class="btn btn-primary"><i class="fa-solid fa-magnifying-glass ms-1"></i> بحث</button>
                        @if (request()->query())
                            <a href="{{ url()->current() }}" class="btn btn-light">مسح</a>
                        @endif
                    </div>
                </div>
            </form>

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
