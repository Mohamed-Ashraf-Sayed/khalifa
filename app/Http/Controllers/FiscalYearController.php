<?php

namespace App\Http\Controllers;

use App\Models\FiscalPeriod;
use App\Models\FiscalYear;
use App\Services\FiscalYearService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class FiscalYearController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:accounting.view', only: ['index', 'show']),
            new Middleware('can:accounting.create', only: ['create', 'store']),
            new Middleware('can:accounting.edit', only: ['closeYear', 'reopenYear', 'closePeriod', 'openPeriod']),
            new Middleware('can:accounting.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $years = FiscalYear::with('periods')->orderByDesc('start_date')->get();

        return view('fiscal_years.index', compact('years'));
    }

    public function create(): View
    {
        return view('fiscal_years.create', ['suggested' => (int) now()->year]);
    }

    public function store(Request $request, FiscalYearService $service): RedirectResponse
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        if (FiscalYear::where('name', (string) $data['year'])->exists()) {
            return back()->with('error', 'السنة المالية موجودة بالفعل.');
        }

        $fy = $service->createYear((int) $data['year'], $request->user()->id);

        return redirect()->route('fiscal_years.show', $fy)->with('success', 'تم إنشاء السنة المالية وفتراتها.');
    }

    public function show(FiscalYear $fiscalYear): View
    {
        $fiscalYear->load(['periods', 'closingEntry', 'closer']);

        return view('fiscal_years.show', ['fy' => $fiscalYear]);
    }

    public function closeYear(Request $request, FiscalYear $fiscalYear, FiscalYearService $service): RedirectResponse
    {
        $service->closeYear($fiscalYear, $request->user()->id);

        return back()->with('success', 'تم إقفال السنة المالية وتوليد قيد الإقفال.');
    }

    public function reopenYear(FiscalYear $fiscalYear, FiscalYearService $service): RedirectResponse
    {
        $service->reopenYear($fiscalYear);

        return back()->with('success', 'تم إعادة فتح السنة المالية وحذف قيد الإقفال.');
    }

    public function closePeriod(Request $request, FiscalPeriod $fiscalPeriod, FiscalYearService $service): RedirectResponse
    {
        $service->setPeriodStatus($fiscalPeriod, 'closed', $request->user()->id);

        return back()->with('success', 'تم إقفال الفترة: '.$fiscalPeriod->name);
    }

    public function openPeriod(FiscalPeriod $fiscalPeriod, FiscalYearService $service): RedirectResponse
    {
        $service->setPeriodStatus($fiscalPeriod, 'open');

        return back()->with('success', 'تم فتح الفترة: '.$fiscalPeriod->name);
    }

    public function destroy(FiscalYear $fiscalYear): RedirectResponse
    {
        if ($fiscalYear->status === 'closed') {
            return back()->with('error', 'لا يمكن حذف سنة مقفلة. أعد فتحها أولاً.');
        }

        $fiscalYear->delete();

        return redirect()->route('fiscal_years.index')->with('success', 'تم حذف السنة المالية.');
    }
}
