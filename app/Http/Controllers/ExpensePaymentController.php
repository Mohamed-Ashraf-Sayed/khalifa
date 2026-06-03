<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Services\BankLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class ExpensePaymentController extends Controller implements HasMiddleware
{
    public function __construct(private readonly BankLedgerService $ledger) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:expenses.edit'),
        ];
    }

    public function store(Request $request, Expense $expense): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:'.implode(',', array_keys(Expense::PAYMENT_METHODS))],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($expense, $data, $request) {
            $payment = $expense->payments()->create([
                ...$data,
                'created_by' => $request->user()->id,
            ]);

            if ($payment->bank_account_id) {
                $account = BankAccount::findOrFail($payment->bank_account_id);
                $this->ledger->post($account, [
                    'type' => 'withdrawal',
                    'amount' => $payment->amount,
                    'transaction_date' => $payment->payment_date,
                    'description' => 'سداد مصروف: '.$expense->description,
                    'reference_number' => $payment->reference_number,
                    'related_type' => 'expense_payment',
                    'related_id' => $payment->id,
                    'created_by' => $payment->created_by,
                ]);
            }

            $expense->refreshPaymentStatus();
        });

        return back()->with('success', 'تمت إضافة القسط.');
    }

    public function destroy(ExpensePayment $expense_payment): RedirectResponse
    {
        DB::transaction(function () use ($expense_payment) {
            $expense = $expense_payment->expense;

            BankTransaction::where('related_type', 'expense_payment')
                ->where('related_id', $expense_payment->id)
                ->get()
                ->each(fn (BankTransaction $t) => $this->ledger->deleteTransaction($t));

            $expense_payment->delete();
            $expense->refreshPaymentStatus();
        });

        return back()->with('success', 'تم حذف القسط.');
    }
}
