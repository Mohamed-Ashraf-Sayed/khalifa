<?php

namespace App\Http\Controllers;

use App\Models\CustomPaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class CustomPaymentMethodController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        // إعادة استخدام صلاحيات الحسابات البنكية لطرق الدفع المخصّصة
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

        $methods = CustomPaymentMethod::query()
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('custom_payment_methods.index', compact('methods', 'search'));
    }

    public function create(): View
    {
        return view('custom_payment_methods.form', ['method' => new CustomPaymentMethod(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        CustomPaymentMethod::create($this->validateData($request));

        return redirect()->route('payment_methods.index')->with('success', 'تمت إضافة طريقة الدفع.');
    }

    public function edit(CustomPaymentMethod $payment_method): View
    {
        return view('custom_payment_methods.form', ['method' => $payment_method]);
    }

    public function update(Request $request, CustomPaymentMethod $payment_method): RedirectResponse
    {
        $payment_method->update($this->validateData($request));

        return redirect()->route('payment_methods.index')->with('success', 'تم تحديث طريقة الدفع.');
    }

    public function destroy(CustomPaymentMethod $payment_method): RedirectResponse
    {
        $payment_method->delete();

        return back()->with('success', 'تم حذف طريقة الدفع.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
