<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Services\BankLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class InvoicePaymentController extends Controller implements HasMiddleware
{
    public function __construct(private readonly BankLedgerService $ledger) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:invoices.edit'),
        ];
    }

    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:'.implode(',', array_keys(Invoice::PAYMENT_METHODS))],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($invoice, $data, $request) {
            $payment = $invoice->payments()->create([
                'payment_date' => $data['payment_date'],
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            if ($payment->bank_account_id) {
                $account = BankAccount::findOrFail($payment->bank_account_id);
                $this->ledger->post($account, [
                    'type' => 'deposit',
                    'amount' => $payment->amount,
                    'transaction_date' => $payment->payment_date,
                    'description' => 'تحصيل فاتورة '.$invoice->invoice_number,
                    'reference_number' => $payment->reference_number,
                    'related_type' => 'invoice_payment',
                    'related_id' => $payment->id,
                    'created_by' => $payment->created_by,
                ]);
            }

            $invoice->refreshPaymentStatus();
        });

        return back()->with('success', 'تمت إضافة الدفعة.');
    }

    public function destroy(InvoicePayment $invoice_payment): RedirectResponse
    {
        DB::transaction(function () use ($invoice_payment) {
            $invoice = $invoice_payment->invoice;

            BankTransaction::where('related_type', 'invoice_payment')
                ->where('related_id', $invoice_payment->id)
                ->get()
                ->each(fn (BankTransaction $t) => $this->ledger->deleteTransaction($t));

            $invoice_payment->delete();

            $invoice->refreshPaymentStatus();
        });

        return back()->with('success', 'تم حذف الدفعة.');
    }
}
