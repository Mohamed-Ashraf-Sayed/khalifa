<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Project;
use App\Models\Revenue;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:reports.view')];
    }

    public function index(Request $request): View
    {
        $from = $request->date('from');
        $to = $request->date('to');

        $revenueQ = Revenue::query()
            ->when($from, fn ($q) => $q->whereDate('revenue_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('revenue_date', '<=', $to));

        $expenseQ = Expense::query()
            ->when($from, fn ($q) => $q->whereDate('expense_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('expense_date', '<=', $to));

        $totalRevenue = (float) (clone $revenueQ)->sum('amount');
        $totalExpense = (float) (clone $expenseQ)->sum('amount');

        // المصروفات حسب الفئة
        $byCategory = (clone $expenseQ)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        // ملخّص لكل مشروع: إيرادات − مصروفات
        $projects = Project::query()
            ->withSum(['revenues as rev' => function ($q) use ($from, $to) {
                $q->when($from, fn ($qq) => $qq->whereDate('revenue_date', '>=', $from))
                    ->when($to, fn ($qq) => $qq->whereDate('revenue_date', '<=', $to));
            }], 'amount')
            ->withSum(['expenses as exp' => function ($q) use ($from, $to) {
                $q->when($from, fn ($qq) => $qq->whereDate('expense_date', '>=', $from))
                    ->when($to, fn ($qq) => $qq->whereDate('expense_date', '<=', $to));
            }], 'amount')
            ->orderBy('name')
            ->get();

        return view('reports.index', [
            'from' => $from?->toDateString(),
            'to' => $to?->toDateString(),
            'totalRevenue' => $totalRevenue,
            'totalExpense' => $totalExpense,
            'net' => $totalRevenue - $totalExpense,
            'byCategory' => $byCategory,
            'projects' => $projects,
        ]);
    }
}
