<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Partner;
use App\Models\PartnerTransaction;
use App\Services\BankLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PartnerTransactionController extends Controller implements HasMiddleware
{
    public function __construct(private readonly BankLedgerService $ledger) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:partners.view', only: ['index', 'show']),
            new Middleware('can:partners.create', only: ['create', 'store']),
            new Middleware('can:partners.edit', only: ['edit', 'update']),
            new Middleware('can:partners.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $type = (string) $request->input('type', '');

        $transactions = PartnerTransaction::query()
            ->with('partner')
            ->when($search !== '', fn ($q) => $q->where('description', 'like', "%{$search}%"))
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->latest('transaction_date')
            ->paginate(15)
            ->withQueryString();

        return view('partner_transactions.index', compact('transactions', 'search', 'type'));
    }

    public function show(PartnerTransaction $partner_transaction): View
    {
        $partner_transaction->load(['partner', 'creator', 'bankAccount']);

        return view('partner_transactions.show', ['transaction' => $partner_transaction]);
    }

    public function create(): View
    {
        return view('partner_transactions.form', [
            'transaction' => new PartnerTransaction(['type' => 'deposit']),
            'partners' => Partner::orderBy('name')->get(),
            'accounts' => BankAccount::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;

        // السحب لا يجوز أن يتجاوز رصيد الشريك الحالي.
        if ($data['type'] === 'withdrawal') {
            $partner = Partner::findOrFail($data['partner_id']);
            if (bccomp((string) $data['amount'], $partner->currentBalance(), 2) > 0) {
                throw ValidationException::withMessages(['amount' => 'السحب يتجاوز رصيد الشريك']);
            }
        }

        DB::transaction(function () use ($data) {
            $transaction = PartnerTransaction::create($data);
            $this->syncBankTransaction($transaction);
        });

        return redirect()->route('partner_transactions.index')->with('success', 'تمت إضافة الحركة بنجاح.');
    }

    public function edit(PartnerTransaction $partnerTransaction): View
    {
        return view('partner_transactions.form', [
            'transaction' => $partnerTransaction,
            'partners' => Partner::orderBy('name')->get(),
            'accounts' => BankAccount::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, PartnerTransaction $partnerTransaction): RedirectResponse
    {
        $data = $this->validateData($request);

        if ($data['type'] === 'withdrawal') {
            $partner = Partner::findOrFail($data['partner_id']);
            // نحسب الرصيد باستبعاد قيمة الحركة الحالية القديمة لو كانت سحباً.
            $balance = $partner->currentBalance();
            if ($partnerTransaction->type === 'withdrawal') {
                $balance = bcadd($balance, (string) $partnerTransaction->amount, 2);
            }
            if (bccomp((string) $data['amount'], $balance, 2) > 0) {
                throw ValidationException::withMessages(['amount' => 'السحب يتجاوز رصيد الشريك']);
            }
        }

        DB::transaction(function () use ($partnerTransaction, $data) {
            $partnerTransaction->update($data);
            $this->syncBankTransaction($partnerTransaction);
        });

        return redirect()->route('partner_transactions.index')->with('success', 'تم تحديث الحركة.');
    }

    public function destroy(PartnerTransaction $partnerTransaction): RedirectResponse
    {
        DB::transaction(function () use ($partnerTransaction) {
            $this->removeLinkedBankTransaction($partnerTransaction);
            $partnerTransaction->delete();
        });

        return back()->with('success', 'تم حذف الحركة.');
    }

    /**
     * يضمن تطابق الحركة البنكية المرتبطة مع الحركة الحالية:
     * يحذف أي حركة سابقة، ثم يسجّل الحركة البنكية لو كانت مرتبطة بحساب.
     */
    private function syncBankTransaction(PartnerTransaction $transaction): void
    {
        $this->removeLinkedBankTransaction($transaction);

        if ($transaction->bank_account_id) {
            $account = BankAccount::findOrFail($transaction->bank_account_id);
            $this->ledger->post($account, [
                'type' => $transaction->type === 'deposit' ? 'deposit' : 'withdrawal',
                'amount' => $transaction->amount,
                'transaction_date' => $transaction->transaction_date,
                'description' => 'حركة شريك: '.optional($transaction->partner)->name,
                'reference_number' => $transaction->check_number,
                'related_type' => 'partner_withdrawal',
                'related_id' => $transaction->id,
                'created_by' => $transaction->created_by,
            ]);
        }
    }

    private function removeLinkedBankTransaction(PartnerTransaction $transaction): void
    {
        BankTransaction::where('related_type', 'partner_withdrawal')
            ->where('related_id', $transaction->id)
            ->get()
            ->each(fn (BankTransaction $t) => $this->ledger->deleteTransaction($t));
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'partner_id' => ['required', 'exists:partners,id'],
            'type' => ['required', 'in:'.implode(',', array_keys(PartnerTransaction::TYPES))],
            'amount' => ['required', 'numeric', 'gt:0'],
            'transaction_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', 'string', 'max:30'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'check_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
