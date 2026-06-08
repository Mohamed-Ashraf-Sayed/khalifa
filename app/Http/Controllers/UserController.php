<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:users.view', only: ['index', 'show']),
            new Middleware('can:users.create', only: ['create', 'store']),
            new Middleware('can:users.edit', only: ['edit', 'update', 'resetPassword', 'toggleActive']),
            new Middleware('can:users.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $role = (string) $request->input('role', '');
        $isActive = (string) $request->input('is_active', '');
        $search = (string) $request->input('search', '');

        $users = User::query()
            ->with('roles')
            ->when($role !== '', fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $role)))
            ->when($isActive !== '', fn ($q) => $q->where('is_active', (int) $isActive))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'roles' => $this->roleList(),
            'role' => $role,
            'isActive' => $isActive,
            'search' => $search,
        ]);
    }

    public function show(User $user): View
    {
        $user->load('roles');

        return view('users.show', compact('user'));
    }

    public function create(): View
    {
        return view('users.form', ['user' => new User(['is_active' => true]), 'roles' => $this->roleList()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in($this->roleList())],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['sometimes'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // يتشفّر عبر cast
            'phone' => $data['phone'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);
        $user->syncRoles([$data['role']]);

        return redirect()->route('users.index')->with('success', 'تمت إضافة المستخدم.');
    }

    public function edit(User $user): View
    {
        return view('users.form', ['user' => $user, 'roles' => $this->roleList()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in($this->roleList())],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['sometimes'],
        ]);

        // امنع المدير من إلغاء تفعيل/تخفيض نفسه (قفل النفس خارج النظام)
        $isSelf = $request->user()->id === $user->id;

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'is_active' => $isSelf ? true : $request->boolean('is_active'),
        ]);
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        if (! $isSelf) {
            $user->syncRoles([$data['role']]);
        }

        return redirect()->route('users.index')->with('success', 'تم تحديث المستخدم.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->password = $data['password']; // يتشفّر عبر cast
        $user->save();

        return redirect()->route('users.show', $user)->with('success', 'تم تعيين كلمة مرور جديدة.');
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        // امنع المدير من تعطيل حسابه الخاص (قفل النفس خارج النظام)
        if ($request->user()->id === $user->id) {
            return back()->with('error', 'لا يمكنك تعطيل حسابك الخاص.');
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        return back()->with('success', $user->is_active ? 'تم تفعيل الحساب.' : 'تم تعطيل الحساب.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->id === $user->id) {
            return back()->with('error', 'لا يمكنك حذف حسابك الخاص.');
        }

        // امنع حذف آخر مدير
        if ($user->hasRole('admin') && User::role('admin')->count() <= 1) {
            return back()->with('error', 'لا يمكن حذف آخر مدير في النظام.');
        }

        $user->delete();

        return back()->with('success', 'تم حذف المستخدم.');
    }

    private function roleList(): array
    {
        return Role::orderBy('id')->pluck('name')->all();
    }
}
