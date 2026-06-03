<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Client;
use App\Models\Contractor;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Revenue;
use App\Models\Supplier;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $totalRevenue = (float) Revenue::sum('amount');
        $totalExpense = (float) Expense::sum('amount');

        $stats = [
            'revenue' => $totalRevenue,
            'expense' => $totalExpense,
            'net' => $totalRevenue - $totalExpense,
            'bank_balance' => (float) BankAccount::where('is_active', true)->sum('current_balance'),
            'projects' => Project::count(),
            'projects_active' => Project::where('status', 'in_progress')->count(),
            'clients' => Client::count(),
            'contractors' => Contractor::count(),
            'suppliers' => Supplier::count(),
            'employees' => Employee::where('is_active', true)->count(),
            'invoices_unpaid' => Invoice::whereIn('status', ['sent', 'partial', 'overdue'])->count(),
        ];

        // اتجاه آخر 6 شهور: إيرادات مقابل مصروفات
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->format('Y-m'));
        $monthLabels = $months->map(fn ($m) => \Carbon\Carbon::createFromFormat('Y-m', $m)->translatedFormat('M Y'));

        $revByMonth = $this->sumByMonth(Revenue::query(), 'revenue_date', $months);
        $expByMonth = $this->sumByMonth(Expense::query(), 'expense_date', $months);

        // المصروفات حسب الفئة
        $byCategory = Expense::query()
            ->get()
            ->groupBy('category')
            ->map(fn ($g) => (float) $g->sum('amount'));

        // المشاريع حسب الحالة
        $byStatus = Project::query()->get()->groupBy('status')->map(fn ($g) => $g->count());

        // أرصدة البنوك
        $banks = BankAccount::where('is_active', true)->get(['name', 'current_balance']);

        // أعلى المقاولين رصيداً مستحقاً
        $topContractors = Contractor::all()
            ->map(fn ($c) => ['name' => $c->name, 'balance' => (float) $c->balanceDue()])
            ->sortByDesc('balance')->take(5)->values();

        $recentProjects = Project::with('client')->latest()->take(5)->get();

        return view('dashboard', [
            'stats' => $stats,
            'chartMonths' => $monthLabels->values(),
            'chartRevenue' => $months->map(fn ($m) => $revByMonth[$m] ?? 0)->values(),
            'chartExpense' => $months->map(fn ($m) => $expByMonth[$m] ?? 0)->values(),
            'catLabels' => $byCategory->keys()->map(fn ($k) => Expense::CATEGORIES[$k] ?? $k)->values(),
            'catValues' => $byCategory->values(),
            'statusLabels' => $byStatus->keys()->map(fn ($k) => Project::STATUSES[$k] ?? $k)->values(),
            'statusValues' => $byStatus->values(),
            'banks' => $banks,
            'topContractors' => $topContractors,
            'recentProjects' => $recentProjects,
        ]);
    }

    /** تجميع مبالغ حسب الشهر (مستقل عن نوع قاعدة البيانات). */
    private function sumByMonth($query, string $dateCol, $months): array
    {
        return $query->whereNotNull($dateCol)
            ->get([$dateCol, 'amount'])
            ->groupBy(fn ($row) => $row->{$dateCol}->format('Y-m'))
            ->map(fn ($g) => (float) $g->sum('amount'))
            ->all();
    }
}
