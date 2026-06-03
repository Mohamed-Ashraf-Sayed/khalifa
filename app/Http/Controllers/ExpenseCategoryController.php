<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseCategoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:expenses.view', only: ['index']),
            new Middleware('can:expenses.create', only: ['create', 'store']),
            new Middleware('can:expenses.edit', only: ['edit', 'update']),
            new Middleware('can:expenses.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));

        $categories = ExpenseCategory::query()
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('expense_categories.index', compact('categories', 'search'));
    }

    public function create(): View
    {
        return view('expense_categories.form', ['category' => new ExpenseCategory(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        ExpenseCategory::create($this->validateData($request));

        return redirect()->route('expense_categories.index')->with('success', 'تمت إضافة الفئة.');
    }

    public function edit(ExpenseCategory $expense_category): View
    {
        return view('expense_categories.form', ['category' => $expense_category]);
    }

    public function update(Request $request, ExpenseCategory $expense_category): RedirectResponse
    {
        $expense_category->update($this->validateData($request, $expense_category));

        return redirect()->route('expense_categories.index')->with('success', 'تم تحديث الفئة.');
    }

    public function destroy(ExpenseCategory $expense_category): RedirectResponse
    {
        $expense_category->delete();

        return back()->with('success', 'تم حذف الفئة.');
    }

    private function validateData(Request $request, ?ExpenseCategory $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'alpha_dash', 'max:50', Rule::unique('expense_categories', 'code')->ignore($category?->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
