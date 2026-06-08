<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:accounting.view', only: ['index', 'show']),
            new Middleware('can:accounting.create', only: ['create', 'store']),
            new Middleware('can:accounting.edit', only: ['edit', 'update']),
            new Middleware('can:accounting.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $accounts = Account::orderBy('code')->get();

        // بناء الشجرة مجمّعة حسب النوع: جذور كل نوع ثم أبناؤها بالتداخل عبر parent_id.
        $byParent = $accounts->groupBy('parent_id');
        $roots = $accounts->groupBy('type');

        $stats = [
            'count' => $accounts->count(),
            'groups' => $accounts->where('is_group', true)->count(),
            'leaves' => $accounts->where('is_group', false)->count(),
            'inactive' => $accounts->where('is_active', false)->count(),
        ];

        return view('accounts.index', compact('accounts', 'byParent', 'roots', 'stats'));
    }

    public function create(): View
    {
        return view('accounts.form', [
            'account' => new Account(['normal_balance' => 'debit', 'type' => 'asset', 'is_active' => true, 'opening_balance' => 0]),
            'parents' => Account::where('is_group', true)->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        Account::create($data);

        return redirect()->route('accounts.index')->with('success', 'تمت إضافة الحساب بنجاح.');
    }

    public function edit(Account $account): View
    {
        return view('accounts.form', [
            'account' => $account,
            'parents' => Account::where('is_group', true)->where('id', '!=', $account->id)->orderBy('code')->get(),
        ]);
    }

    public function update(Request $request, Account $account): RedirectResponse
    {
        $account->update($this->validateData($request, $account));

        return redirect()->route('accounts.index')->with('success', 'تم تحديث الحساب.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        if ($account->children()->exists()) {
            return back()->with('error', 'لا يمكن حذف حساب له حسابات فرعية.');
        }

        if ($account->lines()->exists()) {
            return back()->with('error', 'لا يمكن حذف حساب عليه قيود محاسبية.');
        }

        $account->delete();

        return back()->with('success', 'تم حذف الحساب.');
    }

    private function validateData(Request $request, ?Account $account = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('accounts', 'code')->ignore($account?->id)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.implode(',', array_keys(Account::TYPES))],
            'parent_id' => ['nullable', Rule::exists('accounts', 'id')->where('is_group', true)],
            'normal_balance' => ['required', 'in:'.implode(',', array_keys(Account::NORMAL))],
            'is_group' => ['nullable', 'boolean'],
            'opening_balance' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:255'],
        ], [
            'parent_id.exists' => 'الحساب الأب يجب أن يكون حساباً تجميعياً.',
        ]);
    }
}
