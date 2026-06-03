<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PartnerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:partners.view', only: ['index', 'show', 'statement']),
            new Middleware('can:partners.create', only: ['create', 'store']),
            new Middleware('can:partners.edit', only: ['edit', 'update']),
            new Middleware('can:partners.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));

        $partners = Partner::query()
            ->when($search !== '', fn ($q) => $q->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
            ))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('partners.index', compact('partners', 'search'));
    }

    public function show(Partner $partner): View
    {
        $partner->load(['creator', 'transactions' => fn ($q) => $q->latest('transaction_date')]);

        return view('partners.show', compact('partner'));
    }

    public function create(): View
    {
        return view('partners.form', [
            'partner' => new Partner(),
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        Partner::create($data);

        return redirect()->route('partners.index')->with('success', 'تمت إضافة الشريك بنجاح.');
    }

    public function edit(Partner $partner): View
    {
        return view('partners.form', [
            'partner' => $partner,
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Partner $partner): RedirectResponse
    {
        $partner->update($this->validateData($request));

        return redirect()->route('partners.index')->with('success', 'تم تحديث بيانات الشريك.');
    }

    public function destroy(Partner $partner): RedirectResponse
    {
        $partner->delete();

        return back()->with('success', 'تم حذف الشريك.');
    }

    /** كشف حساب الشريك: حركات مرتّبة زمنياً مع رصيد جارٍ محسوب بـ bcmath. */
    public function statement(Partner $partner): View
    {
        $transactions = $partner->transactions()
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $running = '0';
        $rows = $transactions->map(function ($txn) use (&$running) {
            $running = $txn->type === 'deposit'
                ? bcadd($running, (string) $txn->amount, 2)
                : bcsub($running, (string) $txn->amount, 2);

            return ['txn' => $txn, 'running' => $running];
        });

        $deposits = $partner->deposits()->latest('deposit_date')->get();

        return view('partners.statement', compact('partner', 'rows', 'deposits'));
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'national_id' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'join_date' => ['required', 'date'],
            'status' => ['required', Rule::in(array_keys(Partner::STATUSES))],
            'project_id' => ['nullable', 'exists:projects,id'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
