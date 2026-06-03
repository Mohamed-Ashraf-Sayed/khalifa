<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeTransaction;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class EmployeeTransactionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:employees.view', only: ['index']),
            new Middleware('can:employees.create', only: ['create', 'store']),
            new Middleware('can:employees.edit', only: ['edit', 'update']),
            new Middleware('can:employees.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $type = (string) $request->input('type', '');

        $transactions = EmployeeTransaction::query()
            ->with(['employee', 'project'])
            ->when($search !== '', fn ($q) => $q->where('description', 'like', "%{$search}%"))
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->latest('transaction_date')
            ->paginate(15)
            ->withQueryString();

        return view('employee_transactions.index', compact('transactions', 'search', 'type'));
    }

    public function create(): View
    {
        return view('employee_transactions.form', [
            'transaction' => new EmployeeTransaction(['transaction_date' => now()->toDateString()]),
            'employees' => Employee::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        EmployeeTransaction::create($data);

        return redirect()->route('employee_transactions.index')->with('success', 'تمت إضافة المعاملة بنجاح.');
    }

    public function edit(EmployeeTransaction $employeeTransaction): View
    {
        return view('employee_transactions.form', [
            'transaction' => $employeeTransaction,
            'employees' => Employee::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, EmployeeTransaction $employeeTransaction): RedirectResponse
    {
        $employeeTransaction->update($this->validateData($request));

        return redirect()->route('employee_transactions.index')->with('success', 'تم تحديث المعاملة.');
    }

    public function destroy(EmployeeTransaction $employeeTransaction): RedirectResponse
    {
        $employeeTransaction->delete();

        return back()->with('success', 'تم حذف المعاملة.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'type' => ['required', 'in:'.implode(',', array_keys(EmployeeTransaction::TYPES))],
            'amount' => ['required', 'numeric', 'gt:0'],
            'transaction_date' => ['required', 'date'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
