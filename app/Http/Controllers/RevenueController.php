<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Project;
use App\Models\Revenue;
use App\Services\BankLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RevenueController extends Controller implements HasMiddleware
{
    public function __construct(private readonly BankLedgerService $ledger) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:revenues.view', only: ['index', 'show']),
            new Middleware('can:revenues.create', only: ['create', 'store']),
            new Middleware('can:revenues.edit', only: ['edit', 'update']),
            new Middleware('can:revenues.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $revenues = Revenue::query()
            ->with(['project', 'bankAccount'])
            ->latest('revenue_date')
            ->paginate(15);

        $total = Revenue::sum('amount');

        return view('revenues.index', compact('revenues', 'total'));
    }

    public function show(Revenue $revenue): View
    {
        $revenue->load(['project', 'bankAccount', 'creator', 'collections.bankAccount']);
        $accounts = BankAccount::where('is_active', true)->orderBy('name')->get();

        return view('revenues.show', compact('revenue', 'accounts'));
    }

    public function create(): View
    {
        return view('revenues.form', $this->formData(new Revenue(['revenue_date' => now()->toDateString(), 'payment_method' => 'cash'])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;

        DB::transaction(function () use ($data) {
            $revenue = Revenue::create($data);
            $this->syncBankTransaction($revenue);
            $revenue->refreshCollectionStatus();
        });

        return redirect()->route('revenues.index')->with('success', 'تمت إضافة الإيراد.');
    }

    public function edit(Revenue $revenue): View
    {
        return view('revenues.form', $this->formData($revenue));
    }

    public function update(Request $request, Revenue $revenue): RedirectResponse
    {
        $data = $this->validateData($request);

        DB::transaction(function () use ($revenue, $data) {
            $revenue->update($data);
            $this->syncBankTransaction($revenue);
            $revenue->refreshCollectionStatus();
        });

        return redirect()->route('revenues.index')->with('success', 'تم تحديث الإيراد.');
    }

    public function destroy(Revenue $revenue): RedirectResponse
    {
        DB::transaction(function () use ($revenue) {
            $this->removeLinkedBankTransaction($revenue);
            $revenue->delete();
        });

        return back()->with('success', 'تم حذف الإيراد.');
    }

    /**
     * يضمن تطابق الإيداع البنكي المرتبط مع حالة الإيراد:
     * يحذف أي إيداع سابق ثم يسجّل إيداعاً جديداً لو الإيراد مستلم في حساب بنكي.
     */
    private function syncBankTransaction(Revenue $revenue): void
    {
        $this->removeLinkedBankTransaction($revenue);

        if ($revenue->bank_account_id) {
            $account = BankAccount::findOrFail($revenue->bank_account_id);
            $this->ledger->post($account, [
                'type' => 'deposit',
                'amount' => $revenue->amount,
                'transaction_date' => $revenue->revenue_date,
                'description' => 'إيراد: '.$revenue->description,
                'related_type' => 'revenue',
                'related_id' => $revenue->id,
                'created_by' => $revenue->created_by,
            ]);
        }
    }

    private function removeLinkedBankTransaction(Revenue $revenue): void
    {
        BankTransaction::where('related_type', 'revenue')
            ->where('related_id', $revenue->id)
            ->get()
            ->each(fn (BankTransaction $t) => $this->ledger->deleteTransaction($t));
    }

    private function formData(Revenue $revenue): array
    {
        return [
            'revenue' => $revenue,
            'projects' => Project::orderBy('name')->get(),
            'accounts' => BankAccount::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'project_id' => ['nullable', 'exists:projects,id'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'revenue_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:'.implode(',', array_keys(Revenue::PAYMENT_METHODS))],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'due_date' => ['nullable', 'date'],
            'check_number' => ['nullable', 'string', 'max:50'],
            'deferred_check' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
