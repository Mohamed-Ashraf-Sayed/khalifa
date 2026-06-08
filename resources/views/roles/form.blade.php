@extends('layouts.app')

@section('title', $role->exists ? 'تعديل دور' : 'دور جديد')

@php($roleLabels = ['admin' => 'مدير النظام', 'manager' => 'مدير', 'accountant' => 'محاسب', 'employee' => 'موظف'])
@php($isAdmin = $role->exists && $role->name === 'admin')
@php($groupLabels = [
    'projects' => 'المشاريع', 'clients' => 'العملاء', 'contractors' => 'المقاولون',
    'suppliers' => 'الموردون', 'employees' => 'الموظفون', 'partners' => 'الشركاء',
    'materials' => 'المواد', 'expenses' => 'المصروفات', 'revenues' => 'الإيرادات',
    'invoices' => 'الفواتير', 'bank_accounts' => 'الحسابات البنكية', 'purchase_orders' => 'أوامر الشراء',
    'assets' => 'الأصول', 'contracts' => 'العقود', 'taxes' => 'الضرائب',
    'reports' => 'التقارير', 'settings' => 'الإعدادات', 'users' => 'المستخدمون',
])
@php($actionLabels = ['view' => 'عرض', 'create' => 'إضافة', 'edit' => 'تعديل', 'delete' => 'حذف', 'export' => 'تصدير'])
@php($oldPerms = old('permissions', $assigned))

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ $role->exists ? route('roles.update', $role) : route('roles.store') }}">
                @csrf
                @if ($role->exists) @method('PUT') @endif

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">اسم الدور <span class="text-danger">*</span></label>
                        <input type="text" dir="ltr" name="name" value="{{ old('name', $role->name) }}" class="form-control text-start" required @disabled($isAdmin)>
                        @if ($isAdmin)<div class="form-text text-warning">اسم دور مدير النظام ثابت ولا يمكن تغييره.</div>@endif
                        @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="m-0 fw-semibold">الصلاحيات</h6>
                    <div class="form-check">
                        <input type="checkbox" id="check_all" class="form-check-input" onclick="document.querySelectorAll('.perm-cb').forEach(c => c.checked = this.checked)">
                        <label for="check_all" class="form-check-label small">تحديد الكل</label>
                    </div>
                </div>

                <div class="row g-3">
                    @foreach ($groupedPermissions as $group => $permissions)
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100">
                                <div class="card-header py-2 fw-semibold" style="background:#f3efe9">
                                    {{ $groupLabels[$group] ?? $group }}
                                </div>
                                <div class="card-body py-2">
                                    @foreach ($permissions as $permission)
                                        @php($parts = explode('.', $permission->name))
                                        @php($action = $parts[1] ?? $permission->name)
                                        <div class="form-check">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                                id="perm_{{ $permission->id }}" class="form-check-input perm-cb"
                                                @checked(in_array($permission->name, $oldPerms, true))>
                                            <label for="perm_{{ $permission->id }}" class="form-check-label small">
                                                {{ $actionLabels[$action] ?? $action }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($isAdmin)
                    <div class="alert alert-info mt-3 mb-0 small">مدير النظام يتجاوز كل فحوصات الصلاحيات تلقائياً.</div>
                @endif

                <div class="mt-4 d-flex gap-2">
                    <button class="btn" style="background:#2b4c80;color:#fff"><i class="fa-solid fa-floppy-disk ms-1"></i> حفظ</button>
                    <a href="{{ route('roles.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
