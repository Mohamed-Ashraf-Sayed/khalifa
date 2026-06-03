<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Expense;
use App\Models\Project;
use App\Services\BankLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExpenseController extends Controller implements HasMiddleware
{
    public function __construct(private readonly BankLedgerService $ledger) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:expenses.view', only: ['index']),
            new Middleware('can:expenses.create', only: ['create', 'store']),
            new Middleware('can:expenses.edit', only: ['edit', 'update']),
            new Middleware('can:expenses.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $category = (string) $request->input('category', '');

        $expenses = Expense::query()
            ->with(['project', 'bankAccount'])
            ->when($category !== '', fn ($q) => $q->where('category', $category))
            ->latest('expense_date')
            ->paginate(15)
            ->withQueryString();

        $total = Expense::when($category !== '', fn ($q) => $q->where('category', $category))->sum('amount');

        return view('expenses.index', compact('expenses', 'category', 'total'));
    }

    public function create(): View
    {
        return view('expenses.form', $this->formData(new Expense(['expense_date' => now()->toDateString(), 'category' => 'other', 'payment_method' => 'cash'])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;

        DB::transaction(function () use ($data) {
            $expense = Expense::create($data);
            $this->syncBankTransaction($expense);
        });

        return redirect()->route('expenses.index')->with('success', 'تمت إضافة المصروف.');
    }

    public function edit(Expense $expense): View
    {
        return view('expenses.form', $this->formData($expense));
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $data = $this->validateData($request);

        DB::transaction(function () use ($expense, $data) {
            $expense->update($data);
            $this->syncBankTransaction($expense);
        });

        return redirect()->route('expenses.index')->with('success', 'تم تحديث المصروف.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        DB::transaction(function () use ($expense) {
            $this->removeLinkedBankTransaction($expense);
            $expense->delete();
        });

        return back()->with('success', 'تم حذف المصروف.');
    }

    /**
     * يضمن تطابق الحركة البنكية المرتبطة مع حالة المصروف الحالية:
     * يحذف أي حركة سابقة، ثم يسجّل سحباً جديداً لو المصروف مدفوع من حساب بنكي.
     * (يتجنّب باج النظام القديم: عكس حركة قد لا تكون موجودة).
     */
    private function syncBankTransaction(Expense $expense): void
    {
        $this->removeLinkedBankTransaction($expense);

        if ($expense->bank_account_id) {
            $account = BankAccount::findOrFail($expense->bank_account_id);
            $this->ledger->post($account, [
                'type' => 'withdrawal',
                'amount' => $expense->amount,
                'transaction_date' => $expense->expense_date,
                'description' => 'مصروف: '.$expense->description,
                'related_type' => 'expense',
                'related_id' => $expense->id,
                'created_by' => $expense->created_by,
            ]);
        }
    }

    private function removeLinkedBankTransaction(Expense $expense): void
    {
        BankTransaction::where('related_type', 'expense')
            ->where('related_id', $expense->id)
            ->get()
            ->each(fn (BankTransaction $t) => $this->ledger->deleteTransaction($t));
    }

    private function formData(Expense $expense): array
    {
        return [
            'expense' => $expense,
            'projects' => Project::orderBy('name')->get(),
            'accounts' => BankAccount::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'project_id' => ['nullable', 'exists:projects,id'],
            'category' => ['required', 'in:'.implode(',', array_keys(Expense::CATEGORIES))],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'expense_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:'.implode(',', array_keys(Expense::PAYMENT_METHODS))],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
