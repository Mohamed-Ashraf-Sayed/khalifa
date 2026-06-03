<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Cheque;
use App\Services\BankLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ChequeController extends Controller implements HasMiddleware
{
    public function __construct(private readonly BankLedgerService $ledger) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:bank_accounts.view', only: ['index', 'show']),
            new Middleware('can:bank_accounts.create', only: ['create', 'store']),
            new Middleware('can:bank_accounts.edit', only: ['edit', 'update', 'markDeposited', 'markCleared', 'markBounced']),
            new Middleware('can:bank_accounts.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $direction = (string) $request->input('direction', '');
        $status = (string) $request->input('status', '');
        $dateFrom = (string) $request->input('date_from', '');
        $dateTo = (string) $request->input('date_to', '');

        $filtered = fn ($q) => $q
            ->when($direction !== '', fn ($q) => $q->where('direction', $direction))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('issue_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('issue_date', '<=', $dateTo));

        $cheques = Cheque::query()
            ->with(['bankAccount'])
            ->tap($filtered)
            ->latest('issue_date')
            ->paginate(15)
            ->withQueryString();

        // إحصائيات لكل حالة: العدد والإجمالي (bcmath غير ضروري هنا — مجرد عرض).
        $statsRows = Cheque::query()
            ->tap($filtered)
            ->selectRaw('status, COUNT(*) as cnt, SUM(amount) as total')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $stats = [];
        foreach (array_keys(Cheque::STATUSES) as $key) {
            $stats[$key] = [
                'count' => (int) ($statsRows[$key]->cnt ?? 0),
                'sum' => (string) ($statsRows[$key]->total ?? '0'),
            ];
        }

        return view('cheques.index', [
            'cheques' => $cheques,
            'stats' => $stats,
            'direction' => $direction,
            'status' => $status,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function show(Cheque $cheque): View
    {
        $cheque->load(['bankAccount', 'creator']);

        return view('cheques.show', ['cheque' => $cheque]);
    }

    public function create(): View
    {
        return view('cheques.form', $this->formData(new Cheque([
            'direction' => 'incoming',
            'status' => 'pending',
            'issue_date' => now()->toDateString(),
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;

        Cheque::create($data);

        return redirect()->route('cheques.index')->with('success', 'تمت إضافة الشيك.');
    }

    public function edit(Cheque $cheque): View
    {
        return view('cheques.form', $this->formData($cheque));
    }

    public function update(Request $request, Cheque $cheque): RedirectResponse
    {
        $cheque->update($this->validateData($request));

        return redirect()->route('cheques.index')->with('success', 'تم تحديث الشيك.');
    }

    public function destroy(Cheque $cheque): RedirectResponse
    {
        DB::transaction(function () use ($cheque) {
            $this->removeLinkedBankTransaction($cheque);
            $cheque->delete();
        });

        return back()->with('success', 'تم حذف الشيك.');
    }

    /**
     * pending → deposited
     */
    public function markDeposited(Cheque $cheque): RedirectResponse
    {
        if ($cheque->status !== 'pending') {
            return back()->with('error', 'لا يمكن إيداع شيك في هذه الحالة.');
        }

        DB::transaction(function () use ($cheque) {
            $cheque->update(['status' => 'deposited']);
        });

        return back()->with('success', 'تم تعليم الشيك كمودع.');
    }

    /**
     * pending/deposited → cleared. لو فيه حساب بنكي يسجَّل القيد:
     * إيداع للوارد / سحب للصادر، مرتبط بالشيك.
     */
    public function markCleared(Cheque $cheque): RedirectResponse
    {
        if (! in_array($cheque->status, ['pending', 'deposited'], true)) {
            return back()->with('error', 'لا يمكن تحصيل شيك في هذه الحالة.');
        }

        DB::transaction(function () use ($cheque) {
            if ($cheque->bank_account_id) {
                $account = BankAccount::findOrFail($cheque->bank_account_id);
                $this->ledger->post($account, [
                    'type' => $cheque->direction === 'incoming' ? 'deposit' : 'withdrawal',
                    'amount' => (string) $cheque->amount,
                    'transaction_date' => $cheque->due_date ?? $cheque->issue_date,
                    'description' => 'شيك '.$cheque->cheque_number,
                    'reference_number' => $cheque->cheque_number,
                    'related_type' => 'cheque',
                    'related_id' => $cheque->id,
                    'created_by' => $cheque->created_by,
                ]);
            }

            $cheque->update(['status' => 'cleared']);
        });

        return back()->with('success', 'تم تحصيل الشيك.');
    }

    /**
     * pending/deposited/cleared → bounced. لو كان محصّلاً يُحذف القيد البنكي المرتبط.
     */
    public function markBounced(Cheque $cheque): RedirectResponse
    {
        if (! in_array($cheque->status, ['pending', 'deposited', 'cleared'], true)) {
            return back()->with('error', 'لا يمكن ارتداد شيك في هذه الحالة.');
        }

        DB::transaction(function () use ($cheque) {
            if ($cheque->status === 'cleared') {
                $this->removeLinkedBankTransaction($cheque);
            }

            $cheque->update(['status' => 'bounced']);
        });

        return back()->with('success', 'تم تعليم الشيك كمرتد.');
    }

    private function removeLinkedBankTransaction(Cheque $cheque): void
    {
        BankTransaction::where('related_type', 'cheque')
            ->where('related_id', $cheque->id)
            ->get()
            ->each(fn (BankTransaction $t) => $this->ledger->deleteTransaction($t));
    }

    private function formData(Cheque $cheque): array
    {
        return [
            'cheque' => $cheque,
            'accounts' => BankAccount::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'cheque_number' => ['required', 'string', 'max:50'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'direction' => ['required', 'in:'.implode(',', array_keys(Cheque::DIRECTIONS))],
            'party_name' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', 'in:'.implode(',', array_keys(Cheque::STATUSES))],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
