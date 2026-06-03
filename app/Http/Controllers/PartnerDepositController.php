<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Partner;
use App\Models\PartnerDeposit;
use App\Models\PartnerProfitSchedule;
use App\Models\PartnerTransaction;
use App\Services\BankLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PartnerDepositController extends Controller implements HasMiddleware
{
    public function __construct(private readonly BankLedgerService $ledger) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:partners.view', only: ['index', 'show']),
            new Middleware('can:partners.create', only: ['create', 'store', 'payProfit', 'settle']),
            new Middleware('can:partners.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $partnerId = (string) $request->input('partner_id', '');
        $status = (string) $request->input('status', '');

        $deposits = PartnerDeposit::query()
            ->with('partner')
            ->when($partnerId !== '', fn ($q) => $q->where('partner_id', $partnerId))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest('deposit_date')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'active_capital' => (string) PartnerDeposit::where('status', 'active')->sum('amount'),
            'scheduled_profit' => (string) PartnerProfitSchedule::sum('amount'),
            'paid_profit' => (string) PartnerProfitSchedule::where('is_paid', true)->sum('amount'),
            'count' => PartnerDeposit::count(),
        ];

        return view('partner_deposits.index', [
            'deposits' => $deposits,
            'stats' => $stats,
            'partners' => Partner::orderBy('name')->get(),
            'partnerId' => $partnerId,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('partner_deposits.form', [
            'deposit' => new PartnerDeposit(['deposit_date' => now()->toDateString(), 'payout_frequency' => 'monthly', 'status' => 'active']),
            'partners' => Partner::orderBy('name')->get(),
            'accounts' => BankAccount::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        $data['status'] = 'active';

        DB::transaction(function () use ($data) {
            $deposit = PartnerDeposit::create($data);

            // كل إيداع رأس مال يسجّل حركة شريك من نوع deposit (دخول نقدي للشركة)
            PartnerTransaction::create([
                'partner_id' => $deposit->partner_id,
                'type' => 'deposit',
                'amount' => $deposit->amount,
                'transaction_date' => $deposit->deposit_date,
                'partner_deposit_id' => $deposit->id,
                'bank_account_id' => $deposit->bank_account_id,
                'description' => 'إيداع رأس مال',
                'created_by' => $deposit->created_by,
            ]);

            if ($deposit->bank_account_id) {
                $account = BankAccount::findOrFail($deposit->bank_account_id);
                $this->ledger->post($account, [
                    'type' => 'deposit',
                    'amount' => $deposit->amount,
                    'transaction_date' => $deposit->deposit_date,
                    'description' => 'إيداع رأس مال شريك: '.optional($deposit->partner)->name,
                    'related_type' => 'partner_deposit',
                    'related_id' => $deposit->id,
                    'created_by' => $deposit->created_by,
                ]);
            }

            $deposit->generateSchedule();
        });

        return redirect()->route('partner_deposits.index')->with('success', 'تم تسجيل الإيداع وتوليد جدول الأرباح.');
    }

    public function show(PartnerDeposit $partner_deposit): View
    {
        $partner_deposit->load(['partner', 'bankAccount', 'schedules' => fn ($q) => $q->orderBy('due_date')]);

        return view('partner_deposits.show', [
            'deposit' => $partner_deposit,
            'accounts' => BankAccount::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function destroy(PartnerDeposit $partner_deposit): RedirectResponse
    {
        if ($partner_deposit->schedules()->where('is_paid', true)->exists()) {
            return back()->with('error', 'لا يمكن حذف إيداع تم صرف أرباح منه.');
        }

        DB::transaction(function () use ($partner_deposit) {
            // حذف الحركة البنكية المرتبطة بالإيداع
            BankTransaction::where('related_type', 'partner_deposit')
                ->where('related_id', $partner_deposit->id)
                ->get()
                ->each(fn (BankTransaction $t) => $this->ledger->deleteTransaction($t));

            // حذف حركة الشريك (إيداع رأس المال) المرتبطة
            PartnerTransaction::where('partner_deposit_id', $partner_deposit->id)
                ->where('type', 'deposit')
                ->delete();

            // الجداول تُحذف cascade مع الإيداع
            $partner_deposit->delete();
        });

        return back()->with('success', 'تم حذف الإيداع.');
    }

    public function payProfit(Request $request, PartnerDeposit $deposit, PartnerProfitSchedule $schedule): RedirectResponse
    {
        if ($schedule->is_paid) {
            return back()->with('error', 'تم صرف هذه الدفعة بالفعل.');
        }

        $validated = $request->validate([
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
        ]);

        DB::transaction(function () use ($deposit, $schedule, $validated) {
            $transaction = PartnerTransaction::create([
                'partner_id' => $deposit->partner_id,
                'type' => 'profit',
                'amount' => $schedule->amount,
                'transaction_date' => now()->toDateString(),
                'partner_deposit_id' => $deposit->id,
                'profit_period' => optional($schedule->due_date)->format('Y-m-d'),
                'bank_account_id' => $validated['bank_account_id'] ?? null,
                'description' => 'صرف أرباح شريك',
                'created_by' => request()->user()->id,
            ]);

            if (! empty($validated['bank_account_id'])) {
                $account = BankAccount::findOrFail($validated['bank_account_id']);
                $this->ledger->post($account, [
                    'type' => 'withdrawal',
                    'amount' => $schedule->amount,
                    'transaction_date' => now()->toDateString(),
                    'description' => 'صرف أرباح شريك: '.optional($deposit->partner)->name,
                    'related_type' => 'partner_profit',
                    'related_id' => $transaction->id,
                    'created_by' => request()->user()->id,
                ]);
            }

            $schedule->update([
                'is_paid' => true,
                'paid_date' => now()->toDateString(),
                'partner_transaction_id' => $transaction->id,
            ]);
        });

        return back()->with('success', 'تم صرف الأرباح.');
    }

    public function settle(Request $request, PartnerDeposit $deposit): RedirectResponse
    {
        if ($deposit->status === 'settled') {
            return back()->with('error', 'هذا الإيداع مُسوّى بالفعل.');
        }

        $validated = $request->validate([
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
        ]);

        DB::transaction(function () use ($deposit, $validated) {
            $transaction = PartnerTransaction::create([
                'partner_id' => $deposit->partner_id,
                'type' => 'settlement',
                'amount' => $deposit->amount,
                'transaction_date' => now()->toDateString(),
                'partner_deposit_id' => $deposit->id,
                'bank_account_id' => $validated['bank_account_id'] ?? null,
                'description' => 'تسوية وإرجاع رأس مال',
                'created_by' => request()->user()->id,
            ]);

            if (! empty($validated['bank_account_id'])) {
                $account = BankAccount::findOrFail($validated['bank_account_id']);
                $this->ledger->post($account, [
                    'type' => 'withdrawal',
                    'amount' => $deposit->amount,
                    'transaction_date' => now()->toDateString(),
                    'description' => 'تسوية رأس مال شريك: '.optional($deposit->partner)->name,
                    'related_type' => 'partner_settlement',
                    'related_id' => $transaction->id,
                    'created_by' => request()->user()->id,
                ]);
            }

            $deposit->update(['status' => 'settled']);
        });

        return back()->with('success', 'تمت التسوية وإرجاع رأس المال.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'partner_id' => ['required', 'exists:partners,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'deposit_date' => ['required', 'date'],
            'profit_rate' => ['required', 'numeric', 'min:0'],
            'duration_months' => ['required', 'integer', 'min:1'],
            'payout_frequency' => ['required', Rule::in(array_keys(PartnerDeposit::PAYOUT_FREQUENCIES))],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
