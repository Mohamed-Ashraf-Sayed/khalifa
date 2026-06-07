@extends('layouts.app')

@section('title', 'إعدادات النظام')

@section('content')
    <ul class="nav nav-tabs mb-3" id="settingsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="company-tab" data-bs-toggle="tab" data-bs-target="#company" type="button" role="tab">
                <i class="fa-solid fa-building ms-1"></i> بيانات الشركة
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="finance-tab" data-bs-toggle="tab" data-bs-target="#finance" type="button" role="tab">
                <i class="fa-solid fa-money-bill-wave ms-1"></i> الإعدادات المالية
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="alerts-tab" data-bs-toggle="tab" data-bs-target="#alerts" type="button" role="tab">
                <i class="fa-solid fa-bell ms-1"></i> التنبيهات
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab">
                <i class="fa-solid fa-circle-info ms-1"></i> معلومات النظام
            </button>
        </li>
    </ul>

    <form method="POST" action="{{ route('settings.update') }}">
        @csrf @method('PUT')
        <div class="tab-content">
            {{-- (1) بيانات الشركة --}}
            <div class="tab-pane fade show active" id="company" role="tabpanel">
                <div class="card">
                    <div class="card-body p-4">
                        <h5 class="mb-3">بيانات الشركة</h5>
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
                            <div class="col-md-4">
                                <label class="form-label">الشكل القانوني</label>
                                <input type="text" name="legal_form" value="{{ old('legal_form', $settings['legal_form'] ?? '') }}" class="form-control" placeholder="شركة فردية">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">السجل التجاري</label>
                                <input type="text" name="commercial_register" value="{{ old('commercial_register', $settings['commercial_register'] ?? '') }}" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">الرقم القومي/الضريبي للمنشأة</label>
                                <input type="text" name="tax_number" value="{{ old('tax_number', $settings['tax_number'] ?? '') }}" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- (2) الإعدادات المالية --}}
            <div class="tab-pane fade" id="finance" role="tabpanel">
                <div class="card">
                    <div class="card-body p-4">
                        <h5 class="mb-3">الإعدادات المالية</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">نسبة الضريبة (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="tax_rate" value="{{ old('tax_rate', $settings['tax_rate']) }}" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">نسبة المحتجز (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="retention_rate" value="{{ old('retention_rate', $settings['retention_rate']) }}" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">نسبة التأمين (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="insurance_rate" value="{{ old('insurance_rate', $settings['insurance_rate']) }}" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">العملة</label>
                                @php $currency = old('currency', $settings['currency']); @endphp
                                <select name="currency" class="form-select">
                                    @foreach (['ج.م', 'ر.س', 'د.إ', 'د.ك', '$'] as $cur)
                                        <option value="{{ $cur }}" @selected($currency === $cur)>{{ $cur }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">المنطقة الزمنية</label>
                                @php $tz = old('timezone', $settings['timezone']); @endphp
                                <select name="timezone" class="form-select">
                                    @foreach (['Africa/Cairo', 'Asia/Riyadh', 'Asia/Dubai', 'Asia/Kuwait'] as $zone)
                                        <option value="{{ $zone }}" @selected($tz === $zone)>{{ $zone }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- (3) التنبيهات --}}
            <div class="tab-pane fade" id="alerts" role="tabpanel">
                <div class="card">
                    <div class="card-body p-4">
                        <h5 class="mb-3">التنبيهات</h5>
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="low_stock_alert" value="0">
                                    <input type="checkbox" name="low_stock_alert" value="1" id="low_stock_alert" class="form-check-input" @checked(old('low_stock_alert', $settings['low_stock_alert']) == '1')>
                                    <label class="form-check-label" for="low_stock_alert">تفعيل تنبيه نقص المخزون</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">أيام التذكير بالدفعات</label>
                                <input type="number" min="0" max="365" name="payment_reminder_days" value="{{ old('payment_reminder_days', $settings['payment_reminder_days']) }}" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- (4) معلومات النظام (عرض فقط) --}}
            <div class="tab-pane fade" id="system" role="tabpanel">
                <div class="card">
                    <div class="card-body p-4">
                        <h5 class="mb-3">معلومات النظام</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <table class="table table-sm mb-0">
                                    <tbody>
                                        <tr><th class="text-muted" style="width:50%">إصدار PHP</th><td dir="ltr">{{ $systemInfo['php_version'] }}</td></tr>
                                        <tr><th class="text-muted">إصدار Laravel</th><td dir="ltr">{{ $systemInfo['laravel_version'] }}</td></tr>
                                        <tr><th class="text-muted">قاعدة البيانات</th><td dir="ltr">{{ $systemInfo['db_driver'] }}</td></tr>
                                        <tr><th class="text-muted">المنطقة الزمنية للخادم</th><td dir="ltr">{{ $systemInfo['timezone'] }}</td></tr>
                                        <tr><th class="text-muted">وقت الخادم</th><td dir="ltr">{{ now()->format('Y-m-d H:i:s') }}</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm mb-0">
                                    <tbody>
                                        <tr><th class="text-muted" style="width:50%">المستخدمين</th><td>{{ $counts['users'] }}</td></tr>
                                        <tr><th class="text-muted">المشاريع</th><td>{{ $counts['projects'] }}</td></tr>
                                        <tr><th class="text-muted">العملاء</th><td>{{ $counts['clients'] }}</td></tr>
                                        <tr><th class="text-muted">المقاولين</th><td>{{ $counts['contractors'] }}</td></tr>
                                        <tr><th class="text-muted">الموردين</th><td>{{ $counts['suppliers'] }}</td></tr>
                                        <tr><th class="text-muted">الموظفين</th><td>{{ $counts['employees'] }}</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <button class="btn" style="background:#8b7355;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ الإعدادات</button>
        </div>
    </form>
@endsection
