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

        $this->command->info('تم إنشاء بيانات تجريبية واقعية لشركة مقاولات.');
    }
}
