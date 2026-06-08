@extends('layouts.app')

@section('title', 'بيانات المستخدم')

@php($roleLabels = ['admin' => 'مدير النظام', 'manager' => 'مدير', 'accountant' => 'محاسب', 'employee' => 'موظف'])
@php($role = $user->getRoleNames()->first())
@php($isSelf = auth()->id() === $user->id)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">{{ $user->name }}</h5>
        <div class="d-flex gap-2">
            @can('users.edit')
                <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen ms-1"></i> تعديل</a>
                @unless ($isSelf)
                    <form method="POST" action="{{ route('users.toggle_active', $user) }}" class="m-0" data-confirm="{{ $user->is_active ? 'تعطيل هذا الحساب؟' : 'تفعيل هذا الحساب؟' }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-outline-{{ $user->is_active ? 'warning' : 'success' }}">
                            <i class="fa-solid fa-{{ $user->is_active ? 'ban' : 'circle-check' }} ms-1"></i> {{ $user->is_active ? 'تعطيل' : 'تفعيل' }}
                        </button>
                    </form>
                @endunless
            @endcan
            @can('users.delete')
                @unless ($isSelf)
                    <form method="POST" action="{{ route('users.destroy', $user) }}" class="m-0" data-confirm="حذف المستخدم؟">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash ms-1"></i> حذف</button>
                    </form>
                @endunless
            @endcan
            <a href="{{ route('users.index') }}" class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-3">
                @if ($user->avatar)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($user->avatar) }}" alt="الصورة الشخصية" class="rounded-circle" style="width:72px;height:72px;object-fit:cover;border:2px solid #2b4c80">
                @else
                    <i class="fa-solid fa-user-circle" style="font-size:72px;color:#2b4c80"></i>
                @endif
                <div>
                    <div class="fw-semibold fs-5">{{ $user->name }}</div>
                    <div class="text-muted small" dir="ltr">{{ $user->email }}</div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">الاسم</div><div class="fw-semibold">{{ $user->name }}</div></div>
                <div class="col-md-4"><div class="text-muted small">البريد</div><div dir="ltr" class="text-end">{{ $user->email }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الهاتف</div><div dir="ltr" class="text-end">{{ $user->phone ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">الدور</div><div><span class="badge text-bg-secondary">{{ $roleLabels[$role] ?? $role ?? '—' }}</span></div></div>
                <div class="col-md-4"><div class="text-muted small">الحالة</div><div><span class="badge text-bg-{{ $user->is_active ? 'success' : 'secondary' }}">{{ $user->is_active ? 'نشط' : 'معطّل' }}</span></div></div>
                <div class="col-md-4"><div class="text-muted small">المصادقة الثنائية</div><div><span class="badge text-bg-{{ $user->two_factor_enabled ? 'success' : 'secondary' }}"><i class="fa-solid fa-{{ $user->two_factor_enabled ? 'shield-halved' : 'shield' }} ms-1"></i>{{ $user->two_factor_enabled ? 'مفعّلة' : 'غير مفعّلة' }}</span></div></div>
                <div class="col-md-4"><div class="text-muted small">تاريخ الإضافة</div><div>{{ $user->created_at?->format('Y-m-d') ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">آخر دخول</div><div>{{ $user->last_login_at?->format('Y-m-d H:i') ?: '—' }}</div></div>
            </div>
        </div>
    </div>

    @can('users.edit')
        <div class="card mb-3">
            <div class="card-header fw-semibold" style="background:#f3efe9"><i class="fa-solid fa-key ms-1"></i> إعادة تعيين كلمة المرور</div>
            <div class="card-body">
                <form method="POST" action="{{ route('users.reset_password', $user) }}" class="row g-3">
                    @csrf
                    <div class="col-md-5">
                        <label class="form-label">كلمة المرور الجديدة <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" minlength="8" required>
                        @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">تأكيد كلمة المرور <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control" minlength="8" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn w-100" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection
