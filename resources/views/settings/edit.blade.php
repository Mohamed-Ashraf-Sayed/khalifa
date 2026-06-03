@extends('layouts.app')

@section('title', 'إعدادات النظام')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('settings.update') }}">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">اسم النظام / الشركة (يظهر في الواجهة)</label>
                        <input type="text" name="site_name" value="{{ old('site_name', $settings['site_name']) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الاسم القانوني للشركة</label>
                        <input type="text" name="company_name" value="{{ old('company_name', $settings['company_name']) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الهاتف</label>
                        <input type="text" name="company_phone" value="{{ old('company_phone', $settings['company_phone']) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" dir="ltr" name="company_email" value="{{ old('company_email', $settings['company_email']) }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">العنوان</label>
                        <textarea name="company_address" rows="2" class="form-control">{{ old('company_address', $settings['company_address']) }}</textarea>
                    </div>
                </div>
                <div class="mt-4">
                    <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ الإعدادات</button>
                </div>
            </form>
        </div>
    </div>
@endsection
