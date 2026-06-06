<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\BankAccount;
use App\Models\Client;
use App\Models\Contractor;
use App\Models\ContractorExtract;
use App\Models\ContractorPayment;
use App\Models\Employee;
use App\Models\EmployeeTransaction;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\Partner;
use App\Models\PartnerDeposit;
use App\Models\PartnerTransaction;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Revenue;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\SupplierTransaction;
use App\Models\User;
use App\Services\BankLedgerService;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    private int $by;
    private BankLedgerService $ledger;

    public function run(): void
    {
        $this->by = User::min('id') ?? 1;
        $this->ledger = app(BankLedgerService::class);

        // عملاء
        $clients = collect([
            ['name' => 'الهيئة الهندسية للقوات المسلحة', 'company_name' => 'الجيش', 'phone' => '0227000000', 'city' => 'القاهرة', 'tax_number' => '200-100-300'],
            ['name' => 'شركة المستقبل للتطوير العقاري', 'company_name' => 'المستقبل', 'phone' => '0235000000', 'city' => 'الجيزة', 'tax_number' => '311-222-400'],
            ['name' => 'مجموعة طلعت مصطفى', 'company_name' => 'TMG', 'phone' => '0224000000', 'city' => 'القاهرة الجديدة', 'tax_number' => '410-330-500'],
        ])->map(fn ($c) => Client::create($c + ['created_by' => $this->by]));

        // مشاريع
        $projects = collect([
            ['name' => 'كمبوند الفردوس — 6 عمارات سكنية', 'client_id' => $clients[0]->id, 'project_type' => 'residential', 'location' => 'التجمع الخامس', 'contract_value' => 282902352, 'status' => 'in_progress', 'start' => 14],
            ['name' => 'برج المستقبل التجاري', 'client_id' => $clients[1]->id, 'project_type' => 'commercial', 'location' => 'الشيخ زايد', 'contract_value' => 145000000, 'status' => 'in_progress', 'start' => 8],
            ['name' => 'طريق محور 26 يوليو', 'client_id' => $clients[2]->id, 'project_type' => 'road', 'location' => '6 أكتوبر', 'contract_value' => 96500000, 'status' => 'completed', 'start' => 20],
            ['name' => 'مدارس التجمع الحكومية', 'client_id' => $clients[0]->id, 'project_type' => 'building', 'location' => 'القاهرة الجديدة', 'contract_value' => 54000000, 'status' => 'pending', 'start' => 1],
        ])->map(fn ($p) => Project::create([
            'name' => $p['name'], 'client_id' => $p['client_id'], 'project_type' => $p['project_type'],
            'location' => $p['location'], 'contract_value' => $p['contract_value'], 'status' => $p['status'],
            'start_date' => now()->subMonths($p['start'])->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(), 'created_by' => $this->by,
        ]));
        $mainProject = $projects[0];

        // بنوك
        $bankMain = BankAccount::create(['name' => 'الحساب الجاري الرئيسي', 'bank_name' => 'البنك الأهلي المصري', 'account_number' => '1234567890', 'currency' => 'EGP', 'opening_balance' => 0, 'current_balance' => 0, 'is_active' => true, 'created_by' => $this->by]);
        $bankProjects = BankAccount::create(['name' => 'حساب المشاريع', 'bank_name' => 'بنك مصر', 'account_number' => '9988776655', 'currency' => 'EGP', 'opening_balance' => 5000000, 'current_balance' => 5000000, 'is_active' => true, 'created_by' => $this->by]);

        // مقاولون / موردون / موظفون / شركاء
        $contractors = collect([
            ['contractor_code' => 'C-101', 'name' => 'شركة الاتحاد للمقاولات', 'specialty' => 'خرسانة وأعمال إنشائية', 'phone' => '01001234567'],
            ['contractor_code' => 'C-102', 'name' => 'مؤسسة النور للتشطيبات', 'specialty' => 'تشطيبات', 'phone' => '01112223334'],
            ['contractor_code' => 'C-103', 'name' => 'الشركة الوطنية للأسفلت', 'specialty' => 'أسفلت وطرق', 'phone' => '01223334445'],
            ['contractor_code' => 'C-104', 'name' => 'م/ محمد رضا — كهرباء', 'specialty' => 'كهرباء', 'phone' => '01098765432'],
        ])->map(fn ($c) => Contractor::create($c + ['is_active' => true, 'created_by' => $this->by]));

        $suppliers = collect([
            ['name' => 'محطة العربية المتحدة للخرسانة', 'company_name' => 'العربية المتحدة', 'type' => 'external', 'phone' => '0233445566'],
            ['name' => 'الشركة الوطنية للأسمنت', 'company_name' => 'NCC', 'type' => 'external', 'phone' => '0244556677'],
            ['name' => 'عز للحديد والصلب', 'company_name' => 'عز', 'type' => 'external', 'phone' => '0255667788'],
            ['name' => 'مخزن الموقع الداخلي', 'company_name' => 'داخلي', 'type' => 'internal', 'phone' => '01000000000'],
        ])->map(fn ($s) => Supplier::create($s + ['is_active' => true, 'created_by' => $this->by]));

        $jobs = [['مدير مشروع', 'الإدارة', 18000], ['مهندس تنفيذ', 'الهندسة', 12000], ['محاسب', 'الحسابات', 10000], ['مساح', 'الهندسة', 9000], ['أمين مخزن', 'المخازن', 8000], ['مشرف عمالة', 'التنفيذ', 9500], ['سائق', 'الخدمات', 6000], ['عامل فني', 'التنفيذ', 7000]];
        $employees = collect();
        foreach ($jobs as $i => [$title, $dept, $sal]) {
            $employees->push(Employee::create([
                'employee_code' => 'E-'.(101 + $i), 'name' => ['أحمد محمود', 'محمد لقيب', 'محمود فتحي', 'كريم سعيد', 'حسن علي', 'مصطفى رجب', 'عمرو حمدان', 'إبراهيم حلمي'][$i],
                'job_title' => $title, 'department' => $dept, 'salary' => $sal, 'hire_date' => now()->subMonths(rand(6, 30))->toDateString(),
                'phone' => '0100'.rand(1000000, 9999999), 'is_active' => true, 'created_by' => $this->by,
            ]));
        }

        $partners = collect([
            ['name' => 'الحاج فتحي عبدالله', 'capital' => 27558686],
            ['name' => 'م/ أحمد البنهساوي', 'capital' => 6000000],
            ['name' => 'شركة القروانة القابضة', 'capital' => 30000000],
        ])->map(function ($p) {
            $partner = Partner::create(['name' => $p['name'], 'join_date' => now()->subYear()->toDateString(), 'status' => 'active', 'created_by' => $this->by]);
            PartnerTransaction::create(['partner_id' => $partner->id, 'type' => 'deposit', 'amount' => $p['capital'], 'transaction_date' => now()->subYear()->toDateString(), 'description' => 'إيداع رأس مال', 'created_by' => $this->by]);

            return $partner;
        });

        // مواد + أصول
        foreach ([['أسمنت بورتلاندي', 'cement', 'طن', 3500, 240, 50], ['حديد تسليح 16مم', 'steel', 'طن', 42000, 85, 20], ['طوب أحمر', 'other', 'ألف طوبة', 1800, 120, 30], ['رمل', 'other', 'م³', 250, 400, 100], ['خشب فرز أول', 'wood', 'م³', 9000, 35, 10]] as [$n, $cat, $u, $price, $stock, $min]) {
            Material::create(['name' => $n, 'category' => $cat, 'unit' => $u, 'unit_price' => $price, 'current_stock' => $stock, 'min_stock' => $min, 'supplier_id' => $suppliers->random()->id, 'project_id' => $mainProject->id, 'created_by' => $this->by]);
        }
        foreach ([['AST-001', 'حفّار كاتربيلر 320', 'معدات ثقيلة', 4500000], ['AST-002', 'ونش برجي', 'معدات ثقيلة', 3200000], ['AST-003', 'خلاطة خرسانة مركزية', 'معدات', 1800000], ['AST-004', 'سيارة نقل عمالة', 'مركبات', 850000]] as [$code, $name, $cat, $val]) {
            Asset::create(['asset_code' => $code, 'asset_name' => $name, 'category' => $cat, 'purchase_date' => now()->subMonths(rand(6, 24))->toDateString(), 'purchase_value' => $val, 'depreciation_rate' => 15, 'useful_life_years' => 10, 'status' => 'active', 'created_by' => $this->by]);
        }

        // إيرادات (مستخلصات محصّلة) موزّعة على 6 شهور — أغلبها في البنك
        $revDescs = ['مستخلص جاري 1 — الفردوس', 'مستخلص جاري 2 — الفردوس', 'دفعة مقدّم — برج المستقبل', 'مستخلص نهائي — طريق 26 يوليو', 'مستخلص جاري — برج المستقبل', 'إضافات وأعمال خارج العقد'];
        foreach ($revDescs as $i => $d) {
            $amount = [46256155, 28087100, 14500000, 19600000, 9800000, 1830394][$i];
            $rev = Revenue::create(['project_id' => $projects->random()->id, 'description' => $d, 'amount' => $amount, 'revenue_date' => now()->subMonths(6 - $i)->addDays(rand(1, 20))->toDateString(), 'payment_method' => 'bank_transfer', 'bank_account_id' => $bankMain->id, 'created_by' => $this->by]);
            $this->ledger->post($bankMain, ['type' => 'deposit', 'amount' => $rev->amount, 'transaction_date' => $rev->revenue_date, 'description' => 'إيراد: '.$rev->description, 'related_type' => 'revenue', 'related_id' => $rev->id, 'created_by' => $this->by]);
        }

        // مصروفات موزّعة على 6 شهور بفئات متنوعة
        $cats = ['materials', 'labor', 'equipment', 'transportation', 'utilities', 'administrative', 'other'];
        $labels = ['توريد خرسانة جاهزة', 'مقايسة حديد تسليح', 'إيجار معدات', 'نقل مواد للموقع', 'كهرباء ومياه الموقع', 'مصروفات إدارية', 'مستلزمات متنوعة', 'وقود ومحروقات', 'صيانة معدات', 'مكافآت عمالة'];
        for ($m = 6; $m >= 0; $m--) {
            $count = rand(4, 7);
            for ($j = 0; $j < $count; $j++) {
                $fromBank = rand(0, 2) === 0;
                $exp = Expense::create([
                    'project_id' => $projects->random()->id, 'category' => $cats[array_rand($cats)],
                    'description' => $labels[array_rand($labels)], 'amount' => rand(20, 900) * 1000,
                    'expense_date' => now()->subMonths($m)->addDays(rand(1, 25))->toDateString(),
                    'payment_method' => $fromBank ? 'bank_transfer' : 'cash',
                    'bank_account_id' => $fromBank ? $bankMain->id : null, 'created_by' => $this->by,
                ]);
                if ($fromBank) {
                    $this->ledger->post($bankMain, ['type' => 'withdrawal', 'amount' => $exp->amount, 'transaction_date' => $exp->expense_date, 'description' => 'مصروف: '.$exp->description, 'related_type' => 'expense', 'related_id' => $exp->id, 'created_by' => $this->by]);
                }
            }
        }

        // أوامر شراء بأصناف تفصيلية + مشتريات موردين + مدفوعات
        $poMaterials = ['أسمنت بورتلاندي', 'حديد تسليح', 'رمل وزلط', 'طوب أحمر', 'بلاط سيراميك'];
        foreach ($suppliers->take(3) as $i => $sup) {
            $po = PurchaseOrder::create(['order_number' => 'PO-2026-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT), 'supplier_id' => $sup->id, 'project_id' => $mainProject->id, 'order_date' => now()->subMonths(rand(1, 4))->toDateString(), 'expected_delivery' => now()->subMonths(rand(0, 1))->toDateString(), 'status' => 'received', 'add_to_inventory' => true, 'created_by' => $this->by]);
            foreach (range(1, rand(2, 4)) as $n) {
                $qty = rand(10, 500); $price = rand(80, 2500);
                $po->items()->create(['description' => $poMaterials[array_rand($poMaterials)], 'unit' => 'طن', 'quantity' => $qty, 'unit_price' => $price, 'total_price' => bcmul((string) $qty, (string) $price, 2)]);
            }
            $po->recomputeTotals();
            $po->update(['paid_amount' => (int) round($po->net_amount * 0.7)]);

            // مشتريات تفصيلية مباشرة من المورّد (supplier_transactions)
            foreach (range(1, rand(1, 3)) as $n) {
                $qty = rand(5, 100); $price = rand(100, 3000);
                $total = bcmul((string) $qty, (string) $price, 2);
                SupplierTransaction::create([
                    'supplier_id' => $sup->id, 'project_id' => $mainProject->id,
                    'transaction_date' => now()->subMonths(rand(0, 3))->toDateString(),
                    'item_description' => $poMaterials[array_rand($poMaterials)], 'category' => 'materials',
                    'unit' => 'م3', 'quantity' => $qty, 'unit_price' => $price,
                    'total_amount' => $total, 'discount_percentage' => 0, 'net_amount' => $total,
                    'paid_amount' => (int) round((float) $total * 0.5), 'payment_method' => 'cash', 'created_by' => $this->by,
                ]);
            }
            SupplierPayment::create(['supplier_id' => $sup->id, 'amount' => rand(200, 3000) * 1000, 'payment_date' => now()->subMonths(rand(0, 3))->toDateString(), 'payment_method' => 'bank_transfer', 'bank_account_id' => $bankMain->id, 'created_by' => $this->by]);
        }

        // مستخلصات ببنود أعمال + دفعات مقاولين
        $workItems = ['أعمال حفر وردم', 'خرسانة عادية', 'خرسانة مسلحة', 'مباني طوب', 'بياض ومحارة', 'أعمال عزل'];
        foreach ($contractors as $i => $con) {
            $ded = rand(15, 120) * 1000;
            $ext = ContractorExtract::create(['extract_number' => 'EXT-2026-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT), 'contractor_id' => $con->id, 'project_id' => $mainProject->id, 'extract_date' => now()->subMonths(rand(1, 5))->toDateString(), 'deductions' => $ded, 'execution_percent' => rand(40, 95), 'status' => 'approved', 'approved_by' => $this->by, 'approved_at' => now(), 'created_by' => $this->by]);
            foreach (range(1, rand(2, 4)) as $n) {
                $qty = rand(50, 800); $price = rand(150, 1200);
                $ext->items()->create(['description' => $workItems[array_rand($workItems)], 'unit' => 'م3', 'quantity' => $qty, 'unit_price' => $price, 'total_price' => bcmul((string) $qty, (string) $price, 2)]);
            }
            $ext->recomputeTotals();
            $pay = (int) round((float) $ext->net_amount * 0.6);
            $ext->update(['paid_amount' => $pay, 'status' => 'partial']);
            ContractorPayment::create(['contractor_id' => $con->id, 'extract_id' => $ext->id, 'amount' => $pay, 'payment_date' => now()->subMonths(rand(0, 2))->toDateString(), 'payment_method' => 'cash', 'created_by' => $this->by]);
        }

        // فواتير
        foreach (['INV-2026-001' => $clients[0], 'INV-2026-002' => $clients[1]] as $num => $client) {
            $inv = Invoice::create(['invoice_number' => $num, 'client_id' => $client->id, 'project_id' => $mainProject->id, 'invoice_type' => 'progress', 'issue_date' => now()->subMonths(rand(1, 3))->toDateString(), 'due_date' => now()->addMonth()->toDateString(), 'tax_rate' => 14, 'status' => 'sent', 'created_by' => $this->by]);
            $inv->items()->createMany([
                ['description' => 'أعمال خرسانة مسلحة', 'quantity' => 1, 'unit_price' => rand(2000, 8000) * 1000, 'total_price' => 0],
                ['description' => 'أعمال مباني وبياض', 'quantity' => 1, 'unit_price' => rand(1000, 4000) * 1000, 'total_price' => 0],
            ]);
            $inv->items()->get()->each(fn ($it) => $it->update(['total_price' => bcmul((string) $it->quantity, (string) $it->unit_price, 2)]));
            $inv->recomputeTotals();
        }

        // إيراد آجل (شيك مؤجّل) + تحصيل جزئي بإيداع بنكي
        $creditRev = Revenue::create(['project_id' => $mainProject->id, 'description' => 'دفعة عميل آجلة', 'amount' => 80000, 'revenue_date' => now()->subDays(20)->toDateString(), 'due_date' => now()->addDays(15)->toDateString(), 'payment_method' => 'check', 'check_number' => 'CHK-2026-001', 'deferred_check' => true, 'created_by' => $this->by]);
        $col = $creditRev->collections()->create(['collection_date' => now()->subDays(5)->toDateString(), 'amount' => 30000, 'payment_method' => 'bank_transfer', 'bank_account_id' => $bankMain->id, 'created_by' => $this->by]);
        $this->ledger->post($bankMain, ['type' => 'deposit', 'amount' => $col->amount, 'transaction_date' => $col->collection_date, 'description' => 'تحصيل إيراد: '.$creditRev->description, 'related_type' => 'revenue_collection', 'related_id' => $col->id, 'created_by' => $this->by]);
        $creditRev->refreshCollectionStatus();

        // دفعة جزئية على أول فاتورة (تحصيل بنكي)
        $firstInvoice = Invoice::first();
        if ($firstInvoice) {
            $pay = $firstInvoice->payments()->create(['payment_date' => now()->subDays(3)->toDateString(), 'amount' => (int) round((float) $firstInvoice->total_amount * 0.4), 'payment_method' => 'bank_transfer', 'bank_account_id' => $bankMain->id, 'reference_number' => 'INVPAY-1', 'created_by' => $this->by]);
            $this->ledger->post($bankMain, ['type' => 'deposit', 'amount' => $pay->amount, 'transaction_date' => $pay->payment_date, 'description' => 'تحصيل فاتورة '.$firstInvoice->invoice_number, 'related_type' => 'invoice_payment', 'related_id' => $pay->id, 'created_by' => $this->by]);
            $firstInvoice->refreshPaymentStatus();
        }

        // مصروف آجل + قسط سداد (سحب بنكي)
        $creditExp = Expense::create(['project_id' => $mainProject->id, 'category' => 'administrative', 'description' => 'إيجار معدات آجل', 'amount' => 24000, 'expense_date' => now()->subDays(15)->toDateString(), 'payment_method' => 'cash', 'is_credit' => true, 'due_date' => now()->addDays(20)->toDateString(), 'created_by' => $this->by]);
        $creditExp->refreshPaymentStatus();
        $inst = $creditExp->payments()->create(['payment_date' => now()->subDays(2)->toDateString(), 'amount' => 10000, 'payment_method' => 'bank_transfer', 'bank_account_id' => $bankMain->id, 'reference_number' => 'EXPPAY-1', 'created_by' => $this->by]);
        $this->ledger->post($bankMain, ['type' => 'withdrawal', 'amount' => $inst->amount, 'transaction_date' => $inst->payment_date, 'description' => 'سداد مصروف: '.$creditExp->description, 'related_type' => 'expense_payment', 'related_id' => $inst->id, 'created_by' => $this->by]);
        $creditExp->refreshPaymentStatus();

        // عهدة لموظف + مصروف مصروف من العهدة (يُخصم تلقائياً من رصيد العهدة)
        $emp = Employee::first();
        if ($emp) {
            EmployeeTransaction::create(['employee_id' => $emp->id, 'type' => 'custody', 'amount' => 50000, 'transaction_date' => now()->subDays(30)->toDateString(), 'description' => 'عهدة نثرية', 'created_by' => $this->by]);
            $custExp = Expense::create(['project_id' => $mainProject->id, 'category' => 'materials', 'description' => 'مشتريات نثرية من العهدة', 'amount' => 12000, 'expense_date' => now()->subDays(10)->toDateString(), 'payment_method' => 'cash', 'delivered_by_employee_id' => $emp->id, 'created_by' => $this->by]);
            $custExp->syncCustodyTransaction();
            $custExp->refreshPaymentStatus();
        }

        // دفعة مورد باستقطاعات تفصيلية (ضريبة قيمة مضافة + تأمينات)
        $supPay = SupplierPayment::first();
        if ($supPay) {
            $vat = bcmul((string) $supPay->amount, '0.14', 2);
            $si = bcmul((string) $supPay->amount, '0.01', 2);
            $supPay->update(['vat' => $vat, 'social_insurance' => $si, 'total_deductions' => bcadd($vat, $si, 2)]);
        }

        // إيداع رأس مال لشريك بنظام أرباح + جدول أرباح + صرف أول قسط
        $partner = Partner::first();
        if ($partner) {
            $dep = PartnerDeposit::create(['partner_id' => $partner->id, 'amount' => 1000000, 'deposit_date' => now()->subMonths(6)->toDateString(), 'profit_rate' => 12, 'duration_months' => 12, 'payout_frequency' => 'quarterly', 'bank_account_id' => $bankMain->id, 'status' => 'active', 'created_by' => $this->by]);
            PartnerTransaction::create(['partner_id' => $partner->id, 'type' => 'deposit', 'amount' => $dep->amount, 'transaction_date' => $dep->deposit_date, 'partner_deposit_id' => $dep->id, 'bank_account_id' => $bankMain->id, 'description' => 'إيداع رأس مال', 'created_by' => $this->by]);
            $this->ledger->post($bankMain, ['type' => 'deposit', 'amount' => $dep->amount, 'transaction_date' => $dep->deposit_date, 'description' => 'إيداع رأس مال شريك: '.$partner->name, 'related_type' => 'partner_deposit', 'related_id' => $dep->id, 'created_by' => $this->by]);
            $dep->generateSchedule();
            $sched = $dep->schedules()->orderBy('due_date')->first();
            if ($sched) {
                $due = $sched->due_date->format('Y-m-d');
                $pt = PartnerTransaction::create(['partner_id' => $partner->id, 'type' => 'profit', 'amount' => $sched->amount, 'transaction_date' => $due, 'partner_deposit_id' => $dep->id, 'profit_period' => $due, 'bank_account_id' => $bankMain->id, 'description' => 'صرف أرباح شريك', 'created_by' => $this->by]);
                $this->ledger->post($bankMain, ['type' => 'withdrawal', 'amount' => $sched->amount, 'transaction_date' => $due, 'description' => 'صرف أرباح شريك: '.$partner->name, 'related_type' => 'partner_profit', 'related_id' => $pt->id, 'created_by' => $this->by]);
                $sched->update(['is_paid' => true, 'paid_date' => $due, 'partner_transaction_id' => $pt->id]);
            }
        }

        // تكاليف المشاريع حسب بنود الأعمال (BOQ) + إسناد موظفين للمشروع الرئيسي
        $costItems = [['أعمال حفر وردم', 'مقاول الحفر'], ['خرسانة مسلحة', 'مصنع الخرسانة'], ['أعمال مباني', 'مقاول المباني'], ['تشطيبات', 'مقاول التشطيب'], ['أعمال كهرباء', 'مقاول الكهرباء']];
        foreach ($costItems as [$wi, $cs]) {
            $qty = rand(100, 1500); $price = rand(200, 1800);
            \App\Models\ProjectCost::create(['project_id' => $mainProject->id, 'work_item' => $wi, 'contractor_supplier' => $cs, 'category' => 'أعمال', 'unit' => 'م3', 'quantity' => $qty, 'unit_price' => $price, 'amount' => bcmul((string) $qty, (string) $price, 2), 'cost_date' => now()->subMonths(rand(1, 5))->toDateString(), 'created_by' => $this->by]);
        }
        foreach ($employees->take(4) as $idx => $emp2) {
            \App\Models\ProjectEmployee::create(['project_id' => $mainProject->id, 'employee_id' => $emp2->id, 'role' => ['مهندس موقع', 'مشرف', 'فني', 'عامل'][$idx] ?? 'عضو فريق', 'start_date' => now()->subMonths(5)->toDateString()]);
        }

        // مراكز التكلفة + ربطها ببعض المصروفات والإيرادات
        $centers = [];
        foreach ([['الإدارة العامة', 'ADMIN'], ['المشاريع', 'PROJ'], ['النقل والمعدات', 'TRANS'], ['الصيانة', 'MAINT']] as [$cn, $cc]) {
            $centers[] = \App\Models\CostCenter::create(['name' => $cn, 'code' => $cc, 'is_active' => true]);
        }
        Expense::query()->inRandomOrder()->take(20)->get()->each(fn ($e) => $e->update(['cost_center_id' => $centers[array_rand($centers)]->id]));
        Revenue::query()->inRandomOrder()->take(4)->get()->each(fn ($r) => $r->update(['cost_center_id' => $centers[1]->id]));

        // عقد للمشروع الرئيسي
        \App\Models\ProjectContract::create(['project_id' => $mainProject->id, 'contract_number' => 'CT-2026-001', 'contract_type' => 'main', 'title' => 'عقد إنشاء '.$mainProject->name, 'first_party' => 'شركة القروانة', 'second_party' => $mainProject->client?->name ?? 'العميل', 'signing_date' => now()->subMonths(6)->toDateString(), 'start_date' => now()->subMonths(6)->toDateString(), 'end_date' => now()->addMonths(6)->toDateString(), 'contract_value' => $mainProject->contract_value ?: 50000000, 'status' => 'active', 'signed_date' => now()->subMonths(6)->toDateString(), 'advance_payment' => 5000000, 'retention_percent' => 10, 'warranty_months' => 12, 'consultant' => 'مكتب الاستشارات الهندسية', 'created_by' => $this->by]);

        // شيكات بحالات مختلفة (وارد/صادر)
        \App\Models\Cheque::create(['cheque_number' => 'CHK-IN-1001', 'bank_account_id' => $bankMain->id, 'direction' => 'incoming', 'party_name' => $clients[0]->name, 'amount' => 500000, 'issue_date' => now()->subDays(20)->toDateString(), 'due_date' => now()->addDays(10)->toDateString(), 'status' => 'pending', 'created_by' => $this->by]);
        \App\Models\Cheque::create(['cheque_number' => 'CHK-IN-1002', 'bank_account_id' => $bankMain->id, 'direction' => 'incoming', 'party_name' => $clients[1]->name, 'amount' => 300000, 'issue_date' => now()->subDays(40)->toDateString(), 'due_date' => now()->subDays(5)->toDateString(), 'status' => 'deposited', 'created_by' => $this->by]);
        $chq = \App\Models\Cheque::create(['cheque_number' => 'CHK-OUT-2001', 'bank_account_id' => $bankMain->id, 'direction' => 'outgoing', 'party_name' => $suppliers[0]->name, 'amount' => 150000, 'issue_date' => now()->subDays(30)->toDateString(), 'due_date' => now()->subDays(10)->toDateString(), 'status' => 'cleared', 'created_by' => $this->by]);
        $this->ledger->post($bankMain, ['type' => 'withdrawal', 'amount' => $chq->amount, 'transaction_date' => $chq->due_date->toDateString(), 'description' => 'شيك '.$chq->cheque_number, 'related_type' => 'cheque', 'related_id' => $chq->id, 'created_by' => $this->by]);
        \App\Models\Cheque::create(['cheque_number' => 'CHK-OUT-2002', 'bank_account_id' => $bankMain->id, 'direction' => 'outgoing', 'party_name' => $suppliers[1]->name, 'amount' => 80000, 'issue_date' => now()->subDays(15)->toDateString(), 'due_date' => now()->addDays(15)->toDateString(), 'status' => 'pending', 'created_by' => $this->by]);

        // ===== موديولات المقاولات الجديدة =====
        // خطابات الضمان
        $project = \App\Models\Project::first();
        $bank = \App\Models\BankAccount::first();
        \App\Models\LetterOfGuarantee::create(['lg_number' => 'LG-2026-0001', 'type' => 'bid', 'beneficiary' => 'الهيئة العامة للأبنية التعليمية', 'bank_name' => $bank?->bank_name ?? 'البنك الأهلي المصري', 'bank_account_id' => $bank?->id, 'amount' => 150000, 'issue_date' => now()->subDays(60)->toDateString(), 'expiry_date' => now()->addDays(20)->toDateString(), 'status' => 'active', 'project_id' => $project?->id, 'notes' => 'خطاب ضمان ابتدائي لدخول مناقصة', 'created_by' => $this->by]);
        \App\Models\LetterOfGuarantee::create(['lg_number' => 'LG-2026-0002', 'type' => 'performance', 'beneficiary' => 'وزارة الإسكان', 'bank_name' => $bank?->bank_name ?? 'بنك مصر', 'bank_account_id' => $bank?->id, 'amount' => 500000, 'issue_date' => now()->subDays(30)->toDateString(), 'expiry_date' => now()->addMonths(8)->toDateString(), 'status' => 'active', 'project_id' => $project?->id, 'notes' => 'خطاب ضمان نهائي حسن تنفيذ', 'created_by' => $this->by]);
        \App\Models\LetterOfGuarantee::create(['lg_number' => 'LG-2026-0003', 'type' => 'advance', 'beneficiary' => 'شركة المقاولون العرب', 'bank_name' => $bank?->bank_name ?? 'البنك التجاري الدولي', 'bank_account_id' => $bank?->id, 'amount' => 250000, 'issue_date' => now()->subMonths(6)->toDateString(), 'expiry_date' => now()->subDays(5)->toDateString(), 'status' => 'released', 'project_id' => $project?->id, 'notes' => 'تم الإفراج بعد سداد الدفعة المقدمة', 'created_by' => $this->by]);

        // وثائق التأمين
        \App\Models\InsurancePolicy::create(['policy_number' => 'INS-2026-0001', 'type' => 'contractor_all_risk', 'provider' => 'شركة مصر للتأمين', 'coverage_amount' => 5000000, 'premium' => 75000, 'start_date' => now()->subMonths(11)->toDateString(), 'expiry_date' => now()->addDays(25)->toDateString(), 'status' => 'active', 'project_id' => $project?->id, 'notes' => 'وثيقة تأمين كل أخطار المقاولين على المشروع.', 'created_by' => $this->by]);
        \App\Models\InsurancePolicy::create(['policy_number' => 'INS-2026-0002', 'type' => 'liability', 'provider' => 'GIG للتأمين', 'coverage_amount' => 2000000, 'premium' => 30000, 'start_date' => now()->subMonths(2)->toDateString(), 'expiry_date' => now()->addMonths(10)->toDateString(), 'status' => 'active', 'project_id' => $project?->id, 'notes' => 'تأمين المسؤولية المدنية تجاه الغير.', 'created_by' => $this->by]);

        // المناقصات
        $client = \App\Models\Client::first();
        \App\Models\Tender::create(['tender_number' => 'TND-2026-0001', 'title' => 'إنشاء مبنى إداري - هيئة الأبنية التعليمية', 'client_id' => $client?->id, 'estimated_value' => 5000000, 'bid_value' => 4750000, 'submission_date' => now()->subDays(30)->toDateString(), 'status' => 'won', 'project_id' => $project?->id, 'notes' => 'مناقصة فائزة ومحوّلة لمشروع.', 'created_by' => $this->by]);
        \App\Models\Tender::create(['tender_number' => 'TND-2026-0002', 'title' => 'توريد وتركيب أعمال كهروميكانيكية', 'client_id' => $client?->id, 'estimated_value' => 1800000, 'bid_value' => 1750000, 'submission_date' => now()->subDays(5)->toDateString(), 'status' => 'submitted', 'notes' => 'بانتظار نتيجة الترسية.', 'created_by' => $this->by]);
        \App\Models\Tender::create(['tender_number' => 'TND-2026-0003', 'title' => 'رصف طريق رئيسي - مرحلة أولى', 'client_id' => $client?->id, 'estimated_value' => 3200000, 'bid_value' => null, 'submission_date' => now()->addDays(10)->toDateString(), 'status' => 'draft', 'notes' => 'قيد إعداد العرض الفني والمالي.', 'created_by' => $this->by]);

        // عروض الأسعار
        if ($client) {
            $q1 = \App\Models\Quotation::create(['quotation_number' => 'QUO-2026-0001', 'client_id' => $client->id, 'project_id' => $project?->id, 'issue_date' => now()->subDays(10)->toDateString(), 'valid_until' => now()->addDays(20)->toDateString(), 'tax_rate' => 14, 'status' => 'accepted', 'notes' => 'عرض سعر تجريبي مقبول', 'created_by' => $this->by]);
            foreach ([['توريد وتركيب أعمال كهرباء', 1, 25000], ['أعمال سباكة', 2, 8000], ['دهانات', 3, 4500]] as [$d, $qty, $up]) {
                $q1->items()->create(['description' => $d, 'quantity' => $qty, 'unit_price' => $up, 'total_price' => bcmul((string) $qty, (string) $up, 2)]);
            }
            $q1->recomputeTotals();
            $q2 = \App\Models\Quotation::create(['quotation_number' => 'QUO-2026-0002', 'client_id' => $client->id, 'project_id' => $project?->id, 'issue_date' => now()->subDays(3)->toDateString(), 'valid_until' => now()->addDays(27)->toDateString(), 'tax_rate' => 14, 'status' => 'sent', 'notes' => 'عرض سعر تجريبي مُرسل', 'created_by' => $this->by]);
            foreach ([['أعمال خرسانة مسلحة', 1, 60000], ['أعمال محارة', 1, 15000]] as [$d, $qty, $up]) {
                $q2->items()->create(['description' => $d, 'quantity' => $qty, 'unit_price' => $up, 'total_price' => bcmul((string) $qty, (string) $up, 2)]);
            }
            $q2->recomputeTotals();
        }

        // مراحل المشروع
        if ($project) {
            \App\Models\ProjectMilestone::insert([
                ['project_id' => $project->id, 'name' => 'التأسيس', 'planned_start' => now()->subMonths(6)->toDateString(), 'planned_end' => now()->subMonths(5)->toDateString(), 'actual_start' => now()->subMonths(6)->toDateString(), 'actual_end' => now()->subMonths(5)->toDateString(), 'progress_percent' => 100, 'status' => 'done', 'sort' => 1, 'notes' => null, 'created_at' => now(), 'updated_at' => now()],
                ['project_id' => $project->id, 'name' => 'الهيكل الخرساني', 'planned_start' => now()->subMonths(5)->toDateString(), 'planned_end' => now()->subMonths(1)->toDateString(), 'actual_start' => now()->subMonths(5)->toDateString(), 'actual_end' => null, 'progress_percent' => 60, 'status' => 'in_progress', 'sort' => 2, 'notes' => null, 'created_at' => now(), 'updated_at' => now()],
                ['project_id' => $project->id, 'name' => 'التشطيبات', 'planned_start' => now()->subMonths(2)->toDateString(), 'planned_end' => now()->subDays(10)->toDateString(), 'actual_start' => null, 'actual_end' => null, 'progress_percent' => 0, 'status' => 'pending', 'sort' => 3, 'notes' => null, 'created_at' => now(), 'updated_at' => now()],
                ['project_id' => $project->id, 'name' => 'التسليم', 'planned_start' => now()->addMonths(2)->toDateString(), 'planned_end' => now()->addMonths(3)->toDateString(), 'actual_start' => null, 'actual_end' => null, 'progress_percent' => 0, 'status' => 'pending', 'sort' => 4, 'notes' => null, 'created_at' => now(), 'updated_at' => now()],
            ]);

            // يومية الموقع
            foreach ([['days' => 2, 'weather' => 'مشمس حار', 'labor' => 24, 'work' => 'صب خرسانة الأعمدة بالدور الأرضي وتجهيز الشدّة الخشبية للسقف.'], ['days' => 1, 'weather' => 'معتدل', 'labor' => 18, 'work' => 'أعمال المباني الطوب للحوائط الخارجية وتركيب حديد التسليح للأسقف.'], ['days' => 0, 'weather' => 'غائم جزئياً', 'labor' => 27, 'work' => 'أعمال التشطيبات الداخلية وبدء أعمال السباكة والكهرباء بالدور الأول.']] as $s) {
                \App\Models\DailySiteReport::create(['project_id' => $project->id, 'report_date' => now()->subDays($s['days'])->toDateString(), 'weather' => $s['weather'], 'work_done' => $s['work'], 'labor_count' => $s['labor'], 'equipment_notes' => 'الونش الرئيسي يعمل بكفاءة، تم صيانة خلاطة الخرسانة.', 'progress_notes' => 'سير العمل مطابق للجدول الزمني المعتمد.', 'incidents' => null, 'created_by' => $this->by]);
            }

            // حضور العمالة
            $attDate = now()->toDateString();
            foreach (\App\Models\Employee::take(4)->get() as $emp) {
                \App\Models\LaborAttendance::create(['project_id' => $project->id, 'attendance_date' => $attDate, 'employee_id' => $emp->id, 'hours' => 8, 'present' => true, 'wage' => 300, 'created_by' => $this->by]);
            }
            foreach (['عامل يومية - أحمد', 'عامل يومية - محمود'] as $name) {
                \App\Models\LaborAttendance::create(['project_id' => $project->id, 'attendance_date' => $attDate, 'laborer_name' => $name, 'hours' => 8, 'present' => true, 'wage' => 250, 'created_by' => $this->by]);
            }
        }

        // سجل المعدات
        if ($asset = \App\Models\Asset::first()) {
            \App\Models\EquipmentLog::create(['asset_id' => $asset->id, 'log_type' => 'maintenance', 'log_date' => now()->subDays(5)->toDateString(), 'cost' => 1500, 'description' => 'صيانة دورية وتغيير زيوت', 'next_service_date' => now()->addDays(10)->toDateString(), 'created_by' => $this->by]);
            \App\Models\EquipmentLog::create(['asset_id' => $asset->id, 'log_type' => 'usage', 'log_date' => now()->subDays(2)->toDateString(), 'operating_hours' => 8.5, 'description' => 'تشغيل في موقع المشروع', 'created_by' => $this->by]);
        }

        // أذون صرف المواد
        $materials = \App\Models\Material::take(2)->get();
        if ($project && $materials->count() >= 1) {
            $r1 = \App\Models\MaterialRequisition::create(['requisition_number' => 'MR-2026-0001', 'project_id' => $project->id, 'request_date' => now()->subDays(3)->toDateString(), 'status' => 'pending', 'notes' => 'طلب مواد للموقع - بانتظار الاعتماد', 'created_by' => $this->by]);
            foreach ($materials as $m) {
                $r1->items()->create(['material_id' => $m->id, 'quantity' => 5]);
            }
            $r2 = \App\Models\MaterialRequisition::create(['requisition_number' => 'MR-2026-0002', 'project_id' => $project->id, 'request_date' => now()->subDays(7)->toDateString(), 'status' => 'approved', 'approved_by' => $this->by, 'approved_at' => now()->subDays(6), 'notes' => 'طلب مواد مصروف بالكامل', 'created_by' => $this->by]);
            foreach ($materials as $m) {
                $r2->items()->create(['material_id' => $m->id, 'quantity' => 3]);
            }
            \Illuminate\Support\Facades\DB::transaction(function () use ($r2) {
                foreach ($r2->items as $item) {
                    $material = \App\Models\Material::lockForUpdate()->find($item->material_id);
                    if (! $material) { continue; }
                    $before = (string) $material->current_stock;
                    $after = bcsub($before, (string) $item->quantity, 2);
                    if (bccomp($after, '0', 2) < 0) { $after = '0.00'; }
                    $unitPrice = (string) ($material->unit_price ?? 0);
                    \App\Models\InventoryMovement::create(['material_id' => $material->id, 'type' => 'out', 'quantity' => $item->quantity, 'unit_price' => $unitPrice, 'total_value' => bcmul((string) $item->quantity, $unitPrice, 2), 'stock_before' => $before, 'stock_after' => $after, 'movement_date' => now()->subDays(6)->toDateString(), 'project_id' => $r2->project_id, 'reason' => 'صرف إذن مواد '.$r2->requisition_number, 'reference_type' => 'material_requisition', 'reference_id' => $r2->id, 'created_by' => 1]);
                    $material->current_stock = $after;
                    $material->save();
                    $item->update(['issued_quantity' => $item->quantity]);
                }
                $r2->update(['status' => 'issued']);
            });
        }

        // ===== موديولات التنفيذ: أوامر التغيير + العيوب + RFI =====
        if ($project) {
            \App\Models\ChangeOrder::create(['co_number' => 'CO-2026-0001', 'project_id' => $project->id, 'title' => 'أعمال إضافية بالدور الأرضي', 'description' => 'تنفيذ أعمال إضافية بالدور الأرضي خارج نطاق العقد الأصلي.', 'change_type' => 'addition', 'amount' => 250000, 'status' => 'approved', 'request_date' => now()->subDays(20)->toDateString(), 'approved_by' => $this->by, 'approved_at' => now()->subDays(15), 'created_by' => $this->by]);
            \App\Models\ChangeOrder::create(['co_number' => 'CO-2026-0002', 'project_id' => $project->id, 'title' => 'خصم بند تشطيبات ملغاة', 'description' => 'خصم قيمة بنود تشطيبات تم إلغاؤها بطلب العميل.', 'change_type' => 'deduction', 'amount' => 120000, 'status' => 'approved', 'request_date' => now()->subDays(12)->toDateString(), 'approved_by' => $this->by, 'approved_at' => now()->subDays(8), 'created_by' => $this->by]);
            \App\Models\ChangeOrder::create(['co_number' => 'CO-2026-0003', 'project_id' => $project->id, 'title' => 'توريد وتركيب أعمال كهرباء إضافية', 'description' => 'طلب اعتماد أعمال كهرباء إضافية.', 'change_type' => 'addition', 'amount' => 80000, 'status' => 'pending', 'request_date' => now()->subDays(3)->toDateString(), 'created_by' => $this->by]);

            $snagEmp = \App\Models\Employee::first()?->id;
            \App\Models\Snag::create(['project_id' => $project->id, 'title' => 'تشققات في محارة الدور الثاني', 'description' => 'تشققات واضحة تحتاج معالجة قبل التسليم.', 'location' => 'الدور الثاني', 'priority' => 'high', 'status' => 'open', 'assigned_employee_id' => $snagEmp, 'due_date' => now()->addDays(7)->toDateString(), 'created_by' => $this->by]);
            \App\Models\Snag::create(['project_id' => $project->id, 'title' => 'دهان غير مكتمل في المدخل', 'location' => 'المدخل الرئيسي', 'priority' => 'medium', 'status' => 'open', 'assigned_employee_id' => $snagEmp, 'due_date' => now()->addDays(14)->toDateString(), 'created_by' => $this->by]);
            \App\Models\Snag::create(['project_id' => $project->id, 'title' => 'تسريب في تركيبات السباكة بالحمام', 'location' => 'حمام الدور الأرضي', 'priority' => 'high', 'status' => 'in_progress', 'assigned_employee_id' => $snagEmp, 'due_date' => now()->addDays(3)->toDateString(), 'created_by' => $this->by]);
            \App\Models\Snag::create(['project_id' => $project->id, 'title' => 'ضبط أبواب الألوميتال', 'location' => 'الواجهة الأمامية', 'priority' => 'low', 'status' => 'closed', 'due_date' => now()->subDays(5)->toDateString(), 'closed_at' => now()->subDays(2), 'created_by' => $this->by]);

            \App\Models\Rfi::create(['rfi_number' => 'RFI-2026-0001', 'project_id' => $project->id, 'subject' => 'استفسار عن تفاصيل تسليح الأساسات', 'question' => 'برجاء توضيح أقطار حديد التسليح والمسافات البينية لقواعد المشروع طبقاً للوحات الإنشائية.', 'status' => 'open', 'raised_to' => 'الاستشاري', 'due_date' => now()->subDays(5)->toDateString(), 'created_by' => $this->by]);
            \App\Models\Rfi::create(['rfi_number' => 'RFI-2026-0002', 'project_id' => $project->id, 'subject' => 'نوع العزل المائي للأسطح', 'question' => 'ما هو نوع العزل المائي المعتمد للأسطح في المواصفات الفنية؟', 'answer' => 'يُستخدم عزل مائي بيتوميني بطبقتين متقاطعتين طبقاً للمواصفات المرفقة بالعقد.', 'status' => 'answered', 'raised_to' => 'المهندس المقيم', 'due_date' => now()->subDays(10)->toDateString(), 'answered_at' => now()->subDays(8), 'created_by' => $this->by]);
            \App\Models\Rfi::create(['rfi_number' => 'RFI-2026-0003', 'project_id' => $project->id, 'subject' => 'تفاصيل تشطيبات الواجهات', 'question' => 'برجاء اعتماد عينة التشطيب النهائي للواجهات الخارجية قبل البدء في التنفيذ.', 'status' => 'open', 'raised_to' => 'الاستشاري', 'due_date' => now()->addDays(15)->toDateString(), 'created_by' => $this->by]);
        }

        // الاعتمادات الفنية (Submittals)
        if ($project) {
            \App\Models\Submittal::create(['submittal_number' => 'SUB-2026-0001', 'project_id' => $project->id, 'title' => 'اعتماد عينة بلاط الأرضيات', 'type' => 'material', 'spec_section' => '09 30 00', 'description' => 'عينة بلاط بورسلين للواجهات الداخلية.', 'status' => 'approved', 'submitted_to' => 'الاستشاري', 'due_date' => now()->subDays(10)->toDateString(), 'reviewed_at' => now()->subDays(8), 'review_notes' => 'معتمد حسب المواصفات.', 'created_by' => $this->by]);
            \App\Models\Submittal::create(['submittal_number' => 'SUB-2026-0002', 'project_id' => $project->id, 'title' => 'لوحة تنفيذية لأعمال الحديد', 'type' => 'shop_drawing', 'spec_section' => '03 20 00', 'description' => 'لوحات تسليح الأعمدة والكمرات.', 'status' => 'under_review', 'submitted_to' => 'المكتب الاستشاري', 'due_date' => now()->addDays(7)->toDateString(), 'created_by' => $this->by]);
            \App\Models\Submittal::create(['submittal_number' => 'SUB-2026-0003', 'project_id' => $project->id, 'title' => 'طريقة تنفيذ صب الخرسانة', 'type' => 'method_statement', 'spec_section' => '03 30 00', 'description' => 'method statement لأعمال الصب الجماعي.', 'status' => 'submitted', 'submitted_to' => 'الاستشاري', 'due_date' => now()->subDays(3)->toDateString(), 'created_by' => $this->by]);
        }

        // مسيّر الرواتب — مسيّر معتمد للشهر الماضي (بدون ترحيل بنكي)
        $run = \App\Models\PayrollRun::create([
            'run_number' => 'PR-'.now()->subMonth()->format('Y-m'),
            'period_year' => now()->subMonth()->year,
            'period_month' => now()->subMonth()->month,
            'status' => 'approved',
            'total_net' => 0,
            'created_by' => $this->by,
        ]);
        foreach (\App\Models\Employee::where('is_active', true)->get() as $employee) {
            $item = new \App\Models\PayrollItem([
                'payroll_run_id' => $run->id, 'employee_id' => $employee->id,
                'basic_salary' => (string) $employee->salary, 'allowances' => 0,
                'bonus' => 0, 'deductions' => 0, 'advance_deduction' => 0,
            ]);
            $item->net_salary = $item->computeNet();
            $item->save();
        }
        $run->load('items');
        $run->recomputeTotal();

        // طلبات الفحص (IR) + محاضر الاجتماعات
        if ($project) {
            \App\Models\InspectionRequest::create(['ir_number' => 'IR-2026-0001', 'project_id' => $project->id, 'title' => 'فحص صب خرسانة القواعد', 'type' => 'concrete_pour', 'location' => 'القطاع الأول - القواعد', 'scheduled_date' => now()->subDays(10)->toDateString(), 'status' => 'approved', 'result' => 'تم الفحص ومطابقة المواصفات، الموافقة على الصب.', 'inspector' => 'م. خالد عبد الله', 'inspected_at' => now()->subDays(10), 'created_by' => $this->by]);
            \App\Models\InspectionRequest::create(['ir_number' => 'IR-2026-0002', 'project_id' => $project->id, 'title' => 'فحص حديد تسليح الأعمدة', 'type' => 'steel', 'location' => 'الدور الأرضي - الأعمدة', 'scheduled_date' => now()->addDays(5)->toDateString(), 'status' => 'pending', 'created_by' => $this->by]);
            \App\Models\InspectionRequest::create(['ir_number' => 'IR-2026-0003', 'project_id' => $project->id, 'title' => 'معاينة أعمال التشطيبات', 'type' => 'finishing', 'location' => 'الدور الثاني - الشقق', 'scheduled_date' => now()->subDays(3)->toDateString(), 'status' => 'pending', 'created_by' => $this->by]);

            \App\Models\Meeting::create(['meeting_number' => 'MIN-2026-0001', 'project_id' => $project->id, 'title' => 'اجتماع انطلاق المشروع (Kickoff)', 'meeting_date' => now()->subMonth()->startOfMonth()->addDays(3)->toDateString(), 'location' => 'قاعة الاجتماعات الرئيسية', 'attendees' => "م. أحمد عبد الله - مدير المشروع\nم. سارة محمود - مهندسة موقع\nأ. خالد فؤاد - ممثل العميل", 'agenda' => "1. استعراض نطاق العمل والجدول الزمني\n2. توزيع المسؤوليات\n3. آلية الموافقات والمستخلصات", 'decisions' => "- اعتماد الجدول الزمني المبدئي 6 أشهر\n- بدء أعمال الحفر خلال أسبوع", 'action_items' => "- تجهيز خطة السلامة (م. ياسر)\n- إصدار أمر توريد الخرسانة (م. أحمد)", 'next_meeting_date' => now()->subMonth()->startOfMonth()->addDays(10)->toDateString(), 'created_by' => $this->by]);
            \App\Models\Meeting::create(['meeting_number' => 'MIN-2026-0002', 'project_id' => $project->id, 'title' => 'اجتماع المتابعة الأسبوعي', 'meeting_date' => now()->startOfWeek()->addDay()->toDateString(), 'location' => 'مكتب الموقع', 'attendees' => "م. أحمد عبد الله\nم. سارة محمود\nم. ياسر النجار", 'agenda' => "1. نسبة الإنجاز مقابل الخطة\n2. معوقات التنفيذ\n3. حالة التوريدات", 'decisions' => "- اعتماد صرف مستخلص المقاول الأول\n- زيادة عمالة الموقع", 'action_items' => "- متابعة توريد حديد التسليح (م. أحمد)\n- إعداد تقرير الإنجاز للعميل (م. سارة)", 'next_meeting_date' => now()->startOfWeek()->addWeek()->addDay()->toDateString(), 'created_by' => $this->by]);
        }

        // ===== قيود يومية تجريبية + ترحيل تلقائي من المستندات =====
        if (class_exists(\App\Models\Account::class) && class_exists(\App\Services\JournalService::class)) {
            $journal = app(\App\Services\JournalService::class);
            $cashId = \App\Models\Account::resolve('1102')?->id;
            $capitalId = \App\Models\Account::resolve('3101')?->id;
            if ($cashId && $capitalId) {
                $e1 = $journal->createEntry(
                    ['entry_date' => now()->subMonths(12)->toDateString(), 'description' => 'ضخّ رأس مال نقدي افتتاحي', 'created_by' => $this->by],
                    [
                        ['account_id' => $cashId, 'debit' => 5000000, 'credit' => 0, 'description' => 'إيداع رأس المال'],
                        ['account_id' => $capitalId, 'debit' => 0, 'credit' => 5000000, 'description' => 'رأس المال'],
                    ]
                );
                $journal->post($e1, $this->by);
            }
            // ترحيل تلقائي لكل المستندات التشغيلية (فواتير/مصروفات/مستخلصات/دفعات/رواتب...)
            app(\App\Services\JournalPostingService::class)->generateAll($this->by);
        }

        $this->command->info('تم إنشاء بيانات تجريبية واقعية لشركة مقاولات.');
    }
}
