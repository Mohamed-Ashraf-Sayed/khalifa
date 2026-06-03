<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\BankTransfer;
use App\Services\BankLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BankTransferController extends Controller implements HasMiddleware
{
    public function __construct(private readonly BankLedgerService $ledger) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:bank_accounts.view', only: ['index']),
            new Middleware('can:bank_accounts.edit', only: ['create', 'store', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $fromAccountId = (string) $request->input('from_account_id', '');
        $toAccountId = (string) $request->input('to_account_id', '');
        $dateFrom = (string) $request->input('date_from', '');
        $dateTo = (string) $request->input('date_to', '');

        $transfers = BankTransfer::query()
            ->with(['fromAccount', 'toAccount'])
            ->when($fromAccountId !== '', fn ($q) => $q->where('from_account_id', $fromAccountId))
            ->when($toAccountId !== '', fn ($q) => $q->where('to_account_id', $toAccountId))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('transfer_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('transfer_date', '<=', $dateTo))
            ->latest('transfer_date')
            ->paginate(15)
            ->withQueryString();

        return view('bank_transfers.index', [
            'transfers' => $transfers,
            'accounts' => BankAccount::orderBy('name')->get(),
            'fromAccountId' => $fromAccountId,
            'toAccountId' => $toAccountId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function create(): View
    {
        return view('bank_transfers.form', ['accounts' => BankAccount::where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from_account_id' => ['required', 'exists:bank_accounts,id', 'different:to_account_id'],
            'to_account_id' => ['required', 'exists:bank_accounts,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'fees' => ['nullable', 'numeric', 'min:0'],
            'transfer_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
        $data['fees'] = $data['fees'] ?? 0;
        $data['created_by'] = $request->user()->id;

        DB::transaction(function () use ($data) {
            $transfer = BankTransfer::create($data);
            $from = BankAccount::findOrFail($data['from_account_id']);
            $to = BankAccount::findOrFail($data['to_account_id']);

            // سحب المبلغ فقط من الحساب المصدر، والرسوم تُسجَّل كحركة سحب منفصلة (مُبوّبة)
            $this->ledger->post($from, [
                'type' => 'withdrawal',
                'amount' => $data['amount'],
                'transaction_date' => $data['transfer_date'],
                'description' => 'تحويل إلى '.$to->name,
                'category' => 'transfer',
                'related_type' => 'bank_transfer',
                'related_id' => $transfer->id,
                'created_by' => $data['created_by'],
            ]);
            // الرسوم — حركة سحب منفصلة من نفس الحساب المصدر (الصافي = المبلغ + الرسوم لكن مفصّل)
            if (bccomp((string) $data['fees'], '0', 2) > 0) {
                $this->ledger->post($from, [
                    'type' => 'withdrawal',
                    'amount' => $data['fees'],
                    'transaction_date' => $data['transfer_date'],
                    'description' => 'رسوم تحويل',
                    'category' => 'fee',
                    'related_type' => 'bank_transfer',
                    'related_id' => $transfer->id,
                    'created_by' => $data['created_by'],
                ]);
            }
            $this->ledger->post($to, [
                'type' => 'deposit',
                'amount' => $data['amount'],
                'transaction_date' => $data['transfer_date'],
                'description' => 'تحويل من '.$from->name,
                'related_type' => 'bank_transfer',
                'related_id' => $transfer->id,
                'created_by' => $data['created_by'],
            ]);
        });

        return redirect()->route('bank_transfers.index')->with('success', 'تم تنفيذ التحويل وتحديث رصيدي الحسابين.');
    }

    public function destroy(BankTransfer $bank_transfer): RedirectResponse
    {
        DB::transaction(function () use ($bank_transfer) {
            BankTransaction::where('related_type', 'bank_transfer')
                ->where('related_id', $bank_transfer->id)
                ->get()
                ->each(fn (BankTransaction $t) => $this->ledger->deleteTransaction($t));
            $bank_transfer->delete();
        });

        return back()->with('success', 'تم التراجع عن التحويل وتحديث الأرصدة.');
    }
}
