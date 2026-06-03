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
use App\Http\Controllers\ContractorExtractItemController;
use App\Http\Controllers\ContractorPaymentController;
use App\Http\Controllers\CustomPaymentMethodController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeTransactionController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpensePaymentController;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceItemController;
use App\Http\Controllers\InvoicePaymentController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PartnerDepositController;
use App\Http\Controllers\PartnerTransactionController;
use App\Http\Controllers\ProjectContractController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectCostController;
use App\Http\Controllers\ProjectEmployeeController;
use App\Http\Controllers\ProjectFileController;
use App\Http\Controllers\ProjectMaterialConsumptionController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseOrderItemController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RevenueCollectionController;
use App\Http\Controllers\RevenueController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierPaymentController;
use App\Http\Controllers\SupplierTransactionController;
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

    Route::resource('clients', ClientController::class);
    Route::resource('projects', ProjectController::class);
    Route::post('projects/{project}/employees', [ProjectEmployeeController::class, 'store'])->name('projectEmployees.store');
    Route::delete('project-employees/{project_employee}', [ProjectEmployeeController::class, 'destroy'])->name('projectEmployees.destroy');
    Route::post('projects/{project}/material-consumptions', [ProjectMaterialConsumptionController::class, 'store'])->name('projectMaterialConsumptions.store');
    Route::delete('project-material-consumptions/{project_material_consumption}', [ProjectMaterialConsumptionController::class, 'destroy'])->name('projectMaterialConsumptions.destroy');

    // تكاليف المشاريع / بنود الأعمال (report قبل resource عشان ميتلقفش بالـ wildcard)
    Route::get('project-costs/report', [ProjectCostController::class, 'report'])->name('project_costs.report');
    Route::resource('project-costs', ProjectCostController::class)->names('project_costs');
    Route::resource('contractors', ContractorController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('employees', EmployeeController::class);
    Route::resource('partners', PartnerController::class);

    // الحسابات البنكية + حركاتها (عبر BankLedgerService)
    Route::resource('bank-accounts', BankAccountController::class)
        ->names('bank_accounts')
        ->parameters(['bank-accounts' => 'bank_account']);
    Route::post('bank-accounts/{bank_account}/transactions', [BankTransactionController::class, 'store'])
        ->name('bank_transactions.store');
    Route::delete('bank-transactions/{bank_transaction}', [BankTransactionController::class, 'destroy'])
        ->name('bank_transactions.destroy');
    Route::post('bank-transactions/{bank_transaction}/reconcile', [BankTransactionController::class, 'reconcile'])->name('bank_transactions.reconcile');
    Route::resource('payment-methods', CustomPaymentMethodController::class)->names('payment_methods')->except(['show']);

    Route::resource('expenses', ExpenseController::class);
    Route::post('expenses/{expense}/payments', [ExpensePaymentController::class, 'store'])->name('expense_payments.store');
    Route::delete('expense-payments/{expense_payment}', [ExpensePaymentController::class, 'destroy'])->name('expense_payments.destroy');

    Route::resource('revenues', RevenueController::class);
    Route::post('revenues/{revenue}/collections', [RevenueCollectionController::class, 'store'])->name('revenue_collections.store');
    Route::delete('revenue-collections/{revenue_collection}', [RevenueCollectionController::class, 'destroy'])->name('revenue_collections.destroy');

    Route::resource('users', UserController::class);

    // موجة 1 — كتالوج
    Route::get('materials/report', [MaterialController::class, 'report'])->name('materials.report');
    Route::resource('materials', MaterialController::class);
    Route::resource('assets', AssetController::class);
    Route::resource('contracts', ProjectContractController::class);
    Route::resource('taxes', TaxController::class);

    // موجة 2 — موردون
    Route::resource('purchase-orders', PurchaseOrderController::class)->names('purchase_orders');
    Route::post('purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase_orders.approve');
    Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase_orders.receive');
    Route::post('purchase-orders/{purchase_order}/items', [PurchaseOrderItemController::class, 'store'])->name('purchase_order_items.store');
    Route::delete('purchase-order-items/{purchase_order_item}', [PurchaseOrderItemController::class, 'destroy'])->name('purchase_order_items.destroy');
    Route::resource('supplier-payments', SupplierPaymentController::class)->names('supplier_payments');
    Route::resource('supplier-transactions', SupplierTransactionController::class)->names('supplier_transactions');

    // موجة 3 — مقاولون
    Route::resource('contractor-extracts', ContractorExtractController::class)->names('contractor_extracts');
    Route::post('contractor-extracts/{contractorExtract}/approve', [ContractorExtractController::class, 'approve'])->name('contractor_extracts.approve');
    Route::post('contractor-extracts/{contractor_extract}/items', [ContractorExtractItemController::class, 'store'])->name('contractor_extract_items.store');
    Route::delete('contractor-extract-items/{contractor_extract_item}', [ContractorExtractItemController::class, 'destroy'])->name('contractor_extract_items.destroy');
    Route::resource('contractor-payments', ContractorPaymentController::class)->names('contractor_payments');

    // موجة 4+5 — معاملات الموظفين والشركاء
    Route::resource('employee-transactions', EmployeeTransactionController::class)->names('employee_transactions');
    Route::resource('partner-transactions', PartnerTransactionController::class)->names('partner_transactions');

    // إيداعات الشركاء + جدول الأرباح + التسوية + كشف الحساب
    Route::resource('partner-deposits', PartnerDepositController::class)->names('partner_deposits')->except(['edit', 'update']);
    Route::post('partner-deposits/{deposit}/schedules/{schedule}/pay', [PartnerDepositController::class, 'payProfit'])->name('partner_deposits.pay_profit');
    Route::post('partner-deposits/{deposit}/settle', [PartnerDepositController::class, 'settle'])->name('partner_deposits.settle');
    Route::get('partners/{partner}/statement', [PartnerController::class, 'statement'])->name('partners.statement');

    // موجة 6 — الفواتير (مع صفحة عرض البنود)
    Route::resource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/items', [InvoiceItemController::class, 'store'])->name('invoice_items.store');
    Route::delete('invoice-items/{invoice_item}', [InvoiceItemController::class, 'destroy'])->name('invoice_items.destroy');
    Route::post('invoices/{invoice}/payments', [InvoicePaymentController::class, 'store'])->name('invoice_payments.store');
    Route::delete('invoice-payments/{invoice_payment}', [InvoicePaymentController::class, 'destroy'])->name('invoice_payments.destroy');

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
    Route::get('reports/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.balance_sheet');
    Route::get('reports/income-statement', [ReportController::class, 'incomeStatement'])->name('reports.income_statement');
    Route::get('reports/taxes', [ReportController::class, 'taxReport'])->name('reports.taxes');

    // كشوف حساب الكيانات + تقرير المقاولين
    Route::get('suppliers/{supplier}/statement', [SupplierController::class, 'statement'])->name('suppliers.statement');
    Route::get('contractors/{contractor}/statement', [ContractorController::class, 'statement'])->name('contractors.statement');
    Route::get('employees/{employee}/statement', [EmployeeController::class, 'statement'])->name('employees.statement');
    Route::get('contractor-report', [ContractorController::class, 'report'])->name('contractors.report');
});
