@extends('layouts.app')

@section('title', $user->exists ? 'تعديل مستخدم' : 'مستخدم جديد')

@php($roleLabels = ['admin' => 'مدير النظام', 'manager' => 'مدير', 'accountant' => 'محاسب', 'employee' => 'موظف'])
@php($isSelf = $user->exists && $user->id === auth()->id())

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}">
                @csrf
                @if ($user->exists) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">الاسم <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                        <input type="email" dir="ltr" name="email" value="{{ old('email', $user->email) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">كلمة المرور @if(!$user->exists)<span class="text-danger">*</span>@else<span class="text-muted small">(اتركها فارغة لعدم التغيير)</span>@endif</label>
                        <input type="password" name="password" class="form-control" @if(!$user->exists) required @endif>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الهاتف</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الدور <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" @disabled($isSelf)>
                            @foreach ($roles as $r)
                                <option value="{{ $r }}" @selected(old('role', $user->getRoleNames()->first()) === $r)>{{ $roleLabels[$r] ?? $r }}</option>
                            @endforeach
                        </select>
                        @if ($isSelf)<div class="form-text text-warning">لا يمكنك تغيير دورك الخاص.</div>@endif
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input" @checked(old('is_active', $user->is_active ?? true)) @disabled($isSelf)>
                            <label for="is_active" class="form-check-label">حساب نشط</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('users.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
