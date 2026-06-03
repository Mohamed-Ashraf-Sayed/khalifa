<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:employees.view', only: ['index', 'show', 'statement']),
            new Middleware('can:employees.create', only: ['create', 'store']),
            new Middleware('can:employees.edit', only: ['edit', 'update']),
            new Middleware('can:employees.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));

        $employees = Employee::query()
            ->when($search !== '', fn ($q) => $q->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%")
            ))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('employees.index', compact('employees', 'search'));
    }

    public function show(Employee $employee): View
    {
        $employee->load(['transactions' => fn ($q) => $q->latest()]);

        return view('employees.show', compact('employee'));
    }

    public function create(): View
    {
        return view('employees.form', $this->formData(new Employee()));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        Employee::create($data);

        return redirect()->route('employees.index')->with('success', 'تمت إضافة الموظف بنجاح.');
    }

    public function edit(Employee $employee): View
    {
        return view('employees.form', $this->formData($employee));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $employee->update($this->validateData($request, $employee));

        return redirect()->route('employees.index')->with('success', 'تم تحديث بيانات الموظف.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return back()->with('success', 'تم حذف الموظف.');
    }

    /**
     * كشف حساب الموظف: حركات مرتّبة زمنياً مع رصيد جارٍ (صافي النقد المُسلَّم) بـ bcmath.
     * موجب (+): راتب/سلفة/عهدة/مكافأة (مبالغ صُرفت أو في يد الموظف).
     * سالب (−): سداد سلفة/رد عهدة/صرف من العهدة/خصم.
     */
    public function statement(Employee $employee): View
    {
        // الأنواع التي تخصم من صافي النقد المُسلَّم
        $reducers = ['advance_return', 'custody_return', 'custody_expense', 'deduction'];

        $transactions = $employee->transactions()
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $running = '0';
        $rows = $transactions->map(function ($txn) use (&$running, $reducers) {
            $isReducer = in_array($txn->type, $reducers, true);
            $signed = $isReducer
                ? bcsub('0', (string) $txn->amount, 2)
                : (string) $txn->amount;
            $running = bcadd($running, $signed, 2);

            return [
                'txn' => $txn,
                'isReducer' => $isReducer,
                'running' => $running,
            ];
        });

        return view('employees.statement', [
            'employee' => $employee,
            'rows' => $rows,
            'advanceBalance' => $employee->advanceBalance(),
            'custodyBalance' => $employee->custodyBalance(),
            'netGiven' => $running,
        ]);
    }

    private function formData(Employee $employee): array
    {
        return [
            'employee' => $employee,
            'accounts' => BankAccount::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    private function validateData(Request $request, ?Employee $employee = null): array
    {
        return $request->validate([
            'employee_code' => [
                'required', 'string', 'max:20',
                Rule::unique('employees', 'employee_code')->ignore($employee),
            ],
            'name' => ['required', 'string', 'max:255'],
            'national_id' => ['nullable', 'string', 'max:20'],
            'job_title' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:100'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'hire_date' => ['required', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
