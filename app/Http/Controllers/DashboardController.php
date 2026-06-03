<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Client;
use App\Models\Contractor;
use App\Models\Employee;
use App\Models\Expense;
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
            'projects' => Project::count(),
            'projects_active' => Project::where('status', 'in_progress')->count(),
            'clients' => Client::count(),
            'contractors' => Contractor::count(),
            'suppliers' => Supplier::count(),
            'employees' => Employee::where('is_active', true)->count(),
            'revenue' => $totalRevenue,
            'expense' => $totalExpense,
            'net' => $totalRevenue - $totalExpense,
            'bank_balance' => (float) BankAccount::where('is_active', true)->sum('current_balance'),
        ];

        $recentProjects = Project::with('client')->latest()->take(5)->get();

        return view('dashboard', compact('stats', 'recentProjects'));
    }
}
