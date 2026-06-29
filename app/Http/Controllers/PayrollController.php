<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Employee;
use App\Models\EmployeeTransaction;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Services\BankLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PayrollController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:employees.view', only: ['index', 'show', 'print', 'payslip']),
            new Middleware('can:employees.create', only: ['create', 'store']),
            new Middleware('can:employees.edit', only: ['edit', 'update', 'approve', 'pay', 'updateItem']),
            new Middleware('can:employees.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $runs = PayrollRun::query()
            ->with(['creator'])
            ->withCount('items')
            ->latest()
            ->paginate(15);

        $paidNet = '0';
        foreach (PayrollRun::where('status', 'paid')->get() as $run) {
            $paidNet = bcadd($paidNet, (string) $run->total_net, 2);
        }

        $stats = [
            'count' => PayrollRun::count(),
            'drafts' => PayrollRun::where('status', 'draft')->count(),
            'paid' => $paidNet,
        ];

        return view('payroll.index', compact('runs', 'stats'));
    }

    public function create(): View
    {
        return view('payroll.create', [
            'run' => new PayrollRun([
                'period_year' => now()->year,
                'period_month' => now()->month,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'period_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'notes' => ['nullable', 'string'],
        ]);

        if (PayrollRun::where('period_year', $data['period_year'])->where('period_month', $data['period_month'])->exists()) {
            return back()->withInput()->with('error', 'يوجد مسيّر رواتب لهذا الشهر بالفعل — افتحه من القائمة بدل إنشاء واحد جديد.');
        }

        $run = PayrollRun::create([
            'run_number' => sprintf('PR-%d-%02d', $data['period_year'], $data['period_month']),
            'period_year' => $data['period_year'],
            'period_month' => $data['period_month'],
            'status' => 'draft',
            'total_net' => 0,
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        foreach (Employee::where('is_active', true)->get() as $employee) {
            $advanceBalance = $employee->advanceBalance();
            $salary = (string) $employee->salary;
            $advanceDeduction = bccomp($advanceBalance, $salary, 2) < 0 ? $advanceBalance : $salary;

            $item = new PayrollItem([
                'payroll_run_id' => $run->id,
                'employee_id' => $employee->id,
                'basic_salary' => $salary,
                'allowances' => 0,
                'bonus' => 0,
                'deductions' => 0,
                'advance_deduction' => $advanceDeduction,
            ]);
            $item->net_salary = $item->computeNet();
            $item->save();
        }

        $run->load('items');
        $run->recomputeTotal();

        return redirect()->route('payroll.show', $run)->with('success', 'تم إنشاء مسيّر الرواتب وتوليد بنود الموظفين.');
    }

    public function show(PayrollRun $payrollRun): View
    {
        $payrollRun->load(['items.employee', 'bankAccount', 'creator', 'approver']);

        return view('payroll.show', [
            'run' => $payrollRun,
            'bankAccounts' => BankAccount::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function print(PayrollRun $payrollRun): View
    {
        $payrollRun->load(['items.employee', 'bankAccount', 'creator', 'approver']);

        return view('payroll.print', ['run' => $payrollRun]);
    }

    public function payslip(PayrollRun $payrollRun, PayrollItem $item): View
    {
        abort_unless($item->payroll_run_id === $payrollRun->id, 404);

        $payrollRun->load(['bankAccount', 'approver']);
        $item->load('employee');

        return view('payroll.payslip', ['run' => $payrollRun, 'item' => $item]);
    }

    public function updateItem(Request $request, PayrollRun $payrollRun, PayrollItem $item): RedirectResponse
    {
        if ($payrollRun->status !== 'draft') {
            return back()->with('error', 'لا يمكن تعديل بنود مسيّر معتمد أو مدفوع.');
        }

        $data = $request->validate([
            'allowances' => ['required', 'numeric', 'min:0'],
            'bonus' => ['required', 'numeric', 'min:0'],
            'deductions' => ['required', 'numeric', 'min:0'],
            'advance_deduction' => ['required', 'numeric', 'min:0'],
        ]);

        $item->update($data);
        $item->net_salary = $item->computeNet();
        $item->save();

        $payrollRun->load('items');
        $payrollRun->recomputeTotal();

        return back()->with('success', 'تم تحديث بند الراتب.');
    }

    public function approve(PayrollRun $payrollRun): RedirectResponse
    {
        if ($payrollRun->status !== 'draft') {
            return back()->with('error', 'لا يمكن اعتماد المسيّر في حالته الحالية.');
        }

        $payrollRun->update([
            'status' => 'approved',
            'approved_by' => request()->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'تم اعتماد مسيّر الرواتب.');
    }

    public function pay(Request $request, PayrollRun $payrollRun): RedirectResponse
    {
        if ($payrollRun->status !== 'approved') {
            return back()->with('error', 'لا يمكن صرف المسيّر إلا بعد اعتماده.');
        }

        $data = $request->validate([
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
        ]);

        $payrollRun->load('items.employee');

        if ($payrollRun->items->isEmpty()) {
            return back()->with('error', 'لا توجد بنود في المسيّر للصرف.');
        }

        DB::transaction(function () use ($payrollRun, $data, $request) {
            $payrollRun->bank_account_id = $data['bank_account_id'];
            $payrollRun->save();

            $account = BankAccount::findOrFail($data['bank_account_id']);
            $ledger = app(BankLedgerService::class);
            $userId = $request->user()->id;

            foreach ($payrollRun->items as $item) {
                if ($item->paid) {
                    continue;
                }

                $ledger->post($account, [
                    'type' => 'withdrawal',
                    'amount' => $item->net_salary,
                    'transaction_date' => now()->toDateString(),
                    'description' => 'راتب '.$item->employee->name.' - '.$payrollRun->run_number,
                    'related_type' => 'payroll_item',
                    'related_id' => $item->id,
                    'created_by' => $userId,
                ]);

                EmployeeTransaction::create([
                    'employee_id' => $item->employee_id,
                    'type' => 'salary',
                    'amount' => $item->net_salary,
                    'transaction_date' => now()->toDateString(),
                    'description' => $payrollRun->run_number,
                    'created_by' => $userId,
                ]);

                if (bccomp((string) $item->advance_deduction, '0', 2) > 0) {
                    EmployeeTransaction::create([
                        'employee_id' => $item->employee_id,
                        'type' => 'advance_return',
                        'amount' => $item->advance_deduction,
                        'transaction_date' => now()->toDateString(),
                        'description' => $payrollRun->run_number,
                        'created_by' => $userId,
                    ]);
                }

                $item->paid = true;
                $item->save();
            }

            $payrollRun->status = 'paid';
            $payrollRun->paid_at = now();
            $payrollRun->save();
        });

        return back()->with('success', 'تم صرف الرواتب وتسجيل الحركات البنكية.');
    }

    public function destroy(PayrollRun $payrollRun): RedirectResponse
    {
        if ($payrollRun->status !== 'draft') {
            return back()->with('error', 'لا يمكن حذف مسيّر معتمد أو مدفوع.');
        }

        $payrollRun->delete();

        return redirect()->route('payroll.index')->with('success', 'تم حذف مسيّر الرواتب.');
    }
}
