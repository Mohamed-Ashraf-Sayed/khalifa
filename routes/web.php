<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\BankTransactionController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContractorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\ProjectContractController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RevenueController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

// ضيوف فقط
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

// مستخدمون مسجّلون فقط
Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('clients', ClientController::class)->except('show');
    Route::resource('projects', ProjectController::class)->except('show');
    Route::resource('contractors', ContractorController::class)->except('show');
    Route::resource('suppliers', SupplierController::class)->except('show');
    Route::resource('employees', EmployeeController::class)->except('show');
    Route::resource('partners', PartnerController::class)->except('show');

    // الحسابات البنكية + حركاتها (عبر BankLedgerService)
    Route::resource('bank-accounts', BankAccountController::class)
        ->names('bank_accounts')
        ->parameters(['bank-accounts' => 'bank_account']);
    Route::post('bank-accounts/{bank_account}/transactions', [BankTransactionController::class, 'store'])
        ->name('bank_transactions.store');
    Route::delete('bank-transactions/{bank_transaction}', [BankTransactionController::class, 'destroy'])
        ->name('bank_transactions.destroy');

    Route::resource('expenses', ExpenseController::class)->except('show');
    Route::resource('revenues', RevenueController::class)->except('show');

    Route::resource('users', UserController::class)->except('show');

    // موجة 1 — كتالوج
    Route::resource('materials', MaterialController::class)->except('show');
    Route::resource('assets', AssetController::class)->except('show');
    Route::resource('contracts', ProjectContractController::class)->except('show');
    Route::resource('taxes', TaxController::class)->except('show');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
});
