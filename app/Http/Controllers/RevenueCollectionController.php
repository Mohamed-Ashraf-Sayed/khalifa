<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Revenue;
use App\Models\RevenueCollection;
use App\Services\BankLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class RevenueCollectionController extends Controller implements HasMiddleware
{
    public function __construct(private readonly BankLedgerService $ledger) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:revenues.edit'),
        ];
    }

    public function store(Request $request, Revenue $revenue): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'collection_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:'.implode(',', array_keys(Revenue::PAYMENT_METHODS))],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'check_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($revenue, $data, $request) {
            $collection = $revenue->collections()->create([
                'collection_date' => $data['collection_date'],
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'check_number' => $data['check_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            if ($collection->bank_account_id) {
                $account = BankAccount::findOrFail($collection->bank_account_id);
                $this->ledger->post($account, [
                    'type' => 'deposit',
                    'amount' => $collection->amount,
                    'transaction_date' => $collection->collection_date,
                    'description' => 'تحصيل إيراد: '.$revenue->description,
                    'reference_number' => $collection->check_number,
                    'related_type' => 'revenue_collection',
                    'related_id' => $collection->id,
                    'created_by' => $collection->created_by,
                ]);
            }

            $revenue->refreshCollectionStatus();
        });

        return back()->with('success', 'تم تسجيل التحصيل.');
    }

    public function destroy(RevenueCollection $revenue_collection): RedirectResponse
    {
        DB::transaction(function () use ($revenue_collection) {
            $revenue = $revenue_collection->revenue;

            BankTransaction::where('related_type', 'revenue_collection')
                ->where('related_id', $revenue_collection->id)
                ->get()
                ->each(fn (BankTransaction $t) => $this->ledger->deleteTransaction($t));

            $revenue_collection->delete();
            $revenue->refreshCollectionStatus();
        });

        return back()->with('success', 'تم حذف التحصيل.');
    }
}
