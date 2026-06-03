@extends('layouts.app')

@section('title', 'الملف الشخصي')

@section('content')
    <div class="row g-3">
        {{-- البيانات الشخصية --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body p-4">
                    <h5 class="mb-3"><i class="fa-solid fa-user ms-1" style="color:#8b7355"></i> البيانات الشخصية</h5>
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">الاسم <span class="text-danger">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                            <input type="email" dir="ltr" name="email" value="{{ old('email', $user->email) }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الهاتف</label>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control">
                        </div>
                        <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ البيانات</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- تغيير كلمة المرور --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body p-4">
                    <h5 class="mb-3"><i class="fa-solid fa-key ms-1" style="color:#8b7355"></i> تغيير كلمة المرور</h5>
                    <form method="POST" action="{{ route('profile.password') }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">كلمة المرور الحالية <span class="text-danger">*</span></label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">كلمة المرور الجديدة <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">تأكيد كلمة المرور <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                        <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> تحديث كلمة المرور</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- الصورة الشخصية --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body p-4">
                    <h5 class="mb-3"><i class="fa-solid fa-image ms-1" style="color:#8b7355"></i> الصورة الشخصية</h5>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        @if ($user->avatar)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($user->avatar) }}" alt="الصورة الشخصية" class="rounded-circle" style="width:80px;height:80px;object-fit:cover;border:2px solid #8b7355">
                        @else
                            <i class="fa-solid fa-user-circle" style="font-size:80px;color:#8b7355"></i>
                        @endif
                        @if ($user->avatar)
                            <form method="POST" action="{{ route('profile.avatar.delete') }}" onsubmit="return confirm('حذف الصورة الشخصية؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash ms-1"></i> حذف الصورة</button>
                            </form>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('profile.avatar') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">رفع صورة جديدة</label>
                            <input type="file" name="avatar" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif" required>
                            <div class="form-text">JPG, PNG, WEBP, GIF — بحد أقصى 2 ميجابايت.</div>
                        </div>
                        <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-upload ms-1"></i> رفع الصورة</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
