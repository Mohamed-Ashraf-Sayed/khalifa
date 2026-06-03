<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * الوحدات اللي ليها صلاحيات CRUD كاملة.
     */
    private const CRUD_MODULES = [
        'projects', 'clients', 'contractors', 'suppliers', 'employees',
        'partners', 'materials', 'expenses', 'revenues', 'invoices',
        'bank_accounts', 'purchase_orders', 'assets', 'contracts', 'taxes',
    ];

    /**
     * صلاحيات مخصّصة مش CRUD قياسي.
     */
    private const EXTRA_PERMISSIONS = [
        'reports.view', 'reports.export',
        'settings.view', 'settings.edit',
        'users.view', 'users.create', 'users.edit', 'users.delete',
    ];

    public function run(): void
    {
        // تفريغ الكاش عشان الصلاحيات الجديدة تتسجّل
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1) إنشاء كل الصلاحيات
        $permissions = self::EXTRA_PERMISSIONS;
        foreach (self::CRUD_MODULES as $module) {
            foreach (['view', 'create', 'edit', 'delete'] as $action) {
                $permissions[] = "{$module}.{$action}";
            }
        }

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // 2) إنشاء الأدوار وربط الصلاحيات
        // admin: كل الصلاحيات (وكمان بيتجاوز الفحص عبر Gate::before)
        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions(Permission::all());

        // manager: إدارة كاملة للتشغيل عدا المستخدمين وحذف الماليات الحسّاسة
        $manager = Role::findOrCreate('manager', 'web');
        $manager->syncPermissions($this->managerPermissions());

        // accountant: الماليات (عرض/إضافة/تعديل) بدون حذف البيانات الأساسية
        $accountant = Role::findOrCreate('accountant', 'web');
        $accountant->syncPermissions($this->accountantPermissions());

        // employee: عرض عام + إدارة العملاء فقط
        $employee = Role::findOrCreate('employee', 'web');
        $employee->syncPermissions($this->employeePermissions());
    }

    private function managerPermissions(): array
    {
        $perms = [];
        foreach (['projects', 'clients', 'contractors', 'suppliers', 'employees', 'partners', 'materials', 'purchase_orders', 'assets', 'contracts'] as $m) {
            foreach (['view', 'create', 'edit', 'delete'] as $a) {
                $perms[] = "{$m}.{$a}";
            }
        }
        foreach (['expenses', 'revenues', 'invoices', 'bank_accounts', 'taxes'] as $m) {
            foreach (['view', 'create', 'edit'] as $a) {
                $perms[] = "{$m}.{$a}";
            }
        }
        return array_merge($perms, ['reports.view', 'reports.export']);
    }

    private function accountantPermissions(): array
    {
        $perms = [];
        foreach (['expenses', 'revenues', 'invoices', 'bank_accounts', 'purchase_orders'] as $m) {
            foreach (['view', 'create', 'edit'] as $a) {
                $perms[] = "{$m}.{$a}";
            }
        }
        foreach (['projects', 'clients', 'contractors', 'suppliers', 'partners'] as $m) {
            $perms[] = "{$m}.view";
        }
        return array_merge($perms, ['reports.view', 'reports.export']);
    }

    private function employeePermissions(): array
    {
        return [
            'projects.view',
            'clients.view', 'clients.create', 'clients.edit',
            'contractors.view', 'suppliers.view',
        ];
    }
}
