<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\LetterOfGuarantee;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LetterOfGuaranteeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:guarantees.view', only: ['index', 'show', 'print']),
            new Middleware('can:guarantees.create', only: ['create', 'store']),
            new Middleware('can:guarantees.edit', only: ['edit', 'update', 'release']),
            new Middleware('can:guarantees.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');

        $guarantees = LetterOfGuarantee::query()
            ->with('project')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('lg_number', 'like', "%{$search}%")
                    ->orWhere('beneficiary', 'like', "%{$search}%");
            }))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $today = Carbon::today();
        $active = LetterOfGuarantee::where('status', 'active')->get();

        $stats = [
            'count' => LetterOfGuarantee::count(),
            'active_amount' => (float) $active->reduce(fn ($c, $g) => bcadd($c, (string) $g->amount, 2), '0'),
            'expiring' => $active->filter(fn ($g) => $g->isExpiringSoon())->count(),
            'expired' => LetterOfGuarantee::where('status', 'active')
                ->whereDate('expiry_date', '<', $today)->count(),
        ];

        return view('letters_of_guarantee.index', compact('guarantees', 'search', 'status', 'stats'));
    }

    public function show(LetterOfGuarantee $letterOfGuarantee): View
    {
        $letterOfGuarantee->load('project', 'bankAccount', 'creator');

        return view('letters_of_guarantee.show', ['guarantee' => $letterOfGuarantee]);
    }

    public function print(LetterOfGuarantee $letterOfGuarantee): View
    {
        $letterOfGuarantee->load('project', 'bankAccount', 'creator');

        return view('letters_of_guarantee.print', ['guarantee' => $letterOfGuarantee]);
    }

    public function create(): View
    {
        return view('letters_of_guarantee.form', [
            'guarantee' => new LetterOfGuarantee(['status' => 'active', 'type' => 'bid', 'issue_date' => now()->toDateString()]),
            'lgNumber' => $this->nextNumber(),
            'projects' => Project::orderBy('name')->get(),
            'bankAccounts' => BankAccount::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['lg_number'] = $this->nextNumber();
        $data['created_by'] = $request->user()->id;
        LetterOfGuarantee::create($data);

        return redirect()->route('guarantees.index')->with('success', 'تمت إضافة خطاب الضمان بنجاح.');
    }

    public function edit(LetterOfGuarantee $letterOfGuarantee): View
    {
        return view('letters_of_guarantee.form', [
            'guarantee' => $letterOfGuarantee,
            'lgNumber' => $letterOfGuarantee->lg_number,
            'projects' => Project::orderBy('name')->get(),
            'bankAccounts' => BankAccount::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, LetterOfGuarantee $letterOfGuarantee): RedirectResponse
    {
        $letterOfGuarantee->update($this->validateData($request));

        return redirect()->route('guarantees.index')->with('success', 'تم تحديث خطاب الضمان.');
    }

    /** الإفراج عن خطاب الضمان (إنهاء الالتزام). */
    public function release(LetterOfGuarantee $letterOfGuarantee): RedirectResponse
    {
        $letterOfGuarantee->update(['status' => 'released']);

        return back()->with('success', 'تم الإفراج عن خطاب الضمان.');
    }

    public function destroy(LetterOfGuarantee $letterOfGuarantee): RedirectResponse
    {
        $letterOfGuarantee->delete();

        return back()->with('success', 'تم حذف خطاب الضمان.');
    }

    private function nextNumber(): string
    {
        $year = now()->format('Y');
        $count = LetterOfGuarantee::whereYear('created_at', $year)->count() + 1;

        return sprintf('LG-%s-%04d', $year, $count);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:'.implode(',', array_keys(LetterOfGuarantee::TYPES))],
            'beneficiary' => ['required', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_id' => ['nullable', Rule::exists('bank_accounts', 'id')],
            'amount' => ['required', 'numeric', 'min:0'],
            'issue_date' => ['required', 'date'],
            'expiry_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'status' => ['required', 'in:'.implode(',', array_keys(LetterOfGuarantee::STATUSES))],
            'project_id' => ['nullable', Rule::exists('projects', 'id')],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
