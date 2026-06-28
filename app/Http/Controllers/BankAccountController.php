<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Services\BankLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BankAccountController extends Controller implements HasMiddleware
{
    public function __construct(private readonly BankLedgerService $ledger) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:bank_accounts.view', only: ['index', 'show']),
            new Middleware('can:bank_accounts.create', only: ['create', 'store']),
            new Middleware('can:bank_accounts.edit', only: ['edit', 'update']),
            new Middleware('can:bank_accounts.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');

        $query = BankAccount::query()
            ->banksOnly() // الخزائن النقدية تُعرض في شاشة «الخزنة» المستقلة
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('bank_name', 'like', "%{$search}%")
                        ->orWhere('account_number', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false));

        $accounts = $query->latest()->paginate(15)->withQueryString();
        $total = BankAccount::banksOnly()->where('is_active', true)->sum('current_balance');

        return view('bank_accounts.index', [
            'accounts' => $accounts,
            'total' => $total,
            'filters' => compact('search', 'status'),
        ]);
    }

    /** شاشة الخزنة النقدية: قائمة الخزائن بأرصدتها (تفتح صفحة الحركات لكل خزنة). */
    public function treasury(): View
    {
        $treasuries = BankAccount::query()->cash()->orderBy('name')->get();
        $total = BankAccount::query()->cash()->where('is_active', true)->sum('current_balance');

        return view('bank_accounts.treasury', compact('treasuries', 'total'));
    }

    public function create(Request $request): View
    {
        $type = $request->query('type') === 'cash' ? 'cash' : null;

        return view('bank_accounts.form', ['account' => new BankAccount([
            'currency' => 'EGP', 'is_active' => true, 'account_type' => $type,
        ])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        $data['current_balance'] = $data['opening_balance']; // يبدأ من الافتتاحي

        BankAccount::create($data);

        return redirect()->route('bank_accounts.index')->with('success', 'تمت إضافة الحساب البنكي.');
    }

    public function show(Request $request, BankAccount $bank_account)
    {
        // الكشف الكامل بالرصيد الجاري الصحيح (مرتّب بالتاريخ ثم id) — يُبنى دائماً من المصدر
        $rows = $this->ledger->statement($bank_account);

        // فلترة للعرض فقط مع الحفاظ على الرصيد الجاري المحسوب لكل صف
        $from = trim((string) $request->input('from', ''));
        $to = trim((string) $request->input('to', ''));
        $type = (string) $request->input('type', '');
        $reconciled = (string) $request->input('reconciled', '');

        $filtered = $rows->filter(function (array $row) use ($from, $to, $type, $reconciled) {
            $t = $row['txn'];
            if ($from !== '' && $t->transaction_date->format('Y-m-d') < $from) {
                return false;
            }
            if ($to !== '' && $t->transaction_date->format('Y-m-d') > $to) {
                return false;
            }
            if ($type !== '' && $t->type !== $type) {
                return false;
            }
            if ($reconciled === 'reconciled' && ! $t->is_reconciled) {
                return false;
            }
            if ($reconciled === 'unreconciled' && $t->is_reconciled) {
                return false;
            }

            return true;
        })->values();

        // إجماليات بطاقات الملخص محسوبة بـ bcmath من الكشف المُفلتر
        $totalDeposits = '0';
        $totalWithdrawals = '0';
        foreach ($filtered as $row) {
            if ($row['txn']->type === 'deposit') {
                $totalDeposits = bcadd($totalDeposits, (string) $row['txn']->amount, 2);
            } else {
                $totalWithdrawals = bcadd($totalWithdrawals, (string) $row['txn']->amount, 2);
            }
        }
        $net = bcsub($totalDeposits, $totalWithdrawals, 2);

        if ($request->input('export') === 'csv') {
            return $this->exportCsv($bank_account, $filtered);
        }

        return view('bank_accounts.show', [
            'account' => $bank_account,
            'rows' => $filtered,
            'filters' => compact('from', 'to', 'type', 'reconciled'),
            'totalDeposits' => $totalDeposits,
            'totalWithdrawals' => $totalWithdrawals,
            'net' => $net,
        ]);
    }

    /**
     * تصدير الكشف المُفلتر إلى CSV (مع الحفاظ على الرصيد الجاري لكل صف).
     */
    private function exportCsv(BankAccount $bank_account, $rows): StreamedResponse
    {
        $filename = 'statement-'.$bank_account->id.'-'.date('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            // BOM لدعم العربية في Excel
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['التاريخ', 'البيان', 'التصنيف', 'المستفيد', 'رقم الشيك', 'المرجع', 'تاريخ القيمة', 'إيداع', 'سحب', 'الرصيد الجاري', 'المطابقة']);
            foreach ($rows as $row) {
                $t = $row['txn'];
                fputcsv($out, [
                    $t->transaction_date->format('Y-m-d'),
                    $t->description,
                    BankTransaction::CATEGORIES[$t->category] ?? ($t->category ?? ''),
                    $t->beneficiary ?? '',
                    $t->check_number ?? '',
                    $t->reference_number ?? '',
                    $t->value_date ? $t->value_date->format('Y-m-d') : '',
                    $t->type === 'deposit' ? number_format($t->amount, 2, '.', '') : '',
                    $t->type === 'withdrawal' ? number_format($t->amount, 2, '.', '') : '',
                    $row['running'],
                    $t->is_reconciled ? 'نعم' : 'لا',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function edit(BankAccount $bank_account): View
    {
        return view('bank_accounts.form', ['account' => $bank_account]);
    }

    public function update(Request $request, BankAccount $bank_account): RedirectResponse
    {
        $bank_account->update($this->validateData($request));
        // تغيّر الرصيد الافتتاحي → أعِد اشتقاق الرصيد الحالي من المصدر
        $this->ledger->refreshBalance($bank_account);

        return redirect()->route('bank_accounts.index')->with('success', 'تم تحديث الحساب البنكي.');
    }

    public function destroy(BankAccount $bank_account): RedirectResponse
    {
        if ($bank_account->transactions()->exists()) {
            return back()->with('error', 'لا يمكن حذف حساب له حركات. احذف الحركات أولاً أو عطّل الحساب.');
        }

        $bank_account->delete();

        return back()->with('success', 'تم حذف الحساب البنكي.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bank_name' => ['nullable', 'required_unless:account_type,cash', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'iban' => ['nullable', 'string', 'max:50'],
            'branch' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'max:10'],
            'opening_balance' => ['required', 'numeric'],
            'account_type' => ['nullable', 'in:'.implode(',', array_keys(BankAccount::ACCOUNT_TYPES))],
            'swift_code' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        // الخزنة النقدية لا بنك لها — قيمة افتراضية للعمود غير الفارغ (NOT NULL)
        if (($data['account_type'] ?? null) === 'cash' && empty($data['bank_name'])) {
            $data['bank_name'] = '—';
        }

        return $data;
    }
}
