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
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class RevenueController extends Controller implements HasMiddleware
{
    public function __construct(private readonly BankLedgerService $ledger) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:revenues.view', only: ['index', 'show']),
            new Middleware('can:revenues.create', only: ['create', 'store']),
            new Middleware('can:revenues.edit', only: ['edit', 'update', 'confirm']),
            new Middleware('can:revenues.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View|StreamedResponse
    {
        $projectId = (string) $request->input('project_id', '');
        $paymentStatus = (string) $request->input('payment_status', '');
        $from = trim((string) $request->input('from', ''));
        $to = trim((string) $request->input('to', ''));

        $base = Revenue::query()
            ->when($projectId !== '', fn ($q) => $q->where('project_id', $projectId))
            ->when($paymentStatus !== '', fn ($q) => $q->where('payment_status', $paymentStatus))
            ->when($from !== '', fn ($q) => $q->whereDate('revenue_date', '>=', $from))
            ->when($to !== '', fn ($q) => $q->whereDate('revenue_date', '<=', $to));

        if ($request->input('export') === 'csv') {
            return $this->exportCsv((clone $base));
        }

        $revenues = (clone $base)
            ->with(['project', 'bankAccount'])
            ->latest('revenue_date')
            ->paginate(15)
            ->withQueryString();

        $totalAmount = (string) (clone $base)->sum('amount');
        $totalCollected = (string) (clone $base)->sum('paid_amount');
        $stats = [
            'total' => $totalAmount,
            'collected' => $totalCollected,
            'remaining' => bcsub($totalAmount, $totalCollected, 2),
            'count' => (clone $base)->count(),
        ];

        return view('revenues.index', [
            'revenues' => $revenues,
            'projects' => Project::orderBy('name')->get(),
            'projectId' => $projectId,
            'paymentStatus' => $paymentStatus,
            'from' => $from,
            'to' => $to,
            'stats' => $stats,
        ]);
    }

    private function exportCsv($query): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="revenues.csv"',
        ];

        $revenues = $query->with('project')->latest('revenue_date')->get();

        return response()->stream(function () use ($revenues) {
            $out = fopen('php://output', 'w');
            // BOM لدعم العربية في Excel
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['description', 'amount', 'paid_amount', 'payment_status', 'revenue_date', 'project']);
            foreach ($revenues as $r) {
                fputcsv($out, [
                    $r->description,
                    $r->amount,
                    $r->paid_amount,
                    Revenue::PAYMENT_STATUSES[$r->payment_status] ?? $r->payment_status,
                    $r->revenue_date?->format('Y-m-d'),
                    $r->project?->name,
                ]);
            }
            fclose($out);
        }, 200, $headers);
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

    /** يبدّل حالة تأكيد الإيراد (مؤكد / قيد التأكيد). */
    public function confirm(Revenue $revenue): RedirectResponse
    {
        $revenue->update(['is_confirmed' => ! $revenue->is_confirmed]);

        return back()->with('success', $revenue->is_confirmed ? 'تم تأكيد الإيراد.' : 'تم إلغاء تأكيد الإيراد.');
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
        $allowed = array_merge(
            array_keys(Revenue::PAYMENT_METHODS),
            \App\Models\CustomPaymentMethod::where('is_active', true)->pluck('code')->all(),
        );

        return $request->validate([
            'project_id' => ['nullable', 'exists:projects,id'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'revenue_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:'.implode(',', $allowed)],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'due_date' => ['nullable', 'date'],
            'check_number' => ['nullable', 'string', 'max:50'],
            'deferred_check' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
