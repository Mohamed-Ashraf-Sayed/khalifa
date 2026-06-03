<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:users.view', only: ['index']),
            new Middleware('can:users.create', only: ['create', 'store']),
            new Middleware('can:users.edit', only: ['edit', 'update']),
            new Middleware('can:users.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $roles = Role::query()
            ->withCount(['permissions', 'users'])
            ->orderBy('id')
            ->get();

        return view('roles.index', compact('roles'));
    }

    public function create(): View
    {
        return view('roles.form', [
            'role' => new Role(),
            'groupedPermissions' => $this->groupedPermissions(),
            'assigned' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'تمت إضافة الدور.');
    }

    public function edit(Role $role): View
    {
        return view('roles.form', [
            'role' => $role,
            'groupedPermissions' => $this->groupedPermissions(),
            'assigned' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role)],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        // الدور admin اسمه ثابت حتى لا ينكسر Gate::before وفحوصات النظام
        if ($role->name !== 'admin') {
            $role->update(['name' => $data['name']]);
        }
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'تم تحديث الدور.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === 'admin') {
            return back()->with('error', 'لا يمكن حذف دور مدير النظام.');
        }

        $role->delete();

        return back()->with('success', 'تم حذف الدور.');
    }

    /**
     * كل الصلاحيات مجمّعة حسب الجزء قبل النقطة (prefix).
     *
     * @return array<string, \Illuminate\Support\Collection<int, Permission>>
     */
    private function groupedPermissions(): array
    {
        return Permission::orderBy('name')
            ->get()
            ->groupBy(fn (Permission $p) => str_contains($p->name, '.') ? explode('.', $p->name)[0] : 'عام')
            ->all();
    }
}
