<?php

namespace App\Http\Controllers;

use App\Models\Contractor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContractorController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:contractors.view', only: ['index', 'show']),
            new Middleware('can:contractors.create', only: ['create', 'store']),
            new Middleware('can:contractors.edit', only: ['edit', 'update']),
            new Middleware('can:contractors.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));

        $contractors = Contractor::query()
            ->when($search !== '', fn ($q) => $q->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('contractor_code', 'like', "%{$search}%")
            ))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('contractors.index', compact('contractors', 'search'));
    }

    public function show(Contractor $contractor): View
    {
        $contractor->load([
            'creator',
            'extracts' => fn ($q) => $q->latest(),
            'payments' => fn ($q) => $q->latest(),
        ]);

        return view('contractors.show', compact('contractor'));
    }

    public function create(): View
    {
        return view('contractors.form', ['contractor' => new Contractor()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        Contractor::create($data);

        return redirect()->route('contractors.index')->with('success', 'تمت إضافة المقاول بنجاح.');
    }

    public function edit(Contractor $contractor): View
    {
        return view('contractors.form', compact('contractor'));
    }

    public function update(Request $request, Contractor $contractor): RedirectResponse
    {
        $contractor->update($this->validateData($request, $contractor));

        return redirect()->route('contractors.index')->with('success', 'تم تحديث بيانات المقاول.');
    }

    public function destroy(Contractor $contractor): RedirectResponse
    {
        $contractor->delete();

        return back()->with('success', 'تم حذف المقاول.');
    }

    private function validateData(Request $request, ?Contractor $contractor = null): array
    {
        return $request->validate([
            'contractor_code' => [
                'required', 'string', 'max:50',
                Rule::unique('contractors', 'contractor_code')->ignore($contractor),
            ],
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'phone2' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
