@extends('layouts.app')

@section('title', 'الأدوار والصلاحيات')

@php($roleLabels = ['admin' => 'مدير النظام', 'manager' => 'مدير', 'accountant' => 'محاسب', 'employee' => 'موظف'])

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h5 class="m-0"><i class="fa-solid fa-user-shield ms-1"></i> الأدوار والصلاحيات</h5>
                @can('users.create')
                    <a href="{{ route('roles.create') }}" class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-plus ms-1"></i> دور جديد</a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>الدور</th>
                            <th>عدد الصلاحيات</th>
                            <th>عدد المستخدمين</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr>
                                <td class="fw-semibold">
                                    <span class="badge text-bg-secondary">{{ $roleLabels[$role->name] ?? $role->name }}</span>
                                </td>
                                <td><span class="badge text-bg-info">{{ $role->permissions_count }}</span></td>
                                <td><span class="badge text-bg-light">{{ $role->users_count }}</span></td>
                                <td class="text-end">
                                    @can('users.edit')
                                        <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('users.delete')
                                        @if ($role->name !== 'admin')
                                            <form method="POST" action="{{ route('roles.destroy', $role) }}" class="d-inline" data-confirm="حذف الدور؟">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">لا توجد أدوار.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
