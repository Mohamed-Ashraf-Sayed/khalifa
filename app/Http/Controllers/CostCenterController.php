<?php

namespace App\Http\Controllers;

use App\Models\CostCenter;
use App\Models\Expense;
use App\Models\Revenue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class CostCenterController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:reports.view', only: ['index', 'show', 'report']),
            new Middleware('can:settings.edit', only: ['create', 'store', 'edit', 'update', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));

        $costCenters = CostCenter::query()
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('cost_centers.index', compact('costCenters', 'search'));
    }

    public function create(): View
    {
        return view('cost_centers.form', ['costCenter' => new CostCenter(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        CostCenter::create($this->validateData($request));

        return redirect()->route('cost_centers.index')->with('success', 'تمت إضافة مركز التكلفة.');
    }

    public function edit(CostCenter $costCenter): View
    {
        return view('cost_centers.form', ['costCenter' => $costCenter]);
    }

    public function update(Request $request, CostCenter $costCenter): RedirectResponse
    {
        $costCenter->update($this->validateData($request, $costCenter));

        return redirect()->route('cost_centers.index')->with('success', 'تم تحديث مركز التكلفة.');
    }

    public function destroy(CostCenter $costCenter): RedirectResponse
    {
        $costCenter->delete();

        return back()->with('success', 'تم حذف مركز التكلفة.');
    }

    /**
     * تقرير المصروفات والإيرادات مجمّعة حسب مركز التكلفة مع الصافي لكل مركز.
     */
    public function report(): View
    {
        $centers = CostCenter::orderBy('name')->get();

        $rows = [];
        $totalExpenses = '0';
        $totalRevenues = '0';

        foreach ($centers as $center) {
            $expenses = (string) Expense::where('cost_center_id', $center->id)->sum('amount');
            $revenues = (string) Revenue::where('cost_center_id', $center->id)->sum('amount');
            $net = bcsub($revenues, $expenses, 2);

            $rows[] = [
                'center' => $center,
                'expenses' => $expenses,
                'revenues' => $revenues,
                'net' => $net,
            ];

            $totalExpenses = bcadd($totalExpenses, $expenses, 2);
            $totalRevenues = bcadd($totalRevenues, $revenues, 2);
        }

        // السطر «غير محدد» للسجلات بدون مركز تكلفة
        $unassignedExpenses = (string) Expense::whereNull('cost_center_id')->sum('amount');
        $unassignedRevenues = (string) Revenue::whereNull('cost_center_id')->sum('amount');

        if (bccomp($unassignedExpenses, '0', 2) > 0 || bccomp($unassignedRevenues, '0', 2) > 0) {
            $rows[] = [
                'center' => null,
                'expenses' => $unassignedExpenses,
                'revenues' => $unassignedRevenues,
                'net' => bcsub($unassignedRevenues, $unassignedExpenses, 2),
            ];

            $totalExpenses = bcadd($totalExpenses, $unassignedExpenses, 2);
            $totalRevenues = bcadd($totalRevenues, $unassignedRevenues, 2);
        }

        $totalNet = bcsub($totalRevenues, $totalExpenses, 2);

        return view('cost_centers.report', [
            'rows' => $rows,
            'totalExpenses' => $totalExpenses,
            'totalRevenues' => $totalRevenues,
            'totalNet' => $totalNet,
        ]);
    }

    private function validateData(Request $request, ?CostCenter $costCenter = null): array
    {
        $id = $costCenter?->id;

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', 'unique:cost_centers,code'.($id ? ','.$id : '')],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
