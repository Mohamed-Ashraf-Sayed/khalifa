<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Employee;
use App\Models\EmployeeTransaction;
use App\Models\Setting;
use App\Services\ExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $stats = [
            'count' => Employee::count(),
            'active' => Employee::where('is_active', true)->count(),
            'salaries' => (string) Employee::where('is_active', true)->sum('salary'),
            'advances' => bcsub(
                (string) EmployeeTransaction::where('type', 'advance')->sum('amount'),
                (string) EmployeeTransaction::where('type', 'advance_return')->sum('amount'),
                2
            ),
        ];

        return view('employees.index', compact('employees', 'search', 'stats'));
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
    public function statement(Employee $employee, Request $request): View|\Illuminate\Http\Response|StreamedResponse
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

        $netGiven = $running;
        $format = (string) $request->input('format');

        if ($format === 'pdf' || $format === 'xlsx') {
            $company = Setting::get('company_name', 'القروانة');
            $entity = $employee->name.($employee->job_title ? ' — '.$employee->job_title : '');

            if ($format === 'xlsx') {
                $headers = ['التاريخ', 'النوع', 'البيان', 'صُرف (+)', 'خصم/ردّ (−)', 'صافي جارٍ'];
                $excelRows = [['', '', 'رصيد افتتاحي', '', '', '0.00']];
                foreach ($rows as $row) {
                    $excelRows[] = [
                        optional($row['txn']->transaction_date)->format('Y-m-d') ?: '—',
                        EmployeeTransaction::TYPES[$row['txn']->type] ?? $row['txn']->type,
                        $row['txn']->description ?: '—',
                        $row['isReducer'] ? '' : number_format((float) $row['txn']->amount, 2),
                        $row['isReducer'] ? number_format((float) $row['txn']->amount, 2) : '',
                        number_format((float) $row['running'], 2),
                    ];
                }
                $excelRows[] = ['', '', 'صافي النقد المُسلَّم', '', '', number_format((float) $netGiven, 2)];

                return app(ExportService::class)->excel(
                    $headers,
                    $excelRows,
                    'statement-employee-'.$employee->id,
                    $company.' — كشف حساب موظف: '.$entity
                );
            }

            $bodyRows = '<tr style="background:#f4f1ec"><td colspan="5" style="font-weight:600">رصيد افتتاحي</td><td style="text-align:left;font-weight:600">0.00</td></tr>';
            foreach ($rows as $row) {
                $bodyRows .= '<tr>'
                    .'<td>'.(optional($row['txn']->transaction_date)->format('Y-m-d') ?: '—').'</td>'
                    .'<td>'.e(EmployeeTransaction::TYPES[$row['txn']->type] ?? $row['txn']->type).'</td>'
                    .'<td>'.e($row['txn']->description ?: '—').'</td>'
                    .'<td style="text-align:left;color:#1a7d3c">'.($row['isReducer'] ? '' : number_format((float) $row['txn']->amount, 2)).'</td>'
                    .'<td style="text-align:left;color:#b02a37">'.($row['isReducer'] ? number_format((float) $row['txn']->amount, 2) : '').'</td>'
                    .'<td style="text-align:left;font-weight:600">'.number_format((float) $row['running'], 2).'</td>'
                    .'</tr>';
            }

            $html = $this->statementHtml(
                $company,
                'كشف حساب موظف',
                $entity,
                ['التاريخ', 'النوع', 'البيان', 'صُرف (+)', 'خصم/ردّ (−)', 'صافي جارٍ'],
                $bodyRows,
                '<tr style="background:#f4f1ec;font-weight:700"><td colspan="5">صافي النقد المُسلَّم</td>'
                    .'<td style="text-align:left">'.number_format((float) $netGiven, 2).'</td></tr>'
            );

            return app(ExportService::class)->pdf($html, 'statement-employee-'.$employee->id.'.pdf');
        }

        return view('employees.statement', [
            'employee' => $employee,
            'rows' => $rows,
            'advanceBalance' => $employee->advanceBalance(),
            'custodyBalance' => $employee->custodyBalance(),
            'netGiven' => $netGiven,
        ]);
    }

    /** يبني HTML عربي مكتفٍ ذاتياً (RTL) لكشف حساب لتصديره PDF. */
    private function statementHtml(string $company, string $title, string $entity, array $headers, string $bodyRows, string $footRow): string
    {
        $ths = '';
        foreach ($headers as $h) {
            $ths .= '<th style="border:1px solid #ccc;padding:6px;background:#8b7355;color:#fff;text-align:right">'.e($h).'</th>';
        }

        return '<html><head><meta charset="utf-8"><style>'
            .'body{font-family:dejavusans;direction:rtl;font-size:12px;color:#222}'
            .'h2,h4{margin:0;text-align:center}'
            .'.head{text-align:center;margin-bottom:14px}'
            .'.muted{color:#666;font-size:11px}'
            .'table{width:100%;border-collapse:collapse;margin-top:10px}'
            .'td{border:1px solid #ccc;padding:6px}'
            .'</style></head><body>'
            .'<div class="head"><h2>'.e($company).'</h2><h4>'.e($title).'</h4>'
            .'<div class="muted">'.e($entity).'</div>'
            .'<div class="muted">تاريخ الإصدار: '.now()->format('Y-m-d').'</div></div>'
            .'<table><thead><tr>'.$ths.'</tr></thead>'
            .'<tbody>'.$bodyRows.'</tbody>'
            .'<tfoot>'.$footRow.'</tfoot></table></body></html>';
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
