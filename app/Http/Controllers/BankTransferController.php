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

    public function index(): View
    {
        $transfers = BankTransfer::with(['fromAccount', 'toAccount'])->latest('transfer_date')->paginate(15);

        return view('bank_transfers.index', compact('transfers'));
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

            // سحب (المبلغ + الرسوم) من الحساب المصدر، وإيداع المبلغ في الوجهة — ذرّياً
            $this->ledger->post($from, [
                'type' => 'withdrawal',
                'amount' => bcadd((string) $data['amount'], (string) $data['fees'], 2),
                'transaction_date' => $data['transfer_date'],
                'description' => 'تحويل إلى '.$to->name.($data['fees'] > 0 ? ' (شامل رسوم)' : ''),
                'related_type' => 'bank_transfer',
                'related_id' => $transfer->id,
                'created_by' => $data['created_by'],
            ]);
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
