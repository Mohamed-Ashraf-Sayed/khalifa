<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Services\BankLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class BankAccountController extends Controller implements HasMiddleware
{
    public function __construct(private readonly BankLedgerService $ledger) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:bank_accounts.view', only: ['index', 'show']),
            new Middleware('can:bank_accounts.create', only: ['create', 'store']),
            new Middleware('can:bank_accounts.edit', only: ['edit', 'update']),
            new Middleware('can:bank_accounts.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $accounts = BankAccount::query()->latest()->paginate(15);
        $total = BankAccount::where('is_active', true)->sum('current_balance');

        return view('bank_accounts.index', compact('accounts', 'total'));
    }

    public function create(): View
    {
        return view('bank_accounts.form', ['account' => new BankAccount(['currency' => 'EGP', 'is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        $data['current_balance'] = $data['opening_balance']; // يبدأ من الافتتاحي

        BankAccount::create($data);

        return redirect()->route('bank_accounts.index')->with('success', 'تمت إضافة الحساب البنكي.');
    }

    public function show(BankAccount $bank_account): View
    {
        $rows = $this->ledger->statement($bank_account);

        return view('bank_accounts.show', ['account' => $bank_account, 'rows' => $rows]);
    }

    public function edit(BankAccount $bank_account): View
    {
        return view('bank_accounts.form', ['account' => $bank_account]);
    }

    public function update(Request $request, BankAccount $bank_account): RedirectResponse
    {
        $bank_account->update($this->validateData($request));
        // تغيّر الرصيد الافتتاحي → أعِد اشتقاق الرصيد الحالي من المصدر
        $this->ledger->refreshBalance($bank_account);

        return redirect()->route('bank_accounts.index')->with('success', 'تم تحديث الحساب البنكي.');
    }

    public function destroy(BankAccount $bank_account): RedirectResponse
    {
        if ($bank_account->transactions()->exists()) {
            return back()->with('error', 'لا يمكن حذف حساب له حركات. احذف الحركات أولاً أو عطّل الحساب.');
        }

        $bank_account->delete();

        return back()->with('success', 'تم حذف الحساب البنكي.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bank_name' => ['required', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'iban' => ['nullable', 'string', 'max:50'],
            'branch' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'max:10'],
            'opening_balance' => ['required', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
