<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\BankTransactionController;
use App\Http\Controllers\BankTransferController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContractorController;
use App\Http\Controllers\ContractorExtractController;
use App\Http\Controllers\ContractorPaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeTransactionController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceItemController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PartnerTransactionController;
use App\Http\Controllers\ProjectContractController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectFileController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RevenueController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierPaymentController;
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

    // موجة 2 — موردون
    Route::resource('purchase-orders', PurchaseOrderController::class)->names('purchase_orders')->except('show');
    Route::resource('supplier-payments', SupplierPaymentController::class)->names('supplier_payments')->except('show');

    // موجة 3 — مقاولون
    Route::resource('contractor-extracts', ContractorExtractController::class)->names('contractor_extracts')->except('show');
    Route::resource('contractor-payments', ContractorPaymentController::class)->names('contractor_payments')->except('show');

    // موجة 4+5 — معاملات الموظفين والشركاء
    Route::resource('employee-transactions', EmployeeTransactionController::class)->names('employee_transactions')->except('show');
    Route::resource('partner-transactions', PartnerTransactionController::class)->names('partner_transactions')->except('show');

    // موجة 6 — الفواتير (مع صفحة عرض البنود)
    Route::resource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/items', [InvoiceItemController::class, 'store'])->name('invoice_items.store');
    Route::delete('invoice-items/{invoice_item}', [InvoiceItemController::class, 'destroy'])->name('invoice_items.destroy');

    // موجة 7 — تحويلات بنكية + حركات مخزون
    Route::resource('bank-transfers', BankTransferController::class)->names('bank_transfers')->only(['index', 'create', 'store', 'destroy']);
    Route::resource('inventory-movements', InventoryMovementController::class)->names('inventory_movements')->only(['index', 'create', 'store', 'destroy']);

    // موجة 8 — ملفات المشاريع (رفع آمن) + الإعدادات + سجل النشاطات
    Route::get('project-files', [ProjectFileController::class, 'index'])->name('project_files.index');
    Route::post('project-files', [ProjectFileController::class, 'store'])->name('project_files.store');
    Route::get('project-files/{project_file}/download', [ProjectFileController::class, 'download'])->name('project_files.download');
    Route::delete('project-files/{project_file}', [ProjectFileController::class, 'destroy'])->name('project_files.destroy');

    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity_logs.index');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
});
