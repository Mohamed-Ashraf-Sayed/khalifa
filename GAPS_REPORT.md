# تقرير النواقص — مقارنة النظام القديم بالجديد (القروانة)

**تاريخ:** 2026-06-03  
**الطريقة:** workflow متعدد الوكلاء (114 وكيل) قارن 12 دومين، وكل نقص اتأكد منه عكسياً.  
**الإجمالي:** 98 نقص مؤكد — 🟠 خطير: 31 | 🟡 متوسط: 45 | ⚪ بسيط: 22

---

## Suppliers (internal vs external split)

> النظام الجديد بيغطّي جزء أساسي بس مش كامل من نطاق الموردين. حقل النوع (type: external/internal) اتحافظ عليه في جدول suppliers، والموردين الداخليين (مقاول باطن) اتعمل لهم module منفصل تماماً اسمه Contractor + ContractorExtract، وده اختيار معماري معقول يغطّي فكرة المستخلصات للمقاولين الباطن. لكن في نواقص جوهرية وخطيرة: (1) جدول supplier_transactions بالكامل (دفتر مشتريات/توريدات المورد بالبيان والكمية والوحدة والسعر والخصم والصافي) مش موجود خالص في النظام الجديد — مفيش migration ولا model ولا أي بديل، والنظام بيعتمد بس على purchase_orders. (2) جدول supplier_payments في الـlegacy فيه نظام استقطاعات ومستحقات تفصيلي ضخم (ضريبة قيمة مضافة، تأمين 5%، تأمينات اجتماعية، أرباح تجارية توريدات/أعمال، مهن هندسية/تطبيقية، تحويل بنكي، استقطاعات أخرى، إجمالي الاستقطاعات) واتشال بالكامل — supplier_payments الجديد فيه amount واحد بس. (3) حقول الأرصدة الافتتاحية/الحالية وحد الائتمان وشروط السداد (opening_balance, current_balance, credit_limit, payment_terms) اتشالت من جدول الموردين. (4) ربط دفعات المورد بعهدة الموظف (employee_id, employee_custody_deducted, expense_id) مش موجود. (5) كشف حساب المورد بالرصيد التراكمي وزر الطباعة وفلاتر الفئات والتصدير ناقصة في الواجهة الجديدة. النواقص دي مؤثرة جداً لشركة مقاولات لأنها بتمسّ المحاسبة الفعلية للموردين والمقاولين والضرايب والتأمينات.

### 🟠 خطير — غياب جدول حركات/توريدات المورد (supplier_transactions) بالكامل
- **النوع:** module
- **الدليل (القديم):** esystem.sql سطر 4075 CREATE TABLE `supplier_transactions` بأعمدة: item_description, unit, quantity, category, unit_price, total_amount, discount_percentage, net_amount, paid_amount, remaining_amount (GENERATED), payment_method, check_number. ومستخدم في suppliers.php سطر 76-86 (شاشة عمليات الشراء) وفي supplier_details.php سطر 77-86 (معاملات المواد والمعدات مستبعدة منها أوامر الشراء).
- **الحالة في الجديد:** تم التأكيد: الجدول فعلاً غايب. grep لـ supplier_transaction/SupplierTransaction في كل /Users/mohamed/Downloads/qarwana رجّع صفر. الموجود بس suppliers + supplier_payments + purchase_orders. لا يوجد جدول purchase_order_items ولا علاقة items() في PurchaseOrder. أهم الملفات: جدول purchase_orders (database/migrations/2026_06_03_012001_create_purchase_orders_table.php) هيدر فقط بأعمدة order_number/supplier_id/project_id/order_date/expected_delivery/status/total_amount/notes — مفيش item_description/unit/quantity/unit_price/discount_percentage/net_amount/paid_amount/remaining_amount/payment_method/check_number على مستوى السطر. الفورم (resources/views/purchase_orders/form.blade.php) بياخد المبلغ الإجمالي مرة واحدة بس، والكنترولر (app/Http/Controllers/PurchaseOrderController.php) بيـvalidate نفس الحقول الهيدر فقط. الرصيد بيتحسب derived في Supplier::balanceDue() = مجموع أوامر الشراء (status=received) - المدفوعات. النظام القديم (esystem.sql سطر 4075) كان فيه per-line detail + paid/remaining (GENERATED) + payment_method/check_number، وكمان كان بيخدم معاملات مواد/معدات قائمة بذاتها (عملية شراء مادة / إضافة معاملة مواد) بدفعات جزئية ومتبقي. الـ net_amount/paid_amount اللي ظهرت في النظام الجديد تخص ContractorExtract و Invoice (دومينات تانية) مش الموردين. جداول materials/inventory_movements بتتابع المخزون (quantity/unit/unit_price) بس من غير خصم/مسدد/متبقي/طريقة دفع/رقم شيك على مستوى المعاملة. الخلاصة: feature ناقصة فعلاً مع فقدان تفاصيل السطر وتتبع الدفع الجزئي/المتبقي لكل توريد.
- **التوصية:** إنشاء جدول supplier_transactions (أو SupplierTransaction model + migration) يحفظ توريدات/مشتريات المورد التفصيلية: البيان، الوحدة، الكمية، الفئة، سعر الوحدة، الإجمالي، نسبة الخصم، الصافي، المسدد، المتبقي (محسوب)، طريقة الدفع، رقم الشيك — وربطه بالمورد والمشروع، مع شاشة 'عمليات الشراء' وفلتر بالمورد والفئة زي suppliers.php.

### 🟠 خطير — غياب نظام الاستقطاعات والمستحقات التفصيلي في دفعات الموردين/المقاولين
- **النوع:** logic
- **الدليل (القديم):** esystem.sql سطر 3988 CREATE TABLE `supplier_payments` يحتوي على: total_due, regular_payment, extra_payment, engineering_professions, arts_specialists, applied_professions, vat, insurance_5_percent, social_insurance, commercial_profit_supply, commercial_profit_works, bank_transfer_fee, other_deductions, total_deductions. والحساب موجود في internal_supplier_details.php سطر 1428-1438 (calculatePayment) وفي supplier_details.php سطر 1307-1315.
- **الحالة في الجديد:** الـ gap حقيقي وأكدته بعد بحث عدائي شامل في النظام الجديد /Users/mohamed/Downloads/qarwana.

النظام القديم (esystem.sql سطر 3988-4021) جدول supplier_payments فيه 14 عمود تفصيلي للمستحقات والاستقطاعات: total_due, regular_payment, extra_payment, engineering_professions, arts_specialists, applied_professions, vat, insurance_5_percent, social_insurance, commercial_profit_supply, commercial_profit_works, bank_transfer_fee, other_deductions, total_deductions (+ employee_custody_deducted).

النظام الجديد:
1) database/migrations/2026_06_03_012002_create_supplier_payments_table.php فيه بس: amount, payment_date, payment_method, bank_account_id, reference_number, notes. صفر تفصيل استقطاعات.
2) app/Models/SupplierPayment.php نفس الأعمدة المحدودة (fillable سطر 16-19).
3) app/Http/Controllers/SupplierPaymentController.php الـ validateData (سطر 136-147) ما بيقبلش أي حقل خصم نهائياً.
4) database/migrations/2026_06_03_013001_create_contractor_extracts_table.php فيه deductions كرقم واحد مجمّع فقط، و ContractorExtractController.php سطر 110 بيحسب net_amount = total_amount - deductions بدون أي تفصيل (لا vat ولا تأمينات ولا أرباح تجارية).

حاولت أدحض الادعاء: لقيت جدول taxes عام (database/migrations/2026_06_03_011004_create_taxes_table.php + app/Models/Tax.php) بأنواع vat/income/withholding/stamp/other، لكنه ميزة مستقلة لتسجيل ضرائب عامة، مش مربوط بـ supplier_payments ولا contractor_extracts، وما بيغطيش فئات الاستقطاع التفصيلية (تأمين 5%، تأمينات اجتماعية، أرباح تجارية توريدات/أعمال، مهن هندسية/فنون/تطبيقية، دفعة عادية/إضافية، رسوم تحويل بنكي، إجمالي المستحق). مفيش جدول تفاصيل خصومات ولا pivot للدفعات/المستخلصات (في بس invoice_items وهو غير ذي صلة). الـ grep لأي من أسماء الأعمدة القديمة رجع صفر نتائج. الادعاء صحيح.
- **التوصية:** إضافة أعمدة الاستقطاعات التفصيلية إلى supplier_payments و/أو contractor_payments (ضريبة القيمة المضافة، تأمين 5% دفعة أعمال، شهادة تأمينات اجتماعية، أرباح تجارية توريدات/أعمال، مهن هندسية/تطبيقية/فنون، تحويل بنكي، استقطاعات أخرى) مع حقل total_deductions و net payable محسوب، وإعادة بناء منطق الحساب في الـController/الواجهة.

### 🟡 متوسط — غياب الرصيد الافتتاحي والرصيد الحالي وحد الائتمان وشروط السداد للموردين
- **النوع:** field
- **الدليل (القديم):** esystem.sql سطر 3920 جدول suppliers يحتوي على: opening_balance, current_balance, credit_limit, payment_terms. والرصيد الافتتاحي مُستخدم فعلياً في البيانات (مثال id=26 opening_balance=35150.00, current_balance=-70300.00) وفي suppliers_external.php سطر 622-631 (مدخلات الإضافة) وفي transaction_type='opening_balance' في supplier_payments سطر 4028.
- **الحالة في الجديد:** الادعاء صحيح بعد بحث عدائي شامل. جدول suppliers في الميجريشن /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_005002_create_suppliers_table.php لا يحتوي على opening_balance ولا current_balance ولا credit_limit ولا payment_terms (الأعمدة: name, company_name, type, phone, phone2, email, address, tax_number, commercial_register, notes, is_active, created_by فقط). موديل Supplier (/Users/mohamed/Downloads/qarwana/app/Models/Supplier.php) نفس الـfillable، والرصيد محسوب لحظياً عبر balanceDue() سطر 47-53 = أوامر الشراء المستلَمة (status=received) − المدفوعات، من غير أي حد افتتاحي. جدول supplier_payments (/Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_012002_create_supplier_payments_table.php) وموديله لا يحتويان على عمود transaction_type إطلاقاً، فآلية opening_balance القديمة مش موجودة. لا توجد ميجريشن لاحقة بتعدّل جدول suppliers لإضافة هذه الأعمدة. فورم الإضافة/التعديل suppliers/form.blade.php مفيهوش أي مدخلات للرصيد الافتتاحي أو حد الائتمان أو شروط السداد، وصفحة العرض suppliers/show.blade.php سطر 37 بتعرض الرصيد المستحق فقط. ملاحظة: أعمدة opening_balance/current_balance الموجودة في الكود بتخص جدول bank_accounts وهو غير متعلق بالموردين. التصنيف medium لأن الرصيد المستحق نفسه متاح (محسوب) لكن الرصيد الافتتاحي وحد الائتمان وشروط السداد غايبة فعلاً وممكن تأثر على دقة أرصدة الموردين المرحّلة من النظام القديم.
- **التوصية:** إضافة أعمدة opening_balance و credit_limit و payment_terms إلى جدول suppliers، وإدراج الرصيد الافتتاحي ضمن حساب balanceDue() (received + opening_balance − payments)، مع آلية تسجيل 'رصيد افتتاحي' عند إنشاء المورد كما في الـlegacy.

### 🟡 متوسط — غياب ربط دفعات الموردين/المقاولين بعهدة الموظف وخصمها تلقائياً
- **النوع:** integration
- **الدليل (القديم):** esystem.sql سطر 4002-4019 supplier_payments فيه employee_id و employee_custody_deducted و expense_id و material_transaction_id (تعليق الجدول: 'دفعات الموردين مع ربط بالمصروفات المخصومة من العهدة'). وفي supplier_details.php سطر 557-572 يوجد custodyBalanceInfo و checkCustodyBalance، وفي internal_supplier_details.php سطر 25 'جلب قائمة الموظفين النشطين مع العهدة'.
- **الحالة في الجديد:** الغياب حقيقي بعد بحث شامل. جدول supplier_payments الجديد (database/migrations/2026_06_03_012002_create_supplier_payments_table.php) يحتوي فقط على supplier_id, amount, payment_date, payment_method, bank_account_id, reference_number, notes, created_by — لا يوجد employee_id ولا employee_custody_deducted ولا expense_id ولا material_transaction_id. الموديل app/Models/SupplierPayment.php يطابق ذلك (relations: supplier, bankAccount, creator فقط). الكنترولر app/Http/Controllers/SupplierPaymentController.php عبر syncBankTransaction() يربط الدفعة بحركة بنكية فقط (BankLedgerService، related_type='supplier_payment') ولا يخصم من عهدة أي موظف. الفورم resources/views/supplier_payments/form.blade.php لا يحتوي على أي حقل موظف/عهدة — فقط "خصم من حساب بنكي". جدول expenses (2026_06_03_010334) أيضاً بدون employee_id أو ربط بالعهدة، وExpenseController/Expense.php لا يذكران custody/employee. الخدمة الوحيدة هي BankLedgerService (لا منطق عهدة فيها).

ملاحظة مهمة للسياق: مفهوم العهدة موجود فعلاً في النظام الجديد لكن بشكل منفصل ويدوي عبر EmployeeTransaction (type='custody' و 'custody_return' في 2026_06_03_014001) ودالة Employee::custodyBalance() (app/Models/Employee.php:45-49) التي تحسب الرصيد = العهدة − ردّها. فالموظف له رصيد عهدة، لكن لا يوجد أي آلية تلقائية تربط دفعة مورد/مقاول أو مصروف بهذه العهدة وتخصم منها — وهو بالضبط التكامل المُدّعى غيابه. لذا الـ stillMissing=true. خفّضت الخطورة إلى medium لأن البنية الأساسية للعهدة موجودة ويمكن الخصم منها يدوياً كـ workaround، لكن الربط التلقائي والحقول (employee_id/employee_custody_deducted/expense_id) غير موجودة.
- **التوصية:** إضافة دعم دفع المورد من عهدة موظف: عمود employee_id و employee_custody_deducted في supplier_payments، والتحقق من رصيد العهدة قبل الدفع وخصمه تلقائياً وإنشاء مصروف مرتبط (expense_id) — أو ربطها بنظام العُهد/المصروفات الموجود في النظام الجديد إن وُجد.

### 🟡 متوسط — غياب كشف حساب المورد بالرصيد التراكمي (balance_after) في الواجهة
- **النوع:** report
- **الدليل (القديم):** esystem.sql سطر 4000 supplier_payments فيه balance_after، وفي supplier_details.php سطر 498 يُعرض balance_after لكل حركة، مع أعمدة transaction_type متعددة (payment/debit/credit/refund/opening_balance) في كشف حساب متكامل.
- **الحالة في الجديد:** الـgap حقيقي ومؤكَّد بعد بحث عدائي كامل. في النظام الجديد /Users/mohamed/Downloads/qarwana:

1) resources/views/suppliers/show.blade.php بيعرض جدولين منفصلين تماماً (أوامر الشراء + المدفوعات)، كل واحد مرتّب latest() لوحده، من غير عمود رصيد تراكمي بعد كل حركة ومن غير دمج زمني في كشف حساب واحد.
2) SupplierController::show() بيعمل load منفصل لـpurchaseOrders وpayments بس — مفيش تجميع كشف حساب.
3) app/Models/SupplierPayment.php وmigration database/migrations/2026_06_03_012002_create_supplier_payments_table.php: مفيش balance_after ولا transaction_type نهائياً. grep على transaction_type في كل المشروع = صفر نتائج. الموجود بس payment_method (cash/bank_transfer/check)، مفيش تصنيف payment/debit/credit/refund/opening_balance.
4) Supplier::balanceDue() بيرجّع رقم صافي واحد فقط مش رصيد لكل حركة.
5) مفيش SupplierLedger ولا method statement للموردين. ReportController (app/Http/Controllers/ReportController.php) بيغطّي إيرادات/مصروفات/مشاريع بس — مفيش كشف حساب مورد.

نقطة مهمة (مكافئ جزئي): في فعلاً كشف حساب برصيد جارٍ بس للحسابات البنكية فقط — app/Services/BankLedgerService.php::statement() بيبني ledger زمني حتمي مع $running balance، ومعروض في resources/views/bank_accounts/show.blade.php. لكن ده قرار معماري مقصود: الكومنتات بتقول صراحةً إنهم بيتجنّبوا عمود balance_after مخزّن عمداً (كان بيتكسر مع ترتيب التواريخ). يعني الـpattern والبيانات (POs + payments) موجودين ويسمحوا ببناء كشف حساب مورد مشتق بدون تغيير schema، بس الميزة دي مش مطبّقة على الموردين في الواجهة. عشان كده خفّضت الخطورة لـmedium بدل high: الكشف التراكمي للمورد غايب فعلاً، لكن غياب أعمدة balance_after/transaction_type تصميم متعمّد مش سهو، والإضافة ممكنة بسهولة.
- **التوصية:** بناء كشف حساب موحّد للمورد يدمج التوريدات والدفعات زمنياً ويعرض رصيد تراكمي بعد كل حركة (مدين/دائن/رصيد افتتاحي)، إما عبر تخزين balance_after أو حسابه ديناميكياً في الـController.

### ⚪ بسيط — غياب حقل الهاتف الثاني وحقل client_id لربط المورد بعميل
- **النوع:** field
- **الدليل (القديم):** esystem.sql سطر 3923 client_id int(11) في جدول suppliers (لربط المورد الداخلي بعميل). ملاحظة: phone2 موجود في النظامين.
- **الحالة في الجديد:** تحققت بشكل عدائي والفجوة حقيقية بالنسبة لـ client_id فقط (phone2 موجود فعلاً ومش فجوة).

النظام القديم (esystem.sql سطر 3923): جدول suppliers فيه `client_id int(11) DEFAULT NULL` جنب `supplier_type enum('external','internal')` — كان بيربط المورد الداخلي بسجل عميل. وسجلات الـaudit log بتأكد المعنى: "إضافة عميل كمورد داخلي".

النظام الجديد في /Users/mohamed/Downloads/qarwana:
- migration الموردين /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_005002_create_suppliers_table.php: قريت الملف كامل (أعمدة 15-28)، فيه phone2 (سطر 20) ومفيش client_id خالص.
- Model /Users/mohamed/Downloads/qarwana/app/Models/Supplier.php: phone2 موجود في fillable (سطر 12)، بس مفيش client_id في fillable ولا علاقة client() — العلاقات بس creator / purchaseOrders / payments. فاضل مفهوم النوع internal/external في Supplier::TYPES لكن من غير ربط فعلي بعميل.
- /Users/mohamed/Downloads/qarwana/app/Http/Controllers/SupplierController.php: قواعد الـvalidation فيها type:in:external,internal و phone2 بس، مفيش client.
- مفيش أي عمود client في supplier_payments migration، ولا migration تانية بتعدّل suppliers، ولا pivot، ولا حقل client في views/suppliers، ولا علاقة supplier عكسية في Client model.
- ملاحظة: أول grep -l طلع false positive على ملفي الموردين، لكن grep -n الدقيق أثبت صفر تطابق لـ client_id فيهم. الـ client_id الحقيقي الوحيد في migrations بتاعة projects و invoices (مش له علاقة بالموردين).

الخلاصة: client_id غايب فعلاً من النظام الجديد. خفّضت الـseverity لـ low لأن phone2 (الجزء التاني من الادعاء) متوفّر، و client_id كان حقل ربط ثانوي اختياري (DEFAULT NULL) لحالة استخدام نادرة (المورد الداخلي = عميل)، مش حقل أساسي.
- **التوصية:** إضافة client_id (nullable foreign) إلى جدول suppliers إذا كان النظام يحتاج ربط مورد داخلي بعميل معيّن كما في الـlegacy؛ وإلا توثيق أنه أُسقط عمداً.

### ⚪ بسيط — نقص فلاتر الفئة (category) والطباعة في واجهة الموردين وعمليات الشراء
- **النوع:** ui
- **الدليل (القديم):** suppliers.php سطر 89-102 قائمة فئات (مواد بناء/حديد/أسمنت/رمل/زلط/طوب/بلاط/دهانات/كهربائيات/سباكة/نجارة/أخرى) وفلتر بالفئة سطر 179-189؛ و supplier_details.php فيه أزرار طباعة الإيصالات/الدفعات (printReceipt في الـJS).
- **الحالة في الجديد:** بعد بحث أمين في النظام الجديد (qarwana) الـ gap متأكد جزئياً وحقيقي إنه ناقص، لكن مع تصحيحات:

1) فلتر الفئة (category) للموردين — ناقص فعلاً ومؤكد:
- migration /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_005002_create_suppliers_table.php مفيهاش عمود category خالص.
- /Users/mohamed/Downloads/qarwana/app/Http/Controllers/SupplierController.php سطر 24-39: index بيفلتر بـ name/company_name/phone بس، مفيش أي فلتر بالفئة.
- /Users/mohamed/Downloads/qarwana/resources/views/suppliers/index.blade.php سطر 9-12: input بحث نصّي واحد فقط، مفيش dropdown للفئات.
- مفيش جدول حركات بفئاته (زي قائمة legacy: مواد بناء/حديد/أسمنت/رمل/زلط/طوب/بلاط/دهانات/كهربائيات/سباكة/نجارة) مربوط بالموردين.
- ملاحظة: مفهوم category موجود في النظام الجديد بس لموديلات تانية (Material::CATEGORIES، Expense، Asset) ومش متعلق بتصنيف الموردين، فمش بديل معقول للفلتر اللي في legacy.

2) الطباعة (كشف حساب/إيصال) — ناقصة فعلاً ومؤكد:
- /Users/mohamed/Downloads/qarwana/resources/views/suppliers/show.blade.php مفيهاش أي زر طباعة/كشف حساب/إيصال (بس تعديل + رجوع).
- مفيش window.print ولا @media print ولا printReceipt في أي view بالمشروع، ومفيش أي package PDF (dompdf/mpdf/snappy) في composer.json. كشف الحساب الوحيد موجود لـ bank_accounts بس ومش للموردين.

تصحيح للدليل في الـ legacy: ادعاء وجود printReceipt في supplier_details.php غير دقيق — الملف فيه viewPaymentHistory و modal سجل الدفعات فقط، مفيش دالة طباعة فعلية. قائمة الفئات والفلتر في suppliers.php سطر 89-102 و183-189 مؤكدين 100%.

السيفيرتي صُغّرت لـ low لأنها فجوة UI/راحة استخدام (فلتر اختياري + زر طباعة) مش وظيفة أساسية مفقودة؛ البيانات نفسها موجودة وبتتعرض، بس بدون فلترة بالفئة أو طباعة.
- **التوصية:** بعد إضافة supplier_transactions، إضافة فلتر بالفئة وفلتر بالمورد في شاشة العمليات، وإضافة طباعة كشف حساب المورد وإيصال الدفعة (print/PDF) كما في الـlegacy.

---

## Purchase Orders & Material Purchasing (أوامر الشراء وشراء المواد)

> النظام الجديد في Laravel أعاد بناء أوامر الشراء كـ CRUD سطحي على جدول واحد فقط، وهو ناقص بشكل جوهري مقارنة بالنظام القديم. أخطر نقص هو غياب بنود الأمر (purchase_order_items) تماماً: لا يوجد جدول ولا Model ولا migration لها، فأمر الشراء في النظام الجديد يحتوي فقط على "المبلغ الإجمالي" كرقم واحد يُكتب يدوياً بدون أي أصناف أو كميات أو أسعار وحدة. كذلك جدول purchase_orders الجديد يفتقد أعمدة الخصم والضريبة والصافي والمدفوع (discount/tax/net/paid_amount) وعمود add_to_inventory و approved_by و actual_delivery. الـworkflows الأساسية (الاعتماد approve، الاستلام receive، الاستلام الجزئي partial) غير موجودة، وحالة partial نفسها محذوفة من قائمة الحالات. لا يوجد تسجيل دفعات للمورد ولا ربط برصيد المورد، ولا أي تكامل مع المخزون أو material_purchases عند الاستلام، ولا توليد تلقائي لرقم الأمر، ولا إحصائيات. عملياً الموديول الحالي غير صالح للاستخدام الفعلي في شركة مقاولات لأنه لا يسجّل ماذا تم شراؤه فعلاً.

### 🟠 خطير — غياب بنود أمر الشراء بالكامل (purchase_order_items)
- **النوع:** module
- **الدليل (القديم):** esystem.sql سطر 3758 CREATE TABLE `purchase_order_items` بأعمدة (order_id, material_id, item_description, unit, quantity DECIMAL(15,3), unit_price DECIMAL(15,2), total_price, received_quantity, notes). وفي api/purchase_orders.php سطر 427-448 يتم إدراج كل صنف في purchase_order_items، وسطر 385-387 يحسب total_amount من مجموع (quantity*unit_price) لكل بند.
- **الحالة في الجديد:** الادعاء صحيح ومؤكد بعد بحث عدائي شامل. لا يوجد أي مكافئ لبنود أمر الشراء في النظام الجديد:

1) لا migration: /Users/mohamed/Downloads/qarwana/database/migrations/ فيه فقط 2026_06_03_012001_create_purchase_orders_table.php (جدول رأس واحد بعمود total_amount decimal(15,2) مكتوب يدوياً، السطر 22) ولا يوجد purchase_order_items. ملاحظة مهمة: الجدول المناظر للفواتير موجود فعلاً (2026_06_03_015002_create_invoice_items_table.php) مما يثبت أن النمط متبع للفواتير فقط وليس لأوامر الشراء.

2) لا Model: /Users/mohamed/Downloads/qarwana/app/Models/ يحتوي PurchaseOrder.php و InvoiceItem.php لكن لا يوجد PurchaseOrderItem.php. وموديل PurchaseOrder.php (السطور 18-21) قائمة fillable بدون أي بنود، وعلاقاته (السطور 32-45) فقط supplier/project/creator — لا توجد علاقة items().

3) لا Controller logic: /Users/mohamed/Downloads/qarwana/app/Http/Controllers/PurchaseOrderController.php الدالة validateData (السطور 94-109) لا تتحقق من أي بنود، و store/update (السطور 57-76) ينشئان/يحدّثان الرأس فقط بدون أي معالجة بنود أو حساب total من مجموع (quantity*unit_price).

4) لا View: /Users/mohamed/Downloads/qarwana/resources/views/purchase_orders/form.blade.php حقل total_amount يُكتب يدوياً (السطور 51-54)، ولا show.blade.php ولا index.blade.php يعرضان أي جدول بنود. (نتائج grep لكلمة item كانت فقط CSS class مثل align-items-center).

5) grep على كامل المستودع لـ purchase_order_item / PurchaseOrderItem / received_quantity / item_description لم يُرجع أي نتيجة. كل نتائج material_id و unit_price تخص modules أخرى (materials, inventory_movements, invoices). علاقة items() في DemoSeeder.php:154 تخص الفواتير ($inv->items()) لا أوامر الشراء.

الخلاصة: الميزة غائبة بالكامل. النظام الجديد يقلّص أمر الشراء إلى مبلغ إجمالي يدوي واحد، فاقداً تفصيل الأصناف وحقل received_quantity (المهم لتتبع الاستلام الجزئي) والحساب التلقائي للإجمالي الموجود في api/purchase_orders.php القديم.
- **التوصية:** إنشاء جدول وModel وعلاقة hasMany للبنود (purchase_order_items) بحقول material_id, item_description, unit, quantity, unit_price, total_price, received_quantity. وتعديل النموذج ليضيف صفوف أصناف ديناميكية، وحساب total_amount تلقائياً من مجموع البنود بدل إدخاله يدوياً، وحفظ البنود داخل transaction مع أمر الشراء.

### 🟠 خطير — غياب workflow الاعتماد والاستلام والاستلام الجزئي
- **النوع:** logic
- **الدليل (القديم):** api/purchase_orders.php سطر 104-340: action='approve' (سطر 126-127 يحدث status='approved' و approved_by و add_to_inventory)، action='receive' (سطر 157+ يحدث status ويعالج البنود)، وإلغاء action='cancel' (يحدث status='cancelled' لـ draft/pending/approved). وحالة partial معرّفة في purchase_orders.php سطر 88 و purchase_order_details.php سطر 68.
- **الحالة في الجديد:** تأكدت إن الـgap حقيقي بعد بحث جدي.

1) PurchaseOrderController.php (/Users/mohamed/Downloads/qarwana/app/Http/Controllers/PurchaseOrderController.php): CRUD قياسي بس (index/show/create/store/edit/update/destroy). مفيش أي action للاعتماد (approve) ولا الاستلام (receive) ولا cancel بـside-effects ولا add_to_inventory. الـstatus بيتحط من خلال حقل الفورم المvalidated بس (سطر 105).

2) routes/web.php سطر 77: Route::resource('purchase-orders'...) بس. مفيش routes مخصصة زي approve/receive.

3) Model PurchaseOrder.php سطور 10-16: STATUSES = draft/pending/approved/received/cancelled. حالة 'partial' مش موجودة فعلاً (الـgrep لكلمة partial طلعت في Invoice/ContractorExtract/Contractor بس مش في PurchaseOrder) — مفيش دعم استلام جزئي.

4) form.blade.php سطور 36-41: الحالة بتتغير يدوياً عبر dropdown زي ما الادعاء قال.

5) محاولة الدحض: فيه فعلاً موديول InventoryMovement (Controller + migration 016002) بس منفصل تماماً عن أوامر الشراء — مفيش purchase_order_id ولا import لـPurchaseOrder، والمخزون بيتعدل عبر حركات in/out يدوية مستقلة. مفيش أي ربط تلقائي "add_to_inventory عند الاعتماد/الاستلام".

6) مفيش أصلاً جدول/موديول purchase_order_details أو بنود لأمر الشراء، فالاستلام الجزئي لبنود مستحيل هيكلياً.

7) الإشارة الوحيدة الإضافية لـPurchaseOrder في AppServiceProvider سطر 17 هي تسجيل audit-log بس، مش logic workflow.

الخلاصة: workflow الاعتماد/الاستلام/الاستلام الجزئي مع approved_by والإضافة التلقائية للمخزون غايب فعلاً.
- **التوصية:** إضافة مسارات/أزرار approve و receive (مع خيار add_to_inventory) و cancel مع التحقق من الحالات المسموحة للانتقال، وإعادة حالة 'partial'، وتحديث received_quantity على البنود عند الاستلام.

### 🟠 خطير — غياب التكامل مع المخزون و material_purchases عند الاستلام
- **النوع:** integration
- **الدليل (القديم):** api/purchase_orders.php سطر 184-291: عند الاستلام مع add_to_inventory يحدّث materials.current_stock (سطر 184-185)، ويدرج في inventory_movements (سطر 192-201)، ويدرج في material_purchases (CREATE TABLE esystem.sql سطر 3338) بالحقول material_id, supplier_id, project_id, quantity, unit_price, total_price (سطر 209-221)، وقد ينشئ مادة جديدة (INSERT INTO materials سطر 228-240) ويربطها بالبند.
- **الحالة في الجديد:** تأكدت إن الفجوة حقيقية بعد بحث عدائي كامل. لا يوجد جدول/Model باسم material_purchases في النظام الجديد إطلاقاً: grep -rni "material_purchase" على app/ و database/ و resources/ و routes/ رجع صفر نتائج. ملف migration الخاص بأوامر الشراء /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_012001_create_purchase_orders_table.php أعمدته (order_number, supplier_id, project_id, order_date, expected_delivery, status, total_amount, notes, created_by) ولا يحوي عمود add_to_inventory، ولا يوجد جدول بنود (purchase_order_items) أصلاً (الموجود فقط invoice_items). الـController /Users/mohamed/Downloads/qarwana/app/Http/Controllers/PurchaseOrderController.php عمليات store/update/destroy فيه مجرد PurchaseOrder::create/update/delete بدون DB::transaction، ولا يلمس materials.current_stock، ولا يدرج في inventory_movements، ولا ينشئ مادة جديدة؛ وحالة "received" مجرد خيار في القائمة بدون أي side-effect (تظهر فقط كـ badge في show.blade.php و sum في Supplier model). أقرب شيء وجدته هو ميزة InventoryMovement اليدوية المنفصلة في /Users/mohamed/Downloads/qarwana/app/Http/Controllers/InventoryMovementController.php (تحدّث current_stock عبر bcadd/bcsub) لكنها غير مرتبطة بأوامر الشراء نهائياً (grep أكد عدم وجود أي ذكر لـ PurchaseOrder فيها)، كما أن migration الخاص بـ inventory_movements (2026_06_03_016002) لا يحوي purchase_order_id ولا supplier_id ولا unit_price/total_price، فلا يمكن أن يمثّل بيانات material_purchases القديمة ولا يربطها بأمر الشراء. إذن لا يوجد مكافئ معقول للتكامل التلقائي عند الاستلام.
- **التوصية:** بناء جدول material_purchases وربط استلام أمر الشراء بزيادة رصيد المادة وتسجيل حركة مخزون (inventory_movements) وسجل شراء، مع إمكانية إنشاء مادة جديدة من البند تلقائياً كما في القديم.

### 🟠 خطير — غياب صفحة تفاصيل أمر الشراء الكاملة وطباعتها
- **النوع:** ui
- **الدليل (القديم):** purchase_order_details.php (1237 سطر) يعرض جدول البنود مع الكميات والمستلم منها (سطر 840 received_quantity)، وملخص الخصم/الضريبة/الصافي (سطر 851-865)، وسجل الدفعات، ونسبة السداد (سطر 952-954)، واسم المعتمِد approved_by_name (سطر 751-754).
- **الحالة في الجديد:** الادعاء صحيح بعد بحث معمّق في النظام الجديد. صفحة العرض /Users/mohamed/Downloads/qarwana/resources/views/purchase_orders/show.blade.php عبارة عن 35 سطر فعلاً وتعرض الحقول الأساسية فقط (رقم الأمر، المورّد، المشروع، المبلغ الإجمالي، الحالة، التواريخ، أنشأ بواسطة، ملاحظات).

التفاصيل الناقصة بتأكيد بنيوي مش بس واجهة:
1) لا توجد بنود: مفيش موديل ولا migration باسم purchase_order_items في /Users/mohamed/Downloads/qarwana/database/migrations أو /app/Models (فيه invoice_items فقط)، ولا أي ذكر لـ received_quantity في الكود. فالجدول بالكميات والمستلم منها غير موجود أساساً.
2) لا يوجد ملخص مالي خصم/ضريبة/صافي: migration الـ purchase_orders (2026_06_03_012001) فيه total_amount فقط — مفيش أعمدة discount/tax/net.
3) لا يوجد سجل دفعات للأمر ولا نسبة سداد: supplier_payments (موديل /app/Models/SupplierPayment.php و migration 012002) مرتبطة بـ supplier_id فقط، ومفيش purchase_order_id يربط الدفعة بأمر شراء محدد، فلا يمكن عرض دفعات الأمر أو نسبة السداد.
4) لا يوجد اسم معتمِد: مفيش عمود approved_by ولا علاقة approver في الموديل — فقط created_by (المُنشئ).
5) لا يوجد زر/مسار/واجهة طباعة: لا print ولا window.print في views/purchase_orders، ولا أي route طباعة في routes/web.php (الـ resource عبارة عن index/create/store/edit/update/destroy/show قياسية).

الكنترولر /app/Http/Controllers/PurchaseOrderController.php دالة show بتعمل load لـ supplier/project/creator فقط ولا تمرر بنود ولا دفعات. الخلاصة: الميزة غائبة فعلاً مقابل purchase_order_details.php القديم (1237 سطر). صنّفتها high وليست critical لأنها صفحة عرض/طباعة وليست منطق مالي يفسد البيانات، رغم إنها فجوة كبيرة في القيمة الوظيفية.
- **التوصية:** توسعة صفحة التفاصيل لعرض جدول البنود والمستلم منها والملخص المالي وسجل الدفعات واسم المعتمِد مع زر طباعة بعد إضافة البنى الناقصة.

### 🟡 متوسط — غياب أعمدة الخصم والضريبة والصافي والمدفوع في جدول أوامر الشراء
- **النوع:** field
- **الدليل (القديم):** esystem.sql سطر 3683-3705 جدول purchase_orders يحتوي discount_percentage, discount_amount, tax_percentage, tax_amount, net_amount, paid_amount, actual_delivery, add_to_inventory, approved_by. وملف setup_purchase_percentage.php يضيف discount_percentage و tax_percentage. وapi/purchase_orders.php سطر 389-394 يحسب الصافي: after_discount = total - discount_amount ثم net_amount = after_discount + tax_amount.
- **الحالة في الجديد:** الـgap مؤكد بعد بحث عدائي شامل. جدول purchase_orders في النظام الجديد لا يحتوي على أي من: discount_percentage, discount_amount, tax_percentage, tax_amount, net_amount, paid_amount, actual_delivery, add_to_inventory, approved_by.

الأدلة من النظام الجديد:
1) المايجريشن الوحيد /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_012001_create_purchase_orders_table.php يعرّف فقط: order_number, supplier_id, project_id, order_date, expected_delivery, status, total_amount, notes, created_by, timestamps. ولا يوجد أي مايجريشن آخر يعمل ALTER على جدول purchase_orders (grep على database/migrations لم يرجع غير هذا الملف).
2) الموديل /Users/mohamed/Downloads/qarwana/app/Models/PurchaseOrder.php السطر 18-21 الـfillable يطابق نفس الأعمدة الناقصة فقط، والـcasts لا تذكر أي خصم/ضريبة/صافي/مدفوع.
3) الكنترولر /Users/mohamed/Downloads/qarwana/app/Http/Controllers/PurchaseOrderController.php دالة validateData السطر 96-108 تتحقق فقط من order_number, supplier_id, project_id, order_date, expected_delivery, status, total_amount, notes. لا توجد أي معالجة لحساب net = total - discount + tax كما في الـlegacy api/purchase_orders.php، ولا منطق approved_by أو add_to_inventory.
4) الـviews: form.blade.php به فقط حقل total_amount واحد بدون خصم/ضريبة/صافي/مدفوع. وفي index.blade.php و show.blade.php الكلمتان الوحيدتان المطابقتان لـ"approved" هما تلوين شارة الحالة (قيمة status='approved') وليس عمود approved_by.
5) المطابقات لـ tax_amount/paid_amount/net_amount في الـgrep العام تخص جداول أخرى غير مرتبطة: invoices (2026_06_03_015001_create_invoices_table.php) و contractor_extracts، وليست جدول أوامر الشراء.

الخلاصة: لا يوجد مكافئ معقول تحت أي اسم أو هيكل بديل. الميزة غائبة فعلاً.

تعديل الخطورة إلى medium: أمر الشراء في النظام الجديد يحفظ total_amount مباشرة (إدخال يدوي) بدل اشتقاقه من خصم/ضريبة، فالقيمة الإجمالية لا تضيع لكن يُفقد: تفصيل الخصم والضريبة، تتبع المدفوع، تاريخ التسليم الفعلي، اعتماد الأمر (approved_by)، وإضافة للمخزون (add_to_inventory).
- **التوصية:** إضافة الأعمدة المفقودة في migration وModel، وتطبيق منطق حساب الخصم والضريبة والصافي تلقائياً، وعرض تفاصيل الصافي/المدفوع/المتبقي في صفحة التفاصيل (موجودة في القديم purchase_order_details.php سطر 851-865 و940-954).

### 🟡 متوسط — غياب التوليد التلقائي لرقم أمر الشراء
- **النوع:** logic
- **الدليل (القديم):** api/purchase_orders.php سطر 373-378: يولّد order_number بصيغة PO-{year}- متبوعاً برقم تسلسلي مصفوف بـ str_pad 4 خانات اعتماداً على MAX الرقم الحالي (PO-2026-0001 كما في بيانات esystem.sql سطر 3712).
- **الحالة في الجديد:** تم التأكد من الفجوة بعد بحث شامل. النظام الجديد لا يولّد رقم أمر الشراء تلقائياً.

الأدلة:
- resources/views/purchase_orders/form.blade.php السطر 14-15: حقل order_number عبارة عن input نصي required يكتبه المستخدم يدوياً، ولا يوجد قيمة مقترحة.
- app/Http/Controllers/PurchaseOrderController.php السطر 96-100: دالة validateData تتحقق فقط من required/string/max:50/Rule::unique دون أي توليد. ودالة create (50-55) وstore (57-64) تنشئ السجل من البيانات المُدخلة فقط.
- app/Models/PurchaseOrder.php: لا يوجد boot()/booted() ولا أي معالج لحدث creating لملء order_number تلقائياً.
- app/Providers/AppServiceProvider.php السطر 33-38: الـobservers المسجّلة هي created/updated/deleted لتسجيل النشاط فقط، ولا يوجد creating hook للترقيم.
- لا يوجد FormRequest أو helper أو service يولّد الرقم في أي مكان بالكود.

الظهور الوحيد لصيغة PO-{year}-{0000} مع str_pad هو في database/seeders/DemoSeeder.php السطر 135، وهذا لبيانات تجريبية (seeding) فقط وليس توليداً وقت التشغيل عبر MAX كما في النظام القديم (api/purchase_orders.php سطر 373-378).

النتيجة: الميزة غائبة فعلاً. الخطورة medium (وليست high) لأن قيد unique على العمود يمنع التكرار الفعلي في قاعدة البيانات، لكنه يعرّض المستخدم لأخطاء إدخال يدوية وتجربة استخدام أسوأ من النظام القديم.
- **التوصية:** توليد order_number تلقائياً بصيغة PO-YYYY-#### عند الإنشاء، مع السماح بتجاوزه إن لزم.

### ⚪ بسيط — غياب لوحة الإحصائيات وملخصات أوامر الشراء
- **النوع:** report
- **الدليل (القديم):** purchase_orders.php سطر 74-82: استعلام إحصائي يحسب SUM(net_amount) كإجمالي، SUM(paid_amount) كمدفوع، وعدد pending وapproved، ويعرضها كبطاقات أعلى الصفحة.
- **الحالة في الجديد:** تم البحث بعمق في النظام الجديد والادعاء صحيح: لوحة الإحصائيات/بطاقات الملخص لأوامر الشراء غير موجودة.

الأدلة:
- /Users/mohamed/Downloads/qarwana/app/Http/Controllers/PurchaseOrderController.php (index سطر 27-41): يبني قائمة مفلترة ومقسّمة لصفحات فقط (بحث بـ order_number + فلتر status). لا يوجد أي SUM أو count أو تجميع حسب الحالة.
- /Users/mohamed/Downloads/qarwana/resources/views/purchase_orders/index.blade.php: نموذج بحث/فلترة + جدول فقط، بدون أي بطاقات (إجمالي/مدفوع/متبقٍّ/عدد).
- /Users/mohamed/Downloads/qarwana/app/Models/PurchaseOrder.php والـmigration: الجدول فيه total_amount فقط، ولا يوجد paid_amount ولا net_amount إطلاقًا، فحتى بيانات الـSUM القديمة غير متتبَّعة.
- /Users/mohamed/Downloads/qarwana/app/Http/Controllers/DashboardController.php: يجمّع Revenue/Expense/Project/Invoice/Contractor/Bank... ولا يلمس PurchaseOrder نهائيًا (غير مستورد أصلًا).
- /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ReportController.php: يغطي Revenue/Expense/Project فقط، بدون ملخص لأوامر الشراء.
- السطح الوحيد الآخر لأوامر الشراء هو /Users/mohamed/Downloads/qarwana/resources/views/suppliers/show.blade.php: يعرض قائمة أوامر المورّد مع badge count فقط، وليس بطاقات ملخص على مستوى الصفحة.

الخطورة صُحّحت إلى low لأنها ميزة عرضية إحصائية (UX) لا تؤثر على سلامة البيانات أو العمليات الأساسية.
- **التوصية:** إضافة بطاقات إحصائية أعلى صفحة القائمة (إجمالي القيم، إجمالي المدفوع، المتبقي، عدد الأوامر حسب الحالة).

---

## المقاولون والمستخلصات وبنود المستخلصات (Contractors, Extracts & Extract Items)

> النظام الجديد أعاد بناء الهيكل الأساسي فقط (مقاول + مستخلص برأس واحد + دفعة بسيطة)، لكنه ناقص بشكل جوهري مقارنة بالنظام القديم. أخطر نقص هو غياب جدول بنود المستخلص contractor_extract_items بالكامل (لا migration ولا Model ولا واجهة)، فالمستخلص الجديد رقم إجمالي واحد بدون تفصيل بنود/كميات/أسعار/نسبة تنفيذ. كذلك غاب سجل معاملات المقاول contractor_transactions (سلفة/خصم/استرداد) واستبدل بدفعة مسطّحة لا تفرّق الأنواع ولا تربط بعهدة الموظف ولا تحدّث حالة المستخلص تلقائيًا (paid/partial). أعمدة مالية مهمة في المستخلص (additions, discount_percent, execution_percent, paid_amount, approved_by/approved_at, attachment) محذوفة، وحقول المقاول (project_id, address, city, bank_name, bank_account, commercial_register) محذوفة. صفحة كشف حساب المقاول التفصيلية وتقرير المقاولين بالتصدير/الطباعة غير موجودين إطلاقًا. التغطية الحالية تقديريًا حوالي 35-40% من وظائف الدومين القديم.

### 🟠 خطير — غياب جدول بنود المستخلص contractor_extract_items بالكامل
- **النوع:** module
- **الدليل (القديم):** جدول `contractor_extract_items` في esystem.sql (CREATE TABLE سطر 2097-2110) بأعمدة: item_description (البيان), unit (الوحدة), quantity, unit_price, discount_percent, execution_percent (نسبة التنفيذ), discount_amount, total_price. مُنشأ في update_contractor_extract_items.php (سطر 16-31) ومملوء ببيانات فعلية (INSERT سطر 2116+). الـ API يدرج/يقرأ البنود ويحسب total_price = quantity × unit_price × (execution_percent/100) − discount في api/contractor_extracts.php سطر 220-290، والواجهة contractor_details.php فيها getExtractItems() (سطر 1042) وحساب الإجمالي من البنود.
- **الحالة في الجديد:** الـ gap حقيقي ومؤكد. مفيش جدول بنود للمستخلص في النظام الجديد خالص. بحثت في: app, database/migrations, routes, resources. grep على extract_item / ExtractItem / item / quantity / unit_price / execution_percent / البيان / بند / نسبة التنفيذ رجع فاضي.

الأدلة:
1. migration /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_013001_create_contractor_extracts_table.php فيه أعمدة الرأس بس: extract_number, contractor_id, project_id, extract_date, description, total_amount, deductions, net_amount, status, notes, created_by. مفيش عمود JSON للبنود ولا أي حقل quantity/unit/unit_price/execution_percent/discount_percent. مفيش migration لجدول بنود (الموجود بس contractor_extracts + contractor_payments).
2. Model /Users/mohamed/Downloads/qarwana/app/Models/ContractorExtract.php علاقاته contractor/project/creator فقط. مفيش items() relation ولا JSON cast للبنود.
3. Controller /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ContractorExtractController.php سطر 95-113 (validateData) بيتحقق من total_amount كرقم واحد فقط ويحسب net_amount = total_amount - deductions. مفيش معالجة لأي array بنود ولا حساب لكل سطر.
4. الواجهات: form.blade.php سطر 41 فيه input واحد يدوي لـ total_amount بدون أي صفوف/repeater للبنود. show.blade.php سطر 22-33 بيعرض أرقام الرأس فقط بدون جدول بنود.
5. جدول invoice_items موجود لكنه خاص بالفواتير فقط (مفيش فيه أي مرجع contractor/extract).

النتيجة: منطق البنود بالكامل (item_description, unit, quantity, unit_price, execution_percent, discount, total_price = quantity × unit_price × execution_percent/100 − discount) المفقود ومتبدل بـ total_amount واحد يُدخل يدوياً. خسارة لتفصيل البنود ومنطق نسبة التنفيذ والحساب الآلي للسطور. الخطورة high (مش critical) لأنه ضعف داخل موديول مستخلصات موجود مش حذف الموديول كله.
- **التوصية:** إنشاء جدول وModel ContractorExtractItem (extract_id, item_description, unit, quantity, unit_price, discount_percent, execution_percent, discount_amount, total_price, notes) مع علاقة hasMany من ContractorExtract، وإضافة إدخال البنود في form.blade.php، وحساب total_amount/net_amount من مجموع البنود على السيرفر داخل DB::transaction. هذا أهم نقص لأنه أساس مستخلص المقاول في شركات المقاولات (BOQ).

### 🟠 خطير — غياب سجل معاملات المقاول (سلفة/خصم/استرداد) وربطه بعهدة الموظف
- **النوع:** logic
- **الدليل (القديم):** جدول `contractor_transactions` (esystem.sql سطر 2214-2232) بأنواع enum('payment','advance','deduction','refund') وحقول check_number, bank_name, reference_number, attachment، وأهمها employee_id + employee_custody_deducted. في api/contractor_transactions.php: عند payment/advance يُخصم المبلغ من عهدة الموظف عبر deduct_from_employee_custody (سطر 107-126) وعند الحذف يُرجَّع عبر refund_to_employee_custody (سطر 312-328).
- **الحالة في الجديد:** الادّعاء صحيح بعد بحث عدائي كامل. الجديد فيه ContractorPayment دفعة مسطّحة فقط:
- migration: /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_013002_create_contractor_payments_table.php — حقول amount, payment_date, payment_method(cash/bank_transfer/check), bank_account_id, reference_number, extract_id, notes. لا يوجد transaction_type ولا check_number ولا bank_name ولا employee_id ولا employee_custody_deducted.
- Model: /Users/mohamed/Downloads/qarwana/app/Models/ContractorPayment.php — PAYMENT_METHODS فقط (نقدي/تحويل/شيك)، لا أنواع معاملة (سلفة/خصم/استرداد).
- Controller: /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ContractorPaymentController.php — syncBankTransaction (سطر 101-118) يربط بحساب بنكي فقط (withdrawal)، ولا أي مساس بعهدة الموظف. الحذف (سطر 87-95) يحذف الحركة البنكية فقط.
- Form: /Users/mohamed/Downloads/qarwana/resources/views/contractor_payments/form.blade.php — لا حقل نوع معاملة ولا موظف ولا شيك/بنك.
- Contractor model: علاقات extracts و payments فقط، لا transactions.

بخصوص العهدة: يوجد جدول employee_transactions (migration 2026_06_03_014001) و EmployeeTransaction.php بأنواع advance(سلفة)/deduction(خصم)/custody(عهدة)/custody_return(رد عهدة)، لكنها مرتبطة بالموظف فقط (employee_id) ولا علاقة لها بالمقاول إطلاقًا. لا يوجد أي ربط بين دفعة المقاول وعهدة الموظف.

نتائج grep لـ check_number/employee_custody/transaction_type في سياق المقاول = فارغة تمامًا. كل تطابقات bank_name تخص جدول bank_accounts (اسم البنك نفسه) لا معاملات المقاول. فالخاصية الأهم في القديم (الخصم/الاسترداد التلقائي من عهدة الموظف عند دفعة/سلفة المقاول، وأنواع enum للمعاملة، وحقول الشيك) غير موجودة في النظام الجديد.
- **التوصية:** إضافة عمود transaction_type (payment/advance/deduction/refund) وحقول check_number/bank_name و employee_id + employee_custody_deducted إلى دفعات/معاملات المقاول، وإعادة منطق خصم/إرجاع عهدة الموظف عند الإنشاء/الحذف. هذا حرج لأن الدفع للمقاولين كثيرًا ما يتم من عهدة موظف ولازم يخصم من رصيد العهدة.

### 🟠 خطير — الدفعة لا تحدّث المبلغ المدفوع وحالة المستخلص تلقائيًا (paid/partial)
- **النوع:** logic
- **الدليل (القديم):** في api/contractor_transactions.php سطر 173-221: عند تسجيل دفعة مرتبطة بمستخلص يُحدَّث paid_amount ثم تُحسب الحالة تلقائيًا (paid لو paid>=net، partial لو paid>0)، وعند الحذف يُعكس التأثير (سطر 330-360). جدول contractor_extracts فيه عمود paid_amount (esystem.sql سطر 2015) والبيانات تُظهر حالات partial/paid فعلية.
- **الحالة في الجديد:** الـ gap حقيقي بعد بحث معمّق. (1) migration /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_013001_create_contractor_extracts_table.php مفيهوش عمود paid_amount خالص (الأعمدة: total_amount/deductions/net_amount/status). نتائج paid_amount اللي ظهرت بتخص جداول تانية (invoices, supplier_payments, contractors, contractor_payments). (2) Model /Users/mohamed/Downloads/qarwana/app/Models/ContractorExtract.php سطر 18-21 الـ fillable من غير paid_amount، ومفيش علاقة payments() ولا accessor ولا boot/observer. (3) /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ContractorPaymentController.php::store (57-68) بيعمل create للدفعة + syncBankTransaction بس، عمره ما بيلمس المستخلص ولا حالته، و destroy (87-95) بيعكس الحركة البنكية بس. (4) مفيش أي Observers/Listeners/EventServiceProvider ولا hooks (static::created/saved...) في app/ كله؛ الخدمة الوحيدة BankLedgerService مالهاش علاقة بالمستخلص. (5) الحالة بتتحدد يدوي بس من dropdown <select name=status> في /Users/mohamed/Downloads/qarwana/resources/views/contractor_extracts/form.blade.php سطر 53، والتحقق في ContractorExtractController::validateData سطر 105 بيقبلها كاختيار حر. سلوك الـ legacy (تحديث paid_amount تلقائي + حساب paid/partial + عكسه عند الحذف) مالوش أي مقابل في النظام الجديد.
- **التوصية:** إضافة عمود paid_amount للمستخلص، وعند إنشاء/تعديل/حذف دفعة مرتبطة بمستخلص يُعاد حساب paid_amount والحالة تلقائيًا (paid/partial/approved) داخل DB::transaction. حاليًا الموظف لازم يغيّر الحالة يدويًا وهو عرضة للخطأ والتلاعب.

### 🟠 خطير — أعمدة مالية محذوفة من المستخلص: additions, discount_percent, execution_percent, attachment
- **النوع:** field
- **الدليل (القديم):** جدول contractor_extracts (esystem.sql سطر 2010-2018): deductions, discount_percent (نسبة الخصم الإجمالية), execution_percent (نسبة التنفيذ), additions (الإضافات), net_amount, paid_amount, attachment. صافي المستخلص = total − deductions + additions (api/contractor_extracts.php سطر 124).
- **الحالة في الجديد:** الـ gap حقيقي ومؤكد بعد بحث عدائي كامل في النظام الجديد. الأعمدة الأربعة (additions, discount_percent, execution_percent, attachment) غير موجودة لا باسمها ولا بأي مكافئ:

1) Migration: /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_013001_create_contractor_extracts_table.php — الأعمدة المالية الوحيدة هي total_amount, deductions, net_amount فقط (السطور 21-23). مفيش migration تانية بتعمل alter وتضيف الأعمدة دي (الموجود بس 3 migrations: contractors + contractor_extracts + contractor_payments).

2) Model: /Users/mohamed/Downloads/qarwana/app/Models/ContractorExtract.php — الـ fillable (السطر 18-21) والـ casts (24-30) فيهم total_amount, deductions, net_amount بس.

3) Controller: /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ContractorExtractController.php سطر 110: net_amount = total_amount - deductions فقط، من غير additions ولا نسبة تنفيذ ولا نسبة خصم. الـ validateData (97-107) مبيقبلش أي من الحقول دي.

4) Form view: /Users/mohamed/Downloads/qarwana/resources/views/contractor_extracts/form.blade.php — مفيهاش inputs لـ additions/discount/execution percent ولا حقل رفع ملف (attachment).

5) grep على additions/discount_percent/execution_percent في app+database+resources رجع فاضي تماماً، ومفيش أي معالجة attachment للمستخلصات في أي مكان (ولا في routes/web.php).

ملاحظة على الـ severity: ده regression في منطق مالي فعلي (معادلة الصافي اتغيرت من total - deductions + additions إلى total - deductions، وضاعت نسب التنفيذ/الخصم وإمكانية إرفاق مستند)، فالـ high مناسبة.
- **التوصية:** إضافة الأعمدة additions و discount_percent و execution_percent و attachment للمستخلص وتعديل حساب الصافي ليصبح total − deductions + additions، ودعم مرفق المستخلص (صورة/PDF).

### 🟠 خطير — منع حذف المستخلص المرتبط بدفعات غير منفّذ + غياب منطق رقم المستخلص الفريد لكل مقاول
- **النوع:** logic
- **الدليل (القديم):** api/contractor_extracts.php سطر 414-419: يمنع حذف المستخلص لو له معاملات مالية مرتبطة ('لا يمكن حذف المستخلص لوجود معاملات...'). وسطر 111-116 يمنع تكرار extract_number لنفس المقاول.
- **الحالة في الجديد:** الـgap مؤكد وموجود فعلاً بعد بحث عدواني شامل في النظام الجديد. (1) منع الحذف غايب تماماً: app/Http/Controllers/ContractorExtractController.php سطر 79-84 الدالة destroy بتعمل $contractorExtract->delete() مباشرة من غير أي فحص. الموديل app/Models/ContractorExtract.php مفيهوش boot()/booted() ولا deleting hook ولا حتى علاقة payments(). AppServiceProvider.php بيسجل deleted (بعد الحذف للـaudit) مش deleting. مفيش مجلد Policies ولا Http/Requests. وأسوأ من كده: migration database/migrations/2026_06_03_013002_create_contractor_payments_table.php سطر 18 بيخلي extract_id يستخدم nullOnDelete() — يعني حذف المستخلص بيصفّر الربط في الدفعات بدل ما يمنعه، عكس الـlegacy (system (2)/api/contractor_extracts.php سطر 414-419 اللي بيمنع الحذف لو فيه contractor_transactions مرتبطة). (2) رقم المستخلص الفريد لكل مقاول غايب: validateData سطر 98 بيتحقق من extract_number كـ string/max:50 بس من غير قاعدة unique ولا تركيب مع contractor_id؛ migration create_contractor_extracts_table سطر 16 بيعرّف string('extract_number',50) من غير أي unique index — لا بسيط ولا مركّب. الـlegacy سطر 111-116 بيرفض التكرار لنفس المقاول. أرقام السطور في الـlegacy متطابقة في النسخة system (2)/api/contractor_extracts.php. خفّضت الخطورة من critical لـ high لأن الدفعات بتتيتم (orphan) مش بتتحذف، فمش فقدان بيانات كارثي لكن خلل في سلامة البيانات والتقارير المالية.
- **التوصية:** منع حذف المستخلص لو له دفعات مرتبطة (أو ضبط onDelete مناسب)، وإضافة تحقق فرادة (extract_number + contractor_id) عند الإنشاء/التعديل.

### 🟡 متوسط — صفحة كشف حساب المقاول التفصيلية مفقودة
- **النوع:** report
- **الدليل (القديم):** contractor_details.php (78KB) صفحة كشف حساب كاملة: بطاقات إحصائية (إجمالي المستخلصات، المدفوع، الخصومات، نسبة الخصم سطر 65-83)، تبويب المستخلصات بالحالات الملونة (سطر 481-496)، تبويب المعاملات المالية بأنواعها (سطر 524-587)، وأزرار إضافة مستخلص/دفعة من نفس الصفحة.
- **الحالة في الجديد:** الـ gap حقيقي بعد بحث عدائي كامل. النظام الجديد مفيهوش صفحة كشف حساب مقاول تفصيلية معادلة لـ contractor_details.php.

الموجود فعلاً:
- /Users/mohamed/Downloads/qarwana/resources/views/contractors/show.blade.php: بيانات المقاول + آخر 10 مستخلصات (رقم/تاريخ/صافي/حالة) + آخر 10 دفعات + رصيد مستحق واحد balanceDue() سطر 35. بدون بطاقات إحصائية، بدون تبويبات، بدون نسبة خصم/تنفيذ، بدون ترقيم أو فلترة، بدون أزرار إضافة من نفس الصفحة (بس تعديل/رجوع).
- /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ContractorController.php (show سطر 43-52): بيعمل load لـ creator/extracts/payments بس، مفيش أي حسابات إجمالية (withSum/withCount غير موجودة).
- /Users/mohamed/Downloads/qarwana/app/Models/Contractor.php: balanceDue() سطر 42-50 = صافي المستخلصات المعتمدة − الدفعات، رقم واحد فقط.

اللي اتأكدت إنه مش موجود تحت أي مسمى:
- ReportController (/Users/mohamed/Downloads/qarwana/app/Http/Controllers/ReportController.php) بيغطي إيرادات/مصروفات/مشاريع بس، صفر منطق مقاولين. مجلد reports فيه index.blade.php واحد بدون أي ذكر للمقاول.
- في statement() لكن في BankLedgerService للحسابات البنكية فقط مش للمقاولين.
- routes/web.php: بس Route::resource للـ contractors/contractor-extracts/contractor-payments — مفيش route كشف حساب مخصص لكل مقاول.

البطاقات الإحصائية (إجمالي المستخلصات/المدفوع/الخصومات/نسبة الخصم)، تبويبات الحالات الملونة وتبويب المعاملات بأنواعها، وأزرار الإضافة من نفس الصفحة، والترقيم/الفلترة — كلها غايبة. صفحة الـ show ملخص جزئي مش كشف حساب تفصيلي. خفّضت الخطورة لـ medium لأن نفس البيانات متاحة موزّعة (show + قوائم contractor-extracts/contractor-payments العامة) فالقدرة الوظيفية الأساسية موجودة لكن مجمّعة في صفحة كشف حساب واحدة لأ.
- **التوصية:** بناء صفحة كشف حساب مقاول كاملة (statement) فيها ملخص إحصائي (إجمالي المستحق، المدفوع، المتبقي، الخصومات)، وجدول حركة زمني موحّد للمستخلصات والدفعات، مع طباعة الكشف.

### 🟡 متوسط — تقرير المقاولين (Contractors Report) بالتصدير والطباعة مفقود
- **النوع:** report
- **الدليل (القديم):** contractors_report.php (34KB): تقرير ملخص + تفصيل لكل مقاول (gross_total, total_deductions, total_additions, total_net, total_paid سطر 86-112) + تقرير مجمّع حسب المشروع (سطر 143-159)، فلترة بالمشروع وبالمقاول (سطر 27-59)، وأزرار 'تصدير Excel' (exportToExcel سطر 340) و'طباعة التقرير' (window.print سطر 343). كذلك view `costs_by_contractor` (esystem.sql سطر 5112) لتجميع تكاليف المشروع حسب المقاول/المورد.
- **الحالة في الجديد:** الثغرة مؤكدة. دوّرت في كل النظام الجديد (qarwana) ومفيش تقرير مقاولين بالتصدير/الطباعة.

أدلة البحث:
1) فيه ReportController واحد بس: /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ReportController.php — بيطلّع تقرير مالي عام فقط (إجمالي إيرادات/مصروفات/صافي + المصروفات حسب الفئة + ربحية المشاريع) وفلترة بالتاريخ (from/to) بس. مفيش أي تجميع حسب المقاول، ولا أعمدة gross_total/total_deductions/total_additions/total_net/total_paid، ولا فلترة بالمقاول أو المشروع.
2) الـ route الوحيد: /Users/mohamed/Downloads/qarwana/routes/web.php سطر 116: Route::get('reports', [ReportController::class,'index'])->name('reports.index'). موجة المقاولين (سطر 80-82) فيها resource للمقاولين والمستخلصات والدفعات فقط، بدون أي report.
3) View التقارير الوحيد: /Users/mohamed/Downloads/qarwana/resources/views/reports/index.blade.php — مفيش زر تصدير Excel ولا window.print ولا تقرير مقاول.
4) grep على costs_by_contractor / groupBy contractor / total_paid / total_net / gross_total في app+resources+database رجع فارغ (Exit code 1) — مفيش الـ view الخاص بتجميع التكاليف حسب المقاول.
5) composer.json مافيهوش أي مكتبة تصدير (maatwebsite/excel، dompdf، barryvdh، phpspreadsheet، snappy، mpdf). كلمة export/excel/window.print مالهاش أي ذكر يخص التقارير (بس امتدادات رفع الملفات في ProjectFileController).
6) /Users/mohamed/Downloads/qarwana/resources/views/contractor_extracts/index.blade.php فعلاً فلترة بسيطة بس (search برقم المستخلص + status).

أقرب شيء موجود: صفحة عرض المقاول /Users/mohamed/Downloads/qarwana/resources/views/contractors/show.blade.php بتعرض آخر 10 مستخلصات/دفعات + balanceDue للمقاول الواحد — لكن ده عرض سجلات فردي، مش تقرير مجمّع/قابل للتصدير أو الطباعة، فمش بديل مكافئ للـ contractors_report.php القديم.

التصنيف medium مش high: الداتا الأساسية (إجماليات/خصومات/صافي المستخلصات + balanceDue) موجودة في النظام والـ ReportController، فإعادة بناء التقرير دي إضافة شاشة + تجميع + أزرار تصدير/طباعة، مش نقص في الداتا نفسها.
- **التوصية:** إنشاء ContractorReportController يولّد تقرير المقاولين مجمّعًا (حسب المقاول وحسب المشروع) مع فلترة بالمشروع/المقاول/الفترة، وإضافة تصدير Excel وطباعة، وربطه بتكاليف المشروع (costs_by_contractor) لمعرفة مصروفات كل مقاول.

### 🟡 متوسط — اعتماد المستخلص (approved_by / approved_at) وحالة 'معتمد' غير منفّذة فعليًا
- **النوع:** logic
- **الدليل (القديم):** جدول contractor_extracts فيه approved_by و approved_at (esystem.sql سطر 2020-2021) لتتبّع من اعتمد المستخلص ومتى، والحالة enum فيها 'approved'.
- **الحالة في الجديد:** الـclaim صحيح. بعد بحث جاد: migration الجديد /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_013001_create_contractor_extracts_table.php سطر 24 فيه فقط status default('pending') وسطر 26 created_by — لا يوجد approved_by ولا approved_at إطلاقًا (grep على كل ملفات app رجع فارغ). الموديل /Users/mohamed/Downloads/qarwana/app/Models/ContractorExtract.php عنده STATUSES فيها 'approved' => 'معتمد' (سطر 12) وعلاقة creator() بس، مفيش approver. الكنترولر /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ContractorExtractController.php مفيهوش أي approve() action؛ الحالة بتتعيّن من validateData (سطر 105) كـ dropdown حر، والـform.blade.php سطر 51-58 بيختار الحالة يدويًا بدون workflow أو تسجيل من اعتمد/متى. show.blade.php مبيعرضش أي معلومة عن المعتمِد. وجدت آلية تدقيق عامة ActivityLog (مسجّلة في AppServiceProvider سطر 17 وContractorExtract ضمن AUDITED)، لكنها مكافئ ضعيف جدًا: بتسجّل action='updated' مع user_id وcreated_at فقط (app/Models/ActivityLog.php سطر 43-51) من غير ما تحفظ أي حقل اتغيّر ولا القيمة الجديدة، فمش ممكن تعرف منها إن ده كان اعتماد ولا مجرد تعديل مبلغ/ملاحظات، ولا بتخزّن المعتمِد على السجل نفسه. خفّضت الخطورة لـ medium لأن أثر تدقيق جزئي موجود (مين عدّل ومتى) رغم غياب workflow الاعتماد الفعلي وحقول approved_by/approved_at على الجدول.
- **التوصية:** إضافة approved_by و approved_at مع action اعتماد مخصّص (وصلاحية contractors.approve) يسجّل المُعتمِد والوقت بدل تغيير الحالة يدويًا، لضبط دورة اعتماد المستخلصات.

### ⚪ بسيط — حقول بيانات المقاول محذوفة: project_id, address, city, bank_name, bank_account, commercial_register
- **النوع:** field
- **الدليل (القديم):** جدول contractors (esystem.sql سطر 1917-1939): project_id (ربط المقاول بمشروع — مضاف في setup_contractor_project.php)، address, city, bank_name, bank_account, commercial_register، بجانب الحقول الموجودة. والبيانات تُظهر project_id=41 لكل المقاولين.
- **الحالة في الجديد:** الحقول الستة فعلاً غايبة عن كيان المقاول في النظام الجديد بعد بحث شامل:
- Migration: /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_005001_create_contractors_table.php — مفيهاش أي من (project_id, address, city, bank_name, bank_account, commercial_register).
- Model: /Users/mohamed/Downloads/qarwana/app/Models/Contractor.php (fillable سطر 11-14) — مفيهاش الحقول دي ولا علاقة project().
- Controller: /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ContractorController.php — مفيش validation للحقول دي.
- Form: /Users/mohamed/Downloads/qarwana/resources/views/contractors/form.blade.php — مفيش inputs ليها.
- Show: /Users/mohamed/Downloads/qarwana/resources/views/contractors/show.blade.php — مبتعرضش أي منها.
- مفيش migration لاحقة بتعمل Schema::table('contractors') لإضافتها.

ملاحظة مهمة (تخفيف الخطورة): الأسماء دي موجودة على كيانات تانية مش المقاول — address/city/commercial_register على Client وSupplier (مثلاً /Users/mohamed/Downloads/qarwana/app/Models/Client.php سطر 13)، bank_name على BankAccount، وأهم نقطة: project_id موجود على ContractorExtract (/Users/mohamed/Downloads/qarwana/app/Models/ContractorExtract.php سطر 19 + migration 2026_06_03_013001 سطر 18) فربط المقاول بالمشروع متحقق بشكل غير مباشر عبر المستخلصات (تصميم أكثر تطبيعاً).

والأهم: بيانات الـ legacy (esystem.sql سطر 1945+) بتأكد إن الحقول دي كانت شبه فاضية: address وcity '' في كل الصفوف، commercial_register كله NULL، project_id ثابت=41 لكل المقاولين (قيمة افتراضية واحدة)، bank_name/bank_account غالباً فاضية أو placeholder '6'. عشان كده الفجوة حقيقية لكن تأثيرها منخفض (data-completeness مش فقدان وظيفي).
- **التوصية:** إضافة الحقول الناقصة لجدول المقاولين خصوصًا project_id (لربط المقاول بمشروع وهو مستخدم في التقارير) والبيانات البنكية (bank_name/bank_account) المطلوبة للتحويلات والسجل التجاري.

---

## تكاليف المشاريع / بنود الأعمال (BOQ) / إسناد الموظفين والمواد للمشاريع (Project Costs, Work Items/BOQ, Project Assignments)

> النظام الجديد (qarwana) شبه فاضي في الدومين ده مقارنة بالقديم. النظام القديم فيه موديول كامل لتكاليف المشاريع التفصيلية (جدول project_costs بـ 11 حقل بيانات + واجهة project_costs.php + 5 ملفات API بما فيها استيراد Excel وتصدير) مبني على فكرة تقسيم التكلفة حسب بند الأعمال (work_item / BOQ) وحسب المقاول/المورد (contractor_supplier)، مع 3 Views للتجميع (costs_by_work_item, costs_by_contractor, project_costs_summary) وجدول سجل استيراد (cost_import_logs)، بالإضافة لجداول إسناد الموظفين للمشاريع (project_employees) والمواد المستهلكة في المشاريع (project_materials). النظام الجديد مفيهوش جدول project_costs ولا أي مفهوم لـ work_item/BOQ خالص (الـ grep رجّع بس بنود الفواتير وهي دومين تاني)، ومفيش pivot لإسناد الموظفين للمشاريع (موديل Employee مالوش أي ربط بالمشروع أصلاً)، ومفيش جدول استهلاك مواد بكمية وتاريخ لكل مشروع (موديل Material عنده project_id واحد بس يعني المادة بتتبع مشروع واحد فقط مش استهلاك متكرر). تقرير المشروع الجديد (ReportController) بيجمّع الإيرادات والمصروفات بس، من غير أي تفصيل بنود أو مقاولين. ده فقدان جوهري لقدرة الشركة على تتبع التكلفة الفعلية مقابل بنود الأعمال ومقارنتها بقيمة العقد.

### 🟠 خطير — موديول تكاليف المشاريع التفصيلية (project_costs) مفقود بالكامل
- **النوع:** module
- **الدليل (القديم):** esystem.sql سطر 3589 CREATE TABLE `project_costs` بحقول amount/description/cost_date/work_item/contractor_supplier/payment_method/payment_type/receipt_check_number/bank_name/asset_category/page_number/created_by؛ الواجهة project_costs.php و project_costs_backup.php؛ والـ API: api/project_costs.php (CRUD)، api/project_costs_details.php، debug_costs.php.
- **الحالة في الجديد:** الـ gap حقيقي ومؤكد بعد بحث عدائي شامل. لا يوجد جدول project_costs ولا موديل ProjectCost ولا Controller ولا Route ولا View ولا أي سترينج "تكاليف" في كل شجرة qarwana (ما عدا vendor). تفاصيل البحث:
- migrations: القائمة الكاملة في /Users/mohamed/Downloads/qarwana/database/migrations لا تحتوي project_costs.
- models: /Users/mohamed/Downloads/qarwana/app/Models لا يوجد ProjectCost.
- controllers: /Users/mohamed/Downloads/qarwana/app/Http/Controllers لا يوجد ProjectCostController.
- routes/web.php: مفيش route للتكاليف، الموجود بس Route::resource('expenses', ...).
- grep على project_cost / cost_date / work_item / contractor_supplier / receipt_check / asset_category / page_number / payment_type / "تكاليف" رجّع صفر نتائج.

أقرب موديول موجود هو Expenses (app/Models/Expense.php + migration 2026_06_03_010334_create_expenses_table.php + view resources/views/expenses + route expenses). بيتقاطع جزئياً مع project_costs في: project_id, amount, description, expense_date, payment_method, bank_account_id, created_by. لكنه مش بديل كامل — ناقص الحقول المميزة لـ project_costs: work_item (بند الأعمال)، contractor_supplier (المقاول/المورد)، payment_type، receipt_check_number (رقم الإيصال/الشيك)، bank_name، asset_category (تبويب الأصول)، page_number؛ كمان ناقص الـ view project_costs_summary. وExpenses بيستخدم category enum ثابت بدل سجل تكاليف تفصيلي حر النص.

السبب في تخفيض الشدة من critical لـ high: في تداخل وظيفي جزئي عبر Expenses يسمح بتسجيل صرف المشروع الأساسي، فالقدرة مش صفر تماماً — لكن موديول التكاليف التفصيلي بحقوله السبعة المميزة وملخص project_costs_summary غايب فعلاً.
- **التوصية:** إنشاء جدول project_costs وموديل ProjectCost و ProjectCostController بنفس حقول النظام القديم (المبلغ، التاريخ، البيان، بند الأعمال، المقاول/المورد، طريقة الدفع، رقم الإيصال/الشيك، البنك، تبويب الأصول) مع ربطه بالمشروع، عشان الشركة تقدر تسجّل التكاليف الفعلية بند ببند.

### 🟡 متوسط — تتبع التكلفة حسب بند الأعمال (work_item / BOQ) مش موجود
- **النوع:** logic
- **الدليل (القديم):** العمود project_costs.work_item (esystem.sql سطر ~3596، 'بند الأعمال') + الـ View costs_by_work_item (esystem.sql سطر 5121: GROUP BY project_id, work_item مع item_count و total_amount) + فلتر workItemFilter في project_costs_backup.php.
- **الحالة في الجديد:** الـ gap مؤكد فعلاً بعد بحث شامل في النظام الجديد. مفيش جدول project_costs أصلاً؛ التكاليف بتتسجل في جدول expenses (database/migrations/2026_06_03_010334_create_expenses_table.php). أعمدته: project_id, category, description, amount, expense_date, payment_method, bank_account_id, notes. الـ category مجرد enum ثابت بـ 7 قيم فقط (materials/labor/equipment/transportation/utilities/administrative/other) في app/Models/Expense.php — مش حقل حر لـ بند الأعمال زي legacy work_item. مفيش أي عمود work_item ولا boq في أي migration/model. الـ grep على work_item / costs_by_work / بند رجّع بس invoice_items (وصف + كمية + سعر وحدة مربوط بفاتورة، مش بتتبع تكلفة مشروع) في app/Http/Controllers/InvoiceItemController.php و resources/views/invoices/show.blade.php. الـ ReportController الوحيد (app/Http/Controllers/ReportController.php سطر 37-40) بيجمّع المصروفات بـ groupBy('category') فقط + ملخص لكل مشروع (إيراد - مصروف)، مفيش أي تجميع حسب بند الأعمال يقابل الـ View القديم costs_by_work_item (GROUP BY project_id, work_item). contractor_extracts كمان مفيهاش breakdown ببنود — total_amount/net_amount بس. مفيش route ولا فلتر workItemFilter. التصنيف الصحيح medium مش high: فيه بديل جزئي (تجميع بالـ category الثابت) بيغطي تقرير تكلفة مبدئي لكن بدقة أخشن بكتير من تتبع البنود الحر.
- **التوصية:** إضافة حقل بند الأعمال (work_item) لجدول التكاليف وبناء تقرير تجميعي للتكاليف لكل بند أعمال داخل المشروع (إجمالي المبلغ وعدد السجلات لكل بند) — ده جوهر شغل شركة المقاولات لمقارنة التكلفة الفعلية بالمقايسة.

### 🟡 متوسط — تجميع التكاليف حسب المقاول/المورد (costs_by_contractor) مفقود
- **النوع:** report
- **الدليل (القديم):** العمود project_costs.contractor_supplier + الـ View costs_by_contractor (esystem.sql سطر 5112: GROUP BY project_id, contractor_supplier مع transaction_count و total_amount)، وكمان contractors_summary في api/project_costs_details.php سطر 230.
- **الحالة في الجديد:** بعد بحث عدائي كامل في النظام الجديد، التأكيد إن الـ feature مش موجود فعلاً. التفاصيل:

1) جدول المصروفات الجديد expenses (database/migrations/2026_06_03_010334_create_expenses_table.php) مفيهوش أي عمود contractor_supplier ولا contractor_id ولا supplier_id — بس project_id, category, description, amount, payment_method, bank_account_id. يعني مفيش بُعد مقاول/مورد على المصروف أصلاً عشان نجمّع عليه. ده تأكيد على غياب نظير العمود project_costs.contractor_supplier القديم.

2) التقرير الوحيد الموجود: route واحد بس reports.index (routes/web.php سطر 108) -> ReportController::index (app/Http/Controllers/ReportController.php). الـ controller بيعمل sum للإيرادات/المصروفات، وتجميع واحد فقط ->groupBy('category') للمصروفات حسب الفئة، وملخّص لكل مشروع بـ withSum (rev/exp) على مستوى المشروع. مفيش أي تجميع حسب المقاول/المورد. الـ view (resources/views/reports/index.blade.php) بيلفّ على byCategory و projects بس.

3) كل استخدامات groupBy في التطبيق كله محصورة في: category (ReportController سطر 39 + DashboardController سطر 47)، status (DashboardController سطر 51)، والشهر Y-m (DashboardController سطر 83). مفيش ولا groupBy('contractor_id') ولا groupBy('supplier_id') في أي مكان.

4) أقرب حاجة موجودة لكنها مش نظير حقيقي: صفحات الـ show للمقاول والمورد بتعرض تفاصيل كيان واحد بس (ContractorController::show بيحمّل extracts+payments، SupplierController::show بيحمّل purchaseOrders+payments)، ودوال Contractor::balanceDue() و Supplier::balanceDue() بتحسب رصيد مستحق لكيان واحد. ده per-entity detail/balance مش تقرير مجمّع لكل التكاليف موزّعة حسب المقاول/المورد (transaction_count + total_amount لكل مقاول/مورد زي الـ View القديم costs_by_contractor و contractors_summary).

5) دوّرت على الأسماء الحرفية القديمة (costs_by_contractor, contractor_supplier, contractors_summary, by_contractor) في كل الـ .php و .blade.php — صفر نتائج.

الخلاصة: التجميع المجمّع للتكاليف حسب المقاول/المورد (تقرير) غير موجود. خفّضت الـ severity لـ medium لأن البيانات الأساسية (extracts/payments/purchase orders مربوطة بـ contractor_id/supplier_id) موجودة وممكن يتبني منها التقرير بسهولة، بالإضافة لوجود أرصدة per-entity كبديل جزئي للوظيفة.
- **التوصية:** بناء تقرير يجمّع تكاليف كل مشروع حسب المقاول/المورد (إجمالي ما تم صرفه لكل مقاول وعدد المعاملات) عشان متابعة المستحقات والصرف لكل طرف.

### 🟡 متوسط — ملخص تكاليف المشروع (project_costs_summary) وتقرير التكلفة الموحّد مش موجود
- **النوع:** report
- **الدليل (القديم):** الـ View project_costs_summary (esystem.sql سطر 5130: لكل مشروع total_items/total_cost/first_cost_date/last_cost_date) + شاشة الملخص (Summary Cards) في project_costs.php + تقرير costs.php اللي بيدمج project_costs مع المصروفات والمستخلصات ورواتب الموظفين لحساب إجمالي التكلفة والربح لكل مشروع (سطر 205 total_all_costs).
- **الحالة في الجديد:** دوّرت كويس في النظام الجديد (/Users/mohamed/Downloads/qarwana) والـ gap حقيقي ومؤكّد، مع تحفظ بسيط.

1) مفيش أي أثر لـ project_costs نهائيًا: grep لـ "project_costs|cost_date|ProjectCost|costs.php" رجع صفر نتائج (غير الـ vendor). مفيش migration ولا Model ولا Controller ولا View اسمه project_costs. يعني سجل التكاليف التفصيلي + الـ View project_costs_summary (total_items/total_cost/first_cost_date/last_cost_date) ملهمش أي مقابل.

2) التقرير الوحيد الموجود هو ReportController::index (/Users/mohamed/Downloads/qarwana/app/Http/Controllers/ReportController.php سطر 42-53) + الـ View الوحيد /Users/mohamed/Downloads/qarwana/resources/views/reports/index.blade.php، وله route واحد بس في routes/web.php سطر 108. جدول ربحية المشاريع بيحسب rev − exp فقط (withSum للإيرادات ناقص withSum للمصروفات). مفيش دمج لمستخلصات المقاولين (paid_amount) ولا رواتب الموظفين المخصّصة للمشروع، يعني حساب total_all_costs/net_profit الموحّد الموجود في legacy costs.php (سطر 205: expenses + contractor_extracts + project_costs + supplier + employee_salaries) غير موجود.

3) مفيش Summary Cards بعدد البنود/أول وآخر تاريخ تكلفة؛ البطاقات بتعرض بس إجمالي إيرادات/مصروفات/صافي.

التحفظ (سبب تخفيض الخطورة لـ medium مش high): البنية التحتية للبيانات موجودة جزئيًا — employee_transactions فيه project_id (migration 2026_06_03_014001 سطر 20) و contractor_extracts فيه project_id كمان — فأصل ربط الرواتب والمستخلصات بالمشروع موجود، لكن مفيش أي تقرير بيستهلكها لحساب تكلفة/ربح موحّد لكل مشروع. وكمان فيه تقرير ربحية فعلي (إيراد − مصروف لكل مشروع) فهو تقرير ناقص مش غايب بالكامل. الخلاصة: الـ project_costs ledger + مقاييس الـ summary view + الدمج الموحّد للتكاليف فعلاً ناقصين.
- **التوصية:** بناء تقرير ربحية مشروع موحّد يدمج كل مصادر التكلفة (تكاليف تفصيلية + مصروفات + مستخلصات مقاولين + رواتب موظفين المشروع + مشتريات الموردين) ويقارنها بقيمة العقد، مع بطاقات ملخص (إجمالي التكلفة/عدد البنود/أول وآخر تكلفة).

### 🟡 متوسط — إسناد الموظفين للمشاريع (project_employees) مفقود بالكامل
- **النوع:** module
- **الدليل (القديم):** esystem.sql سطر 3629 CREATE TABLE `project_employees` (project_id, employee_id, assigned_date, end_date, role_in_project, notes) مع FK constraints (سطر 5378) project_employees_ibfk_1/ibfk_2.
- **الحالة في الجديد:** بعد بحث عدائي شامل في النظام الجديد (qarwana) أكدت إن feature إسناد الموظفين للمشاريع مفقود فعلاً.

ما تم فحصه:
1. /Users/mohamed/Downloads/qarwana/app/Models/Employee.php — العلاقات الموجودة بس: creator() (belongsTo User) و transactions() (hasMany EmployeeTransaction). مفيش أي علاقة بالمشروع ولا belongsToMany.
2. /Users/mohamed/Downloads/qarwana/app/Models/Project.php — العلاقات: client, manager (belongsTo User مش Employee), creator, revenues, expenses. مفيش employees() ولا belongsToMany.
3. مجلد database/migrations/ كامل (31 migration) — مفيش أي migration باسم project_employees ولا pivot. الجدول الوحيد اللي فيه employee_id هو employee_transactions.
4. /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_014001_create_employee_transactions_table.php — فيه employee_id + project_id (nullable, nullOnDelete) لكن ده للمعاملة المالية بس مش للإسناد، ومفيش فيه أي من حقول الإسناد (assigned_date, end_date, role_in_project).
5. grep على project_employee / belongsToMany / withPivot / pivot / assign / إسناد / role_in_project / assigned_date في app و database و routes و resources — صفر نتائج فعلية.
6. routes/web.php — في resources لـ projects و employees و employee-transactions بس. مفيش أي route للإسناد.
7. الربط الوحيد بين project و employee في الكود هو EmployeeTransactionController بيعمل ->with(['employee','project']) لعرض المعاملة المالية.

النتيجة مطابقة تماماً لوصف الـ claim. الـ pivot table بحقوله الستة (project_id, employee_id, assigned_date, end_date, role_in_project, notes) مش موجود بأي شكل أو اسم بديل.

صححت الـ severity لـ medium بدل اعتبارها critical: ده feature تتبّع/إداري (تخصيص موظف لمشروع بدور وتواريخ) مش مكوّن مالي أساسي، والربط المالي بين الموظف والمشروع متغطّي جزئياً عبر employee_transactions.project_id، فالأثر التشغيلي محدود مش حرج.
- **التوصية:** إنشاء pivot project_employee (project_id, employee_id, assigned_date, end_date, role_in_project, notes) وعلاقة belongsToMany في موديلَي Project و Employee، عشان نعرف مين الفريق العامل على كل مشروع ودوره وفترة عمله.

### 🟡 متوسط — فقدان حقول مهمة في عقود المشاريع (project_contracts)
- **النوع:** field
- **الدليل (القديم):** esystem.sql سطر 3557 جدول project_contracts فيه: currency، payment_terms (شروط الدفع)، advance_payment_percent (نسبة الدفعة المقدمة)، retention_percent (نسبة المحتجز/الضمان)، contract_file (ملف العقد PDF)، terms_conditions (الشروط والأحكام).
- **الحالة في الجديد:** الـ gap حقيقي ومؤكد بعد بحث كامل في كل طبقات النظام الجديد. حاولت أدوّر على أي equivalent لكن مفيش خالص.

1) الـ legacy (esystem.sql سطور 3557+) جدول project_contracts فعلاً فيه الحقول دي بالكومنتات: currency varchar(10) DEFAULT 'EGP' (سطر 3569)، payment_terms text 'شروط الدفع' (3570)، advance_payment_percent decimal(5,2) 'نسبة الدفعة المقدمة' (3571)، retention_percent decimal(5,2) 'نسبة المحتجز/الضمان' (3572)، contract_file varchar(255) 'ملف العقد PDF' (3573)، terms_conditions text 'الشروط والأحكام' (3576).

2) في النظام الجديد، المigration الوحيد للجدول /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_011003_create_project_contracts_table.php مفيهوش ولا حقل من الستة. الموجود بس: project_id, contract_number, contract_type, title, first_party, second_party, signing_date, start_date, end_date, contract_value, status, description, notes, created_by. ومفيش أي ALTER migration تاني بيضيف الأعمدة دي (grep على project_contracts رجّع نفس الملف بس).

3) الموديل /Users/mohamed/Downloads/qarwana/app/Models/ProjectContract.php الـ fillable والـ casts مفيهومش أي من الحقول دي.

4) الكنترولر /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ProjectContractController.php دالة validateData() (سطور 91-108) بتعمل validate لـ 13 حقل بس، ولا واحد منهم currency/payment_terms/advance_payment_percent/retention_percent/contract_file/terms_conditions.

5) الفورم /Users/mohamed/Downloads/qarwana/resources/views/contracts/form.blade.php مفيهوش أي input أو select أو file upload للحقول دي.

grep على كل المشروع (مع استثناء vendor) للأسماء دي مرتبط بـ project_contracts رجّع صفر. (الـ currency/payment_terms اللي ظهروا في bank_accounts و suppliers حاجة تانية مالهاش علاقة بالعقود).

السبب إني خفّضت الـ severity من high لـ medium: دي حقول معلومات تكميلية (شروط دفع/نسب/ملف PDF/شروط وأحكام) مش حقول core بتكسر علاقات أو حسابات؛ غيابها بيقلّل قيمة موديول العقود لكن مش بيوقّف النظام. خصوصاً advance_payment_percent و retention_percent ممكن يكون ليهم تأثير مالي لو فيه مستخلصات مرتبطة، فلو ثبت إن فيه منطق محاسبي معتمد عليهم ممكن ترجع high.
- **التوصية:** إضافة الأعمدة الناقصة لعقود المشاريع خاصة نسبة الدفعة المقدمة ونسبة المحتجز (retention) وشروط الدفع وملف العقد PDF — دي حقول أساسية لإدارة عقود المقاولات وحساب المستخلصات.

### 🟡 متوسط — فلاتر وبحث وترقيم صفحات شاشة التكاليف مفقودة
- **النوع:** ui
- **الدليل (القديم):** project_costs.php وproject_costs_backup.php فيهم فلتر بالمشروع + من/إلى تاريخ + بحث ببند الأعمال (workItemFilter) + بحث بالمقاول/المورد (contractorFilter) + ترقيم صفحات (pagination) + إجمالي في تذييل الجدول.
- **الحالة في الجديد:** الكلام في الـclaim دقيق جزئياً ومحتاج تصحيح بسيط. مفيش شاشة "تكاليف المشاريع" (project_costs) متطابقة في النظام الجديد، لكن في شاشتين قريبتين بتغطّيا جزء من الوظيفة - مش كلها.

اللي موجود فعلاً في النظام الجديد:
1) شاشة مصروفات Expenses: /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ExpenseController.php + /Users/mohamed/Downloads/qarwana/resources/views/expenses/index.blade.php. عندها: ربط بمشروع (project_id)، pagination (paginate(15)->withQueryString، والـlinks في الـview)، وإجمالي ($total = Expense::...->sum('amount') معروض فوق الجدول). لكن الفلتر الوحيد هو فلتر "الفئة/category" (مش فلتر بالمشروع من القائمة، ولا بحث بالنص). جدول المصروفات (migration: /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_010334_create_expenses_table.php) عمدته: project_id, category, description, amount, expense_date, payment_method, bank_account_id, notes — مفيش فيه work_item ولا contractor_supplier.

2) شاشة تقارير Reports: /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ReportController.php + /Users/mohamed/Downloads/qarwana/resources/views/reports/index.blade.php. دي فيها فلتر من/إلى تاريخ (from/to) وملخص مصروفات لكل مشروع + تقسيم حسب الفئة. فدي بتغطّي "فلتر التاريخ" و"الإجمالي لكل مشروع" بس على مستوى تقرير تجميعي، مش جدول بنود تفصيلي قابل للتعديل.

اللي مفقود فعلاً (اتأكدت منه بـgrep على كل app+resources+database):
- مفيش فلتر اختيار مشروع من قائمة منسدلة جوّا شاشة بنود التكاليف (الفلترة بالمشروع في النظام الجديد على مستوى التقرير بس).
- مفيش فلتر/بحث بـ"بند الأعمال" (work_item) — الكلمة دي مش موجودة خالص في النظام الجديد.
- مفيش فلتر/بحث بـ"المقاول/المورد" (contractor_supplier) على بنود التكلفة — العمود ده مش موجود في جدول expenses.
- مفيش فلتر من/إلى تاريخ داخل شاشة المصروفات نفسها (موجود في التقارير بس).

ملاحظة مهمة عن الـseverity: القيمة "بند الأعمال" و"المقاول/المورد" في النظام القديم كانت أعمدة text حرّة (input نصي)، مش جداول مرجعية، فهي بيانات تفصيلية مش feature معقّد. وفي بديل جزئي شغّال (Expenses + Reports) بيدّي نفس الإجماليات والربط بالمشروع وفلتر التاريخ على مستوى التقرير. عشان كده صحّحت الـseverity لـmedium مش critical/high: الفكرة الأساسية (تتبّع تكاليف لكل مشروع بإجمالي وفلترة) موجودة، اللي ناقص هو الـUI المخصّص لبنود التكلفة بفلاتر work_item/contractor + الفلترة بالمشروع/التاريخ جوّا نفس الشاشة + الـExcel import (project_costs_import) المرتبط بيها.
- **التوصية:** عند بناء شاشة التكاليف، توفير فلاتر بالتاريخ وبند الأعمال والمقاول/المورد مع إجمالي ظاهر وترقيم صفحات وتصدير، مثل النظام القديم.

### ⚪ بسيط — استيراد وتصدير تكاليف Excel + سجل الاستيراد (cost_import_logs) مفقود
- **النوع:** integration
- **الدليل (القديم):** api/project_costs_import.php (تحليل واستيراد ملف Excel على خطوات preview/import)، api/costs_export.php (تصدير)، وجدول cost_import_logs في esystem.sql (سطر بعد 2390: filename/total_rows/imported_rows/failed_rows/status/error_log) لتدقيق عمليات الاستيراد.
- **الحالة في الجديد:** بعد بحث معمّق، الـgap مؤكد إنه مفقود. مفيش أي مكتبة Excel/CSV في /Users/mohamed/Downloads/qarwana/composer.json (الـrequire فيه بس php, laravel/framework, laravel/tinker, spatie/laravel-permission) ولا في composer.lock ولا vendor/ (مفيش maatwebsite ولا phpoffice/phpspreadsheet ولا openspout ولا league/csv). مفيش أي Excel::import / Excel::download / fgetcsv / str_getcsv / IOFactory / SimpleXLSX في كل app. الملف الوحيد اللي ظهر في الـgrep هو app/Http/Controllers/ProjectFileController.php، ولمّا قريته اتأكد إنه بيخزّن الملف بس على القرص ($file->store('project_files','local')) ويرجّعه للتنزيل — مابيحللش محتوى ولا بيستورد صفوف لأي جدول تكاليف؛ امتدادات xls/xlsx موجودة فقط في whitelist الرفع كـblob. مفيش migration لجدول cost_import_logs ولا أي جدول costs أصلاً في كامل قايمة الـmigrations (الموجود activity_logs لتدقيق عام للأفعال، مش batch استيراد Excel بأعمدة filename/total_rows/imported_rows/failed_rows/status/error_log). grep على cost/تكلف/import_log/cost_import في app/database/resources/routes رجّع صفر. ExpenseController وReportController فيهم CRUD/تجميعات عادية بس من غير export/import. مفيش routes للاستيراد/التصدير/preview. ملحوظة: الـseverity صحّحتها لـlow لأن الـcore data CRUD موجود (Expense/Revenue/...) والمفقود هو وسيلة استيراد جماعي من Excel وسجل تدقيقها، وده feature إنتاجية/تشغيلية مش بلوكر للوظيفة الأساسية.
- **التوصية:** إضافة استيراد تكاليف من Excel (مع شاشة معاينة قبل التأكيد وتسجيل عدد الصفوف الناجحة/الفاشلة في جدول سجل استيراد) وتصدير التكاليف، لأن إدخال مئات بنود التكلفة يدوياً غير عملي.

---

## الشركاء والإيداعات وتوزيع الأرباح (Partners, deposits & profit distribution)

> النظام الجديد أعاد بناء جزء بسيط جداً من دومين الشركاء: عنده فقط جدولين (partners و partner_transactions) مقابل أربعة جداول في النظام القديم (partners, partner_deposits, partner_profit_schedule, partner_transactions). الموجود حالياً مجرد CRUD لبيانات الشريك + قائمة حركات يدوية (إيداع/سحب/أرباح/تسوية) بدون أي منطق محاسبي. كل قلب الدومين ناقص: مفيش جدول إيداعات رأس مال (partner_deposits) بنسبة ربح ومدة ودورية صرف، مفيش حساب تلقائي للربح المتوقع، مفيش جدولة أرباح (partner_profit_schedule)، مفيش صرف أرباح مرتبط بالجدول، مفيش عملية تصفية (settlement) تحسب المستحقات وتقفل الحساب، مفيش ربط بالحسابات البنكية، ومفيش كشف حساب شريك قابل للطباعة/التصدير. كمان الحقول المهمة في الحركات (balance_after, payment_method, الحساب البنكي, المسلِّم/المستلِم, رقم الشيك, deposit_id, فترة الربح) كلها مش موجودة. باختصار: السؤال الأساسي إجابته «لأ» — النظام الجديد لا يوزّع أرباح ولا ينتج كشف حساب شريك. ملاحظة دقيقة: النظام القديم لا يوزّع الأرباح بنسبة مساهمة من ربح الشركة (share %) بل بنسبة ربح سنوية ثابتة لكل إيداع (profit_rate) شبيهة بفائدة الاستثمار؛ وده اللي لازم يتبني.

### 🟠 خطير — جدول إيداعات رأس المال (partner_deposits) مفقود بالكامل
- **النوع:** module
- **الدليل (القديم):** esystem.sql السطر 3394 CREATE TABLE `partner_deposits` (amount, deposit_date, capital_period_months, profit_rate, profit_payment_frequency, start_date, end_date, next_profit_date, total_expected_profit, total_paid_profit, remaining_profit, reference_number, bank_account_id, status). و api/partner_deposits.php (POST) ينشئ الإيداع. setup_partners.php السطور 68-96.
- **الحالة في الجديد:** تأكدت بعد بحث عدائي شامل: الجدول والموديل مش موجودين فعلاً في النظام الجديد.

ما تم فحصه:
- /Users/mohamed/Downloads/qarwana/database/migrations/ — فيه بس create_partners_table (2026_06_03_005004) و create_partner_transactions_table (2026_06_03_014002). مفيش أي migration باسم deposits.
- /Users/mohamed/Downloads/qarwana/app/Models/ — فيه Partner.php و PartnerTransaction.php بس. مفيش PartnerDeposit model.
- grep على كل المشروع (app + database + resources + routes) للحقول المميزة بتاعة الجدول القديم: profit_rate / capital_period_months / profit_payment_frequency / next_profit_date / total_expected_profit / total_paid_profit / remaining_profit / partner_deposit / PartnerDeposit = صفر نتائج.
- reference_number موجود بس في bank_transactions و supplier_payments و contractor_payments — مالوش علاقة بإيداعات الشركاء.

الموجود فعلاً (البديل الناقص): الإيداع مجرد صف في partner_transactions نوعه type='deposit' بحقول amount + transaction_date + description + notes فقط. الـ PartnerTransactionController::validateData() (السطور 87-97) مابيقبلش أي حقل أرباح أو مدة. و Partner::totalCapital() / currentBalance() (Partner.php سطور 39-52) بيجمعوا المبالغ حسب النوع بس.

الناقص جوهرياً: منظومة الاستثمار/جدولة الأرباح بالكامل — نسبة الربح، مدة حبس رأس المال، تكرار صرف الأرباح، الربح المتوقع/المدفوع/المتبقي، تاريخ الربح القادم، وربط الإيداع بحساب بنكي محدد (bank_account_id). كله غير موجود. الادعاء صحيح.

ملاحظة على الـ severity: خفضتها من critical لـ high لأن الإيداع الأساسي (رأس المال + الرصيد) موجود كـ transaction، فمفيش فقدان كامل للوظيفة المحاسبية — اللي ناقص هو طبقة جدولة/حساب الأرباح الاستثمارية.
- **التوصية:** إنشاء جدول partner_deposits وموديل PartnerDeposit يحمل: amount, deposit_date, capital_period_months, profit_rate (نسبة سنوية %), profit_payment_frequency (monthly/quarterly/semi_annual/annual/end_of_period), start_date, end_date, next_profit_date, total_expected_profit, total_paid_profit, remaining_profit, status (active/completed/cancelled), reference_number, bank_account_id. وربطه بالشريك. ده أساس الدومين كله.

### 🟠 خطير — صرف الأرباح المرتبط بالجدول (handleProfitPayment) مفقود
- **النوع:** logic
- **الدليل (القديم):** api/partner_transactions.php الدالة handleProfitPayment() السطور 247-365: تصرف ربح من قسط محدد (schedule_id)، تحدّث paid_amount/status (paid/partial)، تحدّث partner_deposits (total_paid_profit, remaining_profit)، تسجّل partner_transactions type=profit_payment مع profit_period_from/to وربط transaction_id بالقسط.
- **الحالة في الجديد:** بعد بحث شامل في النظام الجديد، الـ feature فعلاً مفقود ومفيش أي مكافئ له.

الأدلة:
1) PartnerTransaction model (/Users/mohamed/Downloads/qarwana/app/Models/PartnerTransaction.php): الـ TYPES = deposit/withdrawal/profit/settlement فقط. مفيش profit_payment ولا adjustment. الـ fillable مفيهوش أي ربط بقسط/إيداع/فترة ربح (partner_id, type, amount, transaction_date, description, notes, created_by بس).

2) PartnerTransactionController (/Users/mohamed/Downloads/qarwana/app/Http/Controllers/PartnerTransactionController.php): store() مجرد PartnerTransaction::create($data) بمبلغ يدوي. validateData() بيقبل بس partner_id/type/amount/transaction_date/description/notes. مفيش schedule_id ولا منطق تحديث paid_amount/status ولا تحديث متبقي. مفيش حتى method خاص بصرف الأرباح.

3) Migration (2026_06_03_014002_create_partner_transactions_table.php): الجدول مفيهوش schedule_id ولا profit_period_from/to ولا transaction_id رابط. والـ migrations كلها (شُفت القائمة كاملة) مفيهاش جدول partner_deposits ولا partner_profit_schedules ولا أي جدول أقساط أرباح للشركاء. أعمدة total_paid_profit/remaining_profit غير موجودة في أي مكان.

4) Partner model فيه بس totalCapital() و currentBalance() (حسابات بسيطة بالـ sum على الأنواع)، مفيش أي tracking لأقساط ربح أو متبقي.

5) بحثت في app/database/resources/routes عن: handleProfitPayment, profit_payment, profit_period, total_paid_profit, remaining_profit, schedule_id, partner_deposit, profit_schedule, deposit_schedule — صفر نتائج في كود التطبيق (paid_amount الوحيدة اللي ظهرت بتاعة Invoice، مش ليها علاقة).

6) مفيش Service/Job مخصص لده — app/Services فيه بس BankLedgerService وملوش أي علاقة بالشركاء أو الأرباح. الـ form view (partner_transactions/form.blade.php) مفيهوش أي حقل قسط/إيداع/فترة ربح، مجرد select للنوع من الـ TYPES الأربعة.

الخلاصة: منطق handleProfitPayment (صرف ربح من قسط محدد + تحديث حالة القسط paid/partial + تحديث partner_deposits + تسجيل حركة profit_payment بفترة وربط بالقسط) مفقود بالكامل. النظام الجديد بيسجّل حركة 'profit' يدوية فقط من غير أي ربط أو تتبع. التقييم high مش critical لأنه بيأثر على دقة تتبع توزيع الأرباح والمتبقي مش على سلامة بيانات مالية حرجة فورية، لكنه فقدان حقيقي لمنطق business مهم.
- **التوصية:** إضافة آلية صرف أرباح تختار من الأقساط المستحقة، تحدّث حالة القسط (paid/partial) والمبلغ المدفوع/المتبقي على الإيداع، وتسجّل الحركة مع فترة الربح. مع إمكانية الصرف الكلي أو الجزئي.

### 🟠 خطير — عملية تصفية حساب الشريك (handleSettlement) مفقودة منطقياً
- **النوع:** logic
- **الدليل (القديم):** api/partner_transactions.php الدالة handleSettlement() السطور 370-464: تجمع الأرباح المستحقة + الرصيد الحالي = total_due، تقفل كل الأقساط (status=paid)، تحوّل الإيداعات النشطة لـ completed (remaining_profit=0)، تسجّل حركة settlement، وتعيّن partner.status='settled' و current_balance=0. وزر «تصفية الحساب» في partners.php السطور 602-604.
- **الحالة في الجديد:** الكلام صح، الـ feature فعلاً مفقودة بعد بحث عدائي كامل. PartnerController.php (/Users/mohamed/Downloads/qarwana/app/Http/Controllers/PartnerController.php) فيه CRUD عادي بس (index/show/create/store/edit/update/destroy) ومفيش أي action اسمه settle/تصفية. routes/web.php سطر 54 و86 فيهم Route::resource بس من غير أي route تصفية مخصص. النوع 'settlement' => 'تسوية' موجود في PartnerTransaction::TYPES بس بيتسجّل يدوي عبر فورم الحركة العادي (store بيعمل validate للمبلغ والنوع وبيحفظ السجل من غير أي حساب مستحقات). حالة 'settled' => 'مُسوّى' موجودة في Partner::STATUSES بس بتتغيّر يدوي من فورم التعديل فقط، مفيش حاجة بتقلبها أوتوماتيك. partners/show.blade.php فيه زرار تعديل ورجوع بس، مفيش زرار «تصفية الحساب». الأهم: مفيش جداول/موديلات partner_profit_schedule ولا partner_deposits خالص في النظام الجديد (الميجريشنز بس create_partners_table و create_partner_transactions_table)، فمفيش مكافئ لإقفال الأقساط (status=paid) ولا تحويل الإيداعات النشطة لـ completed (remaining_profit=0). Partner::currentBalance() بيحسب الرصيد ديناميكياً بس مفيش أي عملية بتصفّره ولا فيه حقل total_profits. المنطق الكامل بتاع handleSettlement() الموجود في legacy (/Users/mohamed/Downloads/system (2)/api/partner_transactions.php سطور 370-464: due_profits + current_balance = total_due، إقفال الجدول، إكمال الإيداعات، تسجيل حركة settlement، وتعيين status=settled و current_balance=0) مش موجود في الـ rebuild. خليتها high مش critical لأن اللبنات الأساسية (نوع settlement + حالة settled) موجودة، فهي automation/orchestration ناقصة مش دومين غايب تماماً، ومفيش فساد بيانات مباشر لكن الـ workflow كله بقى إدخال يدوي معرّض للخطأ.
- **التوصية:** إضافة إجراء تصفية يحسب (الرصيد + الأرباح المستحقة)، يقفل الأقساط والإيداعات، يغيّر حالة الشريك لـ settled ويصفّر رصيده، ويسجّل حركة تصفية واحدة.

### 🟠 خطير — الربط مع الحسابات البنكية (bank_transactions) مفقود
- **النوع:** integration
- **الدليل (القديم):** api/partner_deposits.php السطور 173-214: عند الإيداع مع bank_account_id يُسجَّل bank_transactions type=deposit category='partner_capital' ويُحدَّث رصيد البنك. و api/partner_transactions.php السطور 199-225: عند السحب مع خيار deduct_from_bank يُسجَّل سحب بنكي ويُحدَّث الرصيد.
- **الحالة في الجديد:** تأكدت إن الفجوة حقيقية فعلاً. النظام الجديد فيه بنية بنكية كاملة (app/Models/BankTransaction.php، app/Models/BankAccount.php، app/Services/BankLedgerService.php) وبيُستخدم في وحدات تانية، لكن حركات الشركاء مش مربوطة بيها خالص.

الأدلة:
- database/migrations/2026_06_03_014002_create_partner_transactions_table.php: مفيش عمود bank_account_id (الأعمدة: partner_id, type, amount, transaction_date, description, notes, created_by فقط). على عكس revenues/expenses/supplier_payments/contractor_payments اللي كلها فيها foreignId('bank_account_id') مربوط بـ bank_accounts.
- app/Http/Controllers/PartnerTransactionController.php: store()/update() بيعملوا PartnerTransaction::create($data) بس، مفيش أي استدعاء لـ BankLedgerService ولا تسجيل bank_transaction ولا خيار deduct_from_bank. الـ validateData ما بيقبلش bank_account_id ولا deduct_from_bank.
- app/Models/Partner.php و app/Models/PartnerTransaction.php: مفيش أي علاقة أو حقل بنكي (currentBalance بيتحسب من حركات الشريك بس).
- grep على bank/BankLedger/deduct_from/partner_capital في كل ملفات الشركاء (controllers + models + views) رجع صفر نتائج.
- resources/views/partner_transactions/form.blade.php: الفورم فيه partner_id/type/amount/date/description/notes بس — مفيش select لحساب بنكي ولا checkbox للخصم من البنك.
- الربط البوليمورفي related_type على bank_transactions مستخدم لـ: expense, contractor_payment, bank_transfer, revenue, supplier_payment — لكن مفيش ولا حالة لـ partner_capital أو partner_transaction.

(الإشارة الوحيدة لـ Partner جنب البنك في AppServiceProvider.php سطر 15 هي مجرد قائمة موديلات عامة، مش ربط بنكي.)

الخلاصة: نمط الربط البنكي موجود ومُطبّق في وحدات تانية، لكنه متغفلش عمداً في حركات الشركاء، فالميزة اللي في النظام القديم (تسجيل إيداع رأس مال في bank_transactions وتحديث رصيد البنك، وخيار deduct_from_bank عند السحب) مش موجودة في الـ rebuild.
- **التوصية:** ربط الإيداع/السحب/صرف الأرباح بالحساب البنكي: تسجيل حركة بنكية وتحديث رصيد البنك تلقائياً (مع خيار deduct_from_bank الاختياري في السحب كما في القديم) للحفاظ على تطابق الخزينة مع حسابات الشركاء.

### 🟠 خطير — التحقق من الرصيد عند السحب (لا يتجاوز المتاح) مفقود
- **النوع:** logic
- **الدليل (القديم):** api/partner_transactions.php handleWithdrawal السطور 153-157: يرفض السحب لو amount > current_balance ويرجّع رسالة بالرصيد المتاح. وكان بيحدّث total_withdrawals و current_balance.
- **الحالة في الجديد:** الـ gap حقيقي ومؤكد بعد بحث شامل. PartnerTransactionController::validateData (الأسطر 87-97 في /Users/mohamed/Downloads/qarwana/app/Http/Controllers/PartnerTransactionController.php) بيتحقق بس من: partner_id موجود، type ضمن TYPES، amount numeric gt:0، التاريخ، الوصف، الملاحظات — مفيش أي مقارنة بالرصيد المتاح ولا قاعدة max/lte. store() و update() بينادوا validateData وبعدين create/update مباشرة من غير أي فحص للرصيد. مفيش FormRequest (مفيش مجلد Requests أصلاً)، مفيش Observers، مفيش custom Rules، ومفيش boot/saving/creating hooks في PartnerTransaction.php ولا Partner.php. الـ method الوحيدة Partner::currentBalance() (الأسطر 46-52 في /Users/mohamed/Downloads/qarwana/app/Models/Partner.php) بتُستخدم للعرض فقط في /Users/mohamed/Downloads/qarwana/resources/views/partner_transactions/show.blade.php سطر 39، مش كحارس وقت الكتابة. النتيجة: ممكن تسجيل سحب أكبر من رصيد الشريك ويطلع رصيد سالب — تماماً زي ما الـ legacy (api/partner_transactions.php السطور 153-157) كان بيرفض. جدير بالملاحظة إن النظام الجديد بيطبّق نفس فكرة الحارس في تدفقات تانية (InventoryMovementController.php سطر 65 بيرمي DomainException لما الكمية أكبر من المتاح)، فالفريق عارف الـ pattern بس مطبّقش على سحب الشركاء.
- **التوصية:** إضافة تحقق عند حركات السحب/التسوية بأن المبلغ لا يتجاوز currentBalance() للشريك، مع رسالة خطأ توضح الرصيد المتاح.

### 🟡 متوسط — جدولة الأرباح وحسابها التلقائي (partner_profit_schedule + generateProfitSchedule) مفقودة
- **النوع:** logic
- **الدليل (القديم):** esystem.sql السطر 3432 CREATE TABLE `partner_profit_schedule` (due_date, amount, period_from, period_to, status pending/paid/partial/cancelled, paid_amount, paid_date, transaction_id). الدالة generateProfitSchedule() في api/partner_deposits.php السطور 326-422 تحسب الربح الشهري = amount*profit_rate/100/12 وتولّد أقساط حسب الدورية مع قسط جزئي للفترة الأخيرة. وحساب total_expected_profit = amount*profit_rate/100*months/12 (السطر 100).
- **الحالة في الجديد:** أكدت الفجوة بعد بحث عدائي شامل. لا يوجد أي مكافئ في النظام الجديد:

1) جدول partner_profit_schedule غير موجود إطلاقًا. قائمة جداول قاعدة البيانات الحيّة (/Users/mohamed/Downloads/qarwana/database/database.sqlite) تحتوي فقط على partners و partner_transactions. لا يوجد migration باسم schedule (راجعت كل ملفات /Users/mohamed/Downloads/qarwana/database/migrations).

2) جدول partners (migration 2026_06_03_005004_create_partners_table.php و schema الحيّ) لا يحتوي على أي عمود profit_rate أو نسبة ربح. الأعمدة: name, phone, email, national_id, address, join_date, status, notes, created_by فقط.

3) جدول partner_transactions (migration 2026_06_03_014002) لا يحتوي على due_date/period_from/period_to/paid_amount/paid_date/status/transaction_id. مجرد: type(deposit/withdrawal/profit/settlement), amount, transaction_date, description, notes.

4) موديل Partner (/Users/mohamed/Downloads/qarwana/app/Models/Partner.php) فيه totalCapital() السطر 40 و currentBalance() السطر 46 فقط. لا توجد أي دالة generateProfitSchedule أو حساب total_expected_profit أو منطق amount*rate/100/12.

5) لا يوجد أي مجلد Console/Jobs/Commands في app — مفيش scheduled command أو job يولّد أقساط. مجلد Services فيه BankLedgerService فقط.

6) PartnerTransactionController (السطور 56-63 و 87-97) و الـ form (resources/views/partner_transactions/form.blade.php) يؤكدان أن نوع 'profit' مجرد إدخال يدوي للمبلغ عبر CRUD عادي — لا حقل نسبة ولا دورية ولا توليد جدول.

7) grep على profit_schedule/generateProfit/profit_rate في كامل app+database+resources+routes = صفر نتائج. أي مطابقات "rate" تخص tax_rate/depreciation_rate/RateLimiter وأي paid_amount/paid_date تخص الفواتير لا الشركاء.

التقدير medium وليس high لأن المنطق يمكن تعويضه يدويًا عبر إدخال حركة 'profit' لكل قسط، لكن الأتمتة (الحساب التلقائي والجدولة وتتبّع الحالة pending/paid/partial) غير موجودة فعلًا.
- **التوصية:** بناء جدول partner_profit_schedule + Service (مثلاً PartnerProfitService) يولّد الأقساط تلقائياً عند الإيداع حسب الدورية ويحسب الربح المتوقع، مع دعم القسط الجزئي للفترة الأخيرة كما في الكود القديم. وعرض «الأرباح المستحقة» (pending/partial وdue_date<=today) كما في partners.php السطور 99-104.

### 🟡 متوسط — كشف حساب الشريك القابل للطباعة والتصدير (partner_statement.php) مفقود
- **النوع:** report
- **الدليل (القديم):** ملف partner_statement.php كامل (30KB): رأس بترويسة الشركة، بيانات الشريك، المشاريع المرتبطة، 4 كروت ملخّص (إجمالي إيداعات/سحوبات/أرباح مصروفة/الرصيد)، جدول العمليات بكل التفاصيل، فلتر تاريخ ومشروع (السطور 36-82)، زر طباعة + تصدير Excel (exportToExcel JS السطور 724-761)، و @media print styles.
- **الحالة في الجديد:** بعد بحث عدائي كامل في النظام الجديد: الـ feature فعلاً مفقود. الأدلة:

1) /Users/mohamed/Downloads/qarwana/app/Http/Controllers/PartnerController.php فيه بس resource methods العادية (index/show/create/store/edit/update/destroy) — مفيش method اسمه statement أو print أو export. الـ show() بيعمل load للـ transactions بدون أي فلتر تاريخ/مشروع.

2) /Users/mohamed/Downloads/qarwana/routes/web.php سطر 54: Route::resource('partners', PartnerController::class) بس — مفيش route مخصص للكشف/الطباعة/التصدير.

3) /Users/mohamed/Downloads/qarwana/resources/views/partners/show.blade.php بيطابق الادعاء بالظبط: عرض على الشاشة بكرتين بس (totalCapital + currentBalance — مش الـ4 كروت بتاعة الـ legacy: إيداعات/سحوبات/أرباح مصروفة/الرصيد)، جدول بسيط 4 أعمدة (التاريخ/النوع/المبلغ/البيان)، بدون زر طباعة ولا Excel ولا فلتر تاريخ/مشروع.

4) grep على statement|print|export|excel|كشف|طباعة|تصدير في resources/views/partners و partner_transactions = صفر نتائج. ومفيش exportToExcel ولا @media print ولا window.print في أي مكان.

5) composer.json مفيهوش أي package تصدير/طباعة (maatwebsite/excel, dompdf, snappy, mpdf).

6) مفيش أي ملف blade للطباعة/الكشف/التقرير الخاص بالشريك.

7) في ReportController على /reports بس هو تقرير إيرادات/مصروفات/ربح المشاريع — مفيهوش أي ذكر للشريك ولا كشف حساب شريك ولا طباعة/تصدير.

8) Partner model (/Users/mohamed/Downloads/qarwana/app/Models/Partner.php) معندوش علاقة بالمشاريع أصلاً، فحتى الفلترة بالمشروع مش مدعومة بالبيانات.

التصحيح في الـseverity: خفّضتها من المتوقع لـ medium لأن البيانات الأساسية موجودة وبتتعرض على الشاشة (show.blade)، والناقص هو طبقة العرض/التقرير (طباعة + Excel + فلاتر + الكروت التفصيلية) مش الداتا نفسها — يعني فجوة حقيقية لكنها feature تقرير/تصدير مش فقدان بيانات أو وظيفة مالية حرجة.
- **التوصية:** بناء صفحة/route كشف حساب شريك قابلة للطباعة (print view) مع تصدير Excel/CSV، فلترة بالتاريخ والمشروع، وملخص إجماليات (إيداعات/سحوبات/أرباح/رصيد) مطابق للقديم.

### 🟡 متوسط — حقول جوهرية ناقصة في جدول partner_transactions
- **النوع:** field
- **الدليل (القديم):** esystem.sql السطر 3463 CREATE TABLE `partner_transactions` يحتوي: deposit_id, balance_after, reference_number, payment_method (cash/bank_transfer/check), bank_account_id, check_number, delivered_by, received_by, profit_period_from, profit_period_to. والـ enum يشمل profit_payment و adjustment.
- **الحالة في الجديد:** الكلام صحيح والـ gap موجود فعلاً بعد بحث جدّي.

الـ migration الوحيد للجدول: /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_014002_create_partner_transactions_table.php — فيه بس: partner_id, type(string 20), amount, transaction_date, description, notes, created_by, timestamps. مفيش migration تاني بيعمل alter للجدول (grep على partner_transactions في مجلد migrations رجّع الملف ده بس).

الموديل /Users/mohamed/Downloads/qarwana/app/Models/PartnerTransaction.php — الـ const TYPES فيه 4 أنواع بس: deposit / withdrawal / profit / settlement. مفيش profit_payment ولا adjustment. الـ controller (PartnerTransactionController::validateData) بيقيّد النوع على array_keys(TYPES) فمستحيل تتسجّل الأنواع الناقصة، والـ form.blade.php بيعرض نفس الـ 4 أنواع بس مع الحقول الستة الأساسية فقط.

grep على الحقول الجوهرية الناقصة في كل الريبو:
- check_number / delivered_by / received_by / deposit_id / profit_period / profit_payment / adjustment = صفر نتائج خالص في أي ملف.
- balance_after / payment_method / bank_account / reference_number ظهرت في ملفات، لكن كلها في موديول البنك بس (BankAccount.php, BankLedgerService.php, migration بتاع bank_transactions) ومالهاش أي علاقة بالشركاء — grep على payment_method/bank_account/reference_number "near partner" رجّع صفر. وأهم حاجة: balance_after في BankLedgerService هي تعليق بيقول إنهم متعمّدين مش بيخزنوا عمود balance_after.

ملاحظة على الـ severity: النظام الجديد بيحسب رصيد الشريك dynamically في Partner::balance() (deposits ناقص withdrawal+profit+settlement) بدل ما يخزن balance_after، فده تصميم بديل مقبول لجزء من الموضوع. لكن باقي الحقول (طريقة الدفع، رقم المرجع/الشيك، الحساب البنكي، مين سلّم/استلم، فترة الربح) + نوعَي profit_payment و adjustment غايبين تماماً ومفيش أي مكافئ. خفّضت من high لـ medium لأن جزء من الوظيفة (تتبّع الرصيد) متغطّي بطريقة تانية، بس الـ gap نفسه حقيقي.
- **التوصية:** إضافة الأعمدة: deposit_id, balance_after, reference_number, payment_method, bank_account_id, check_number, delivered_by, received_by, profit_period_from, profit_period_to. وإضافة الأنواع profit_payment و adjustment. حقل balance_after مهم لتتبع الرصيد بعد كل حركة في كشف الحساب.

### 🟡 متوسط — ربط الشريك بالمشروع (project_id) وفلترة الشركاء بالمشروع مفقود
- **النوع:** field
- **الدليل (القديم):** esystem.sql جدول partners فيه عمود `project_id`. partners.php يفلتر الشركاء بالمشروع (السطور 59-62, 463-475) و partner_statement.php يعرض المشاريع المرتبطة بالشريك (السطور 41-48, 518-571). البيانات الفعلية: الشركاء الثلاثة كلهم project_id=41.
- **الحالة في الجديد:** الفجوة مؤكدة. بحثت كويس في النظام الجديد ومفيش أي ربط بين الشريك والمشروع:

1) migration الشركاء /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_005004_create_partners_table.php — الأعمدة: name, phone, email, national_id, address, join_date, status, notes, created_by فقط. مفيش project_id إطلاقا.

2) migration معاملات الشركاء /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_014002_create_partner_transactions_table.php — فيه partner_id بس، مفيش project_id.

3) Model /Users/mohamed/Downloads/qarwana/app/Models/Partner.php — العلاقات creator و transactions بس، مفيش علاقة project ولا belongsToMany. والـ fillable مفهوش project_id.

4) PartnerController::index (السطور 25-39 في /Users/mohamed/Downloads/qarwana/app/Http/Controllers/PartnerController.php) — الفلترة بالـ search على name و phone بالـ LIKE فقط، مفيش أي فلترة بالمشروع.

5) مفيش جدول pivot (partner_project / project_partner) في مجلد migrations، ومفيش belongsToMany في أي حتة بالنظام (grep = صفر).

6) views الشركاء (partners + partner_transactions) — grep على project/مشروع رجع صفر.

7) routes/web.php — partners و partner-transactions resources عادية، مفيش حاجة خاصة بالمشروع.

ملاحظة مهمة تثبت إن الإغفال مقصود/عرضي: الباترن نفسه (project_id + constrained('projects')) متطبق على كيانات كتير تانية (Material, Expense, Invoice, Revenue, Tax, PurchaseOrder, ContractorExtract, InventoryMovement, ProjectFile, EmployeeTransaction) — يعني الفكرة موجودة بالنظام بس الشركاء بالتحديد اتساب من غيرها.

الخلاصة: ربط الشريك بالمشروع وفلترة الشركاء بالمشروع فعلا مفقودين. حطيت الـ severity medium مش high لأن الـ partner_statement في الـ legacy بيعرض المشاريع المرتبطة لكن العلاقة في الداتا الفعلية واحد-لواحد بسيطة (التلاتة كلهم project_id=41)، فالأثر الوظيفي محدود لكن feature حقيقي ناقص.
- **التوصية:** إضافة project_id لجدول partners (أو علاقة many-to-many لو الشريك في أكثر من مشروع)، وإتاحة فلترة الشركاء حسب المشروع وعرض مشاريع الشريك في كشف الحساب.

### 🟡 متوسط — إحصائيات لوحة الشركاء (أرباح مستحقة، رأس مال نشط، عدد الإيداعات) مفقودة
- **النوع:** ui
- **الدليل (القديم):** partners.php السطور 90-104: كروت إحصائية (إجمالي الشركاء/النشطين، إجمالي رأس المال، أرباح مدفوعة، أرباح مستحقة من partner_profit_schedule). وعدد الإيداعات ورأس المال النشط لكل شريك (السطور 69-70). و Modal «جدول الأرباح المستحقة» (السطور 1154-1167).
- **الحالة في الجديد:** الـ gap حقيقي بعد بحث معمّق. PartnerController::index في /Users/mohamed/Downloads/qarwana/app/Http/Controllers/PartnerController.php (السطور 25-39) بيعمل paginate(15) بس + بحث، من غير أي stats. و /Users/mohamed/Downloads/qarwana/resources/views/partners/index.blade.php مفيهوش كروت إحصائية خالص (فورم بحث + جدول بس). مفيش أي أثر لـ partner_profit_schedule في النظام كله (لا migration، لا model، لا view — صفر نتائج لكلمة "schedule"). مفيش «أرباح مستحقة» للشركاء (كل نتائج «مستحق» بتخص مقاولين/موردين/موظفين/ضرائب). مفيش «رأس مال نشط» (activeCapital/رأس المال النشط = صفر نتائج) ولا «عدد إيداعات» مجمّع. مفيش Modal جدول أرباح مستحقة.

اللي موجود كبديل جزئي: /Users/mohamed/Downloads/qarwana/resources/views/partners/show.blade.php (السطور 34-35) بيعرض لكل شريك totalCapital() و currentBalance()، والميثودين دول معرّفين في app/Models/Partner.php (السطور 39-52). وفيه نوع حركة "profit" في PartnerTransaction::TYPES (app/Models/PartnerTransaction.php السطر 26) لكن كحركة يدوية بس من غير أي جدولة/استحقاق.

خفّضت الـ severity من المتوقع إلى medium لأن البيانات المالية الأساسية (رأس المال/الرصيد) ظاهرة لكل شريك في صفحة show، فدي فجوة UI/feature على مستوى الـ index aggregates + subsystem الأرباح المستحقة (partner_profit_schedule) اللي مش موجود حتى على مستوى الـ data model، مش فقدان بيانات كامل.
- **التوصية:** إضافة كروت إحصائية في صفحة الشركاء (إجمالي/نشط، رأس المال، أرباح مدفوعة، أرباح مستحقة) وعرض ملخص الإيداعات والأرباح المستحقة لكل شريك، بعد بناء جداول deposits/schedule.

---

## Expenses (categories, installment payments, alerts, custody)

> إعادة بناء وحدة المصروفات في نظام Laravel الجديد ناقصة بشكل جوهري وتغطي تقريباً CRUD أساسي فقط مربوط بحساب بنكي. النظام القديم (expenses.php + api/expenses.php + api/expense_payments.php) يحتوي على منظومة متكاملة: فئات ديناميكية قابلة للإضافة (جدول expense_categories) + فئة مخصصة نصية (custom_category)، ومصروفات آجلة (credit) بنظام دفعات تقسيط كامل (جدول expense_payments مع تتبع paid_amount/remaining_amount/payment_status)، وتنبيهات استحقاق وحدّ أدنى (جدول expense_alerts + الأعمدة due_date/min_limit/alert_enabled/profit_margin)، وربط المصروف بعهدة الموظف (delivered_by_employee_id) مع خصم تلقائي من رصيد العهدة عبر employee_transactions بنوع expense ومرجع reference_type/reference_id، بالإضافة لرفع مرفقات PDF وحقول recipient/reference_number/details. النظام الجديد يستخدم ثابت CATEGORIES ثابت (7 فئات)، وجدول expenses فيه فقط: project_id, category, description, amount, expense_date, payment_method, bank_account_id, notes — أي أن كل ميزات الدفع الجزئي والتنبيهات والعهدة والمرفقات والفئات الديناميكية مفقودة بالكامل. كما أن جدول employee_transactions الجديد لا يحتوي أعمدة reference_type/reference_id فلا يمكن أصلاً ربط مصروف بحركة عهدة. هذه الوحدة تحتاج إعادة بناء كبيرة قبل أن تكون صالحة لشركة مقاولات.

### 🟠 خطير — نظام الدفعات الجزئية (تقسيط) للمصروفات الآجلة مفقود بالكامل
- **النوع:** module
- **الدليل (القديم):** جدول expense_payments في esystem.sql (السطر 2926: expense_id, amount, payment_date, payment_method, reference_number, notes, created_by). API كامل في /Users/mohamed/Downloads/system (2)/api/expense_payments.php (318 سطر: POST يضيف دفعة ويحدّث paid_amount/remaining_amount/payment_status، DELETE يرجّع الدفعة ويعيد حساب الحالة). وأعمدة expenses: paid_amount, remaining_amount, payment_status enum('paid','partial','unpaid') (esystem.sql السطور 2669-2671). الفلترة بحالة الدفع في expenses.php السطر 47 و420.
- **الحالة في الجديد:** الادعاء صحيح — الميزة مفقودة فعلاً بعد بحث عدائي شامل. (1) لا يوجد موديل ExpensePayment ولا migration: grep -rni "expense_payment|ExpensePayment" على كل /Users/mohamed/Downloads/qarwana رجّع 0 نتيجة. (2) migration المصروفات /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_010334_create_expenses_table.php يحتوي فقط: project_id, category, description, amount, expense_date, payment_method, bank_account_id, notes, created_by — بدون paid_amount/remaining_amount/payment_status/due_date. (3) /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ExpenseController.php: store/update يكتبوا amount كامل، وvalidateData (سطور 139-151) ما فيهاش أي حقل دفعات. (4) Expense::PAYMENT_METHODS = cash/bank_transfer/check فقط، مفيش credit/آجل. (5) كل نتائج partial/paid_amount/payment_status بتخص موديولات تانية منفصلة (Invoice status، ContractorExtract، دفعات الموردين والمقاولين) ومش بديل لتقسيط المصروفات. اللافت إن Revenue الجديد كمان شال نفس حقول الدفع الجزئي اللي كانت في النظام القديم (esystem.sql سطر 3829-3831)، فده تبسيط متعمّد مش نقل للميزة لمكان تاني. الدليل القديم متأكد منه: /Users/mohamed/Downloads/system (2)/api/expense_payments.php موجود (عنوانه "API لإدارة دفعات المصروفات الآجلة")، وexpense_payments في esystem.sql سطر 2926، والأعمدة سطور 2669-2671. خفّضت الخطورة من المتوقع critical لـ high لأن تسجيل المصروف الأساسي شغّال والـ AP-style tracking موجود للموردين والمقاولين، فالناقص تحديداً هو تتبّع تقسيط/تأجيل المصروفات.
- **التوصية:** إنشاء جدول وموديل expense_payments وربطه بـ Expense (hasMany)، وإضافة أعمدة paid_amount/remaining_amount/payment_status/due_date لجدول expenses، وإضافة قيمة 'credit' لطرق الدفع، وبناء واجهة تسجيل/حذف دفعات تعيد حساب المتبقي والحالة تلقائياً (نقل منطق api/expense_payments.php) مع فلتر بحالة الدفع في صفحة القائمة.

### 🟠 خطير — ربط المصروف بعهدة الموظف والخصم التلقائي من رصيد العهدة مفقود
- **النوع:** integration
- **الدليل (القديم):** العمود delivered_by_employee_id في جدول expenses (esystem.sql السطر 2679) + setup_expense_custody.php الذي يضيفه مع FK لـ employees. منطق الخصم في /Users/mohamed/Downloads/system (2)/api/expenses.php السطور 235-272: عند تحديد موظف مسلّم يُحسب رصيد عهدته ثم تُسجّل حركة employee_transactions نوع 'expense' بـ reference_type='expense' و reference_id=expense_id. وعند التعديل/الحذف تُحدّث/تُحذف الحركة (السطور 396-410 و501-503). fix_custody_deductions.php يثبت اعتماد reference_type/reference_id لربط العهدة.
- **الحالة في الجديد:** بحثت بعمق في النظام الجديد والادعاء صحيح تماماً، الفيتشر مفقود فعلاً. التفاصيل:

1) جدول expenses الجديد (/Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_010334_create_expenses_table.php) لا يحتوي delivered_by_employee_id ولا أي عمود مسلّم/مستلم/employee. الأعمدة فقط: project_id, category, description, amount, expense_date, payment_method, bank_account_id, notes, created_by. والربط الوحيد للمصروف هو بحساب بنكي (bank_account_id) عبر BankTransaction، مش بعهدة موظف.

2) جدول employee_transactions الجديد (/Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_014001_create_employee_transactions_table.php) لا يحتوي reference_type/reference_id إطلاقاً، فلا توجد آلية لربط حركة عهدة بمصروف. الأعمدة: employee_id, type, amount, transaction_date, project_id, description, notes, created_by.

3) ExpenseController.php (/Users/mohamed/Downloads/qarwana/app/Http/Controllers/ExpenseController.php) لا يستورد ولا يستدعي EmployeeTransaction نهائياً. store/update/destroy تتعامل فقط مع BankTransaction عبر BankLedgerService (syncBankTransaction/removeLinkedBankTransaction). validateData لا يقبل أي حقل موظف. ولا حقل موظف/عهدة في expense views (resources/views/expenses/).

4) custodyBalance() في Employee.php (السطور 45-50) تحسب فقط sum('custody') ناقص sum('custody_return') ولا تطرح أي مصروفات، فلا خصم تلقائي من رصيد العهدة عند تسجيل مصروف.

5) Expense model fillable لا يحوي أي مرجع لموظف؛ EmployeeTransaction model TYPES لا يحوي 'expense' (فقط salary/advance/advance_return/custody/custody_return/bonus/deduction) ولا أي علاقة بالمصروفات.

بحث grep شامل عبر migrations + app + resources + routes + seeders عن (delivered_by|recipient|reference_type|reference_id|custody+expense) رجع صفر تطابقات ذات صلة. الفيتشر غائب فعلاً ولا يوجد مكافئ معقول تحت اسم مختلف.

ملاحظة على الـ severity: خليتها high بدل critical لأن النظام الجديد يدعم تسجيل حركات عهدة/رد عهدة يدوياً عبر EmployeeTransactionController، فالمستخدم نظرياً يقدر يسجّل خصم العهدة يدوياً كحركة منفصلة. لكن الربط التلقائي والتكامل (ربط المصروف بالعهدة والخصم التلقائي وتتبع المرجع) مفقود بالكامل.
- **التوصية:** إضافة delivered_by_employee_id لجدول expenses، وأعمدة reference_type/reference_id لجدول employee_transactions، وعند حفظ مصروف مرتبط بموظف تُنشأ حركة عهدة بنوع مناسب (custody_deduction/expense) تُخصم من رصيده ضمن نفس الـ DB::transaction، مع تحديث/حذف الحركة عند تعديل/حذف المصروف، وتعديل custodyBalance ليطرح هذه الخصومات.

### 🟡 متوسط — الفئات الديناميكية القابلة للإضافة + الفئة المخصصة مفقودة (استبدلت بثابت)
- **النوع:** module
- **الدليل (القديم):** جدول expense_categories في esystem.sql (السطر 2911: name, description, color, is_active) قابل للإضافة من المستخدم، مع فئات افتراضية تُزرع في update_expenses_system.php السطر 76. والعمود custom_category varchar(100) في جدول expenses (esystem.sql السطر 2665) لإدخال فئة نصية حرة عند اختيار 'أخرى' (expenses.php السطور 532-533, 703). أيضاً جدول custom_payment_methods (esystem.sql السطر 2407) لطرق دفع مخصصة.
- **الحالة في الجديد:** الادعاء صحيح بعد بحث عدائي شامل. النظام الجديد فعلاً يستخدم ثابت hardcoded ولا يوجد أي بديل ديناميكي:

1) /Users/mohamed/Downloads/qarwana/app/Models/Expense.php السطور 10-18: const CATEGORIES بـ7 قيم ثابتة فقط. والسطور 20-24: const PAYMENT_METHODS بـ3 قيم ثابتة.

2) لا يوجد جدول/موديل expense_categories: grep على expense_categor / ExpenseCategory أرجع صفر نتائج، ولا توجد migration باسم *categor*.

3) لا يوجد عمود custom_category: migration الـexpenses /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_010334_create_expenses_table.php السطر 17 فيه category varchar(30) فقط، وgrep على custom_categor صفر نتائج.

4) لا يوجد custom_payment_methods: grep على custom_payment صفر نتائج.

5) التحقق يرفض أي قيمة خارج الثابت: /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ExpenseController.php السطر 143 = 'category' => ['required','in:'.implode(',',array_keys(Expense::CATEGORIES))] — قائمة بيضاء in: مش lookup من DB، فالمستخدم لا يقدر يدخل فئة جديدة أو نصية حرة.

6) الفورم dropdown ثابت: /Users/mohamed/Downloads/qarwana/resources/views/expenses/form.blade.php السطور 23-25 بتلف على Expense::CATEGORIES بدون أي حقل نصي حر أو فرع 'أخرى => اكتب فئة'.

7) جرّبت أدحض الادعاء عبر نظام Settings العام (key/value) في app/Models/Setting.php لكنه غير مستخدم نهائياً للفئات (grep categor عليه = صفر).

السبب في خفض الخطورة من high إلى medium: الوظيفة الأساسية (تصنيف المصروفات + فلترة + تقارير حسب الفئة) موجودة وتعمل، والمفقود هو فقط القابلية للتوسع (إضافة فئات/طرق دفع جديدة من المستخدم + الفئة النصية الحرة) وهي تحسين قابلية إدارة وليست تعطيل لميزة جوهرية.
- **التوصية:** إنشاء جدول وموديل expense_categories (name, color, is_active) وإدارة CRUD له، وإضافة عمود custom_category لجدول expenses، وتغيير التحقق من ثابت إلى exists على الجدول. اختيارياً جدول custom_payment_methods لطرق الدفع المخصصة.

### 🟡 متوسط — رفع مرفق PDF/صورة للمصروف مفقود
- **النوع:** field
- **الدليل (القديم):** العمود attachment_file varchar(255) في جدول expenses (esystem.sql السطر 2676 'ملف PDF مرفق'). دالة handleFileUpload ورفع الملف في /Users/mohamed/Downloads/system (2)/api/expenses.php السطور 157, 294-298، وحذف الملف عند حذف المصروف (السطور 507-508)، وعرض رابط العرض في expenses.php السطور 592-593 و1264.
- **الحالة في الجديد:** تأكدت إن الـ feature فعلاً مفقودة بعد بحث جاد في كل أماكن النظام الجديد. التفاصيل:

1) Migration: /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_010334_create_expenses_table.php — جدول expenses فيه (project_id, category, description, amount, expense_date, payment_method, bank_account_id, notes, created_by) ومفيش أي عمود attachment_file ولا أي عمود لتخزين مسار ملف.

2) Model: /Users/mohamed/Downloads/qarwana/app/Models/Expense.php — الـ $fillable مفيهوش attachment، ومفيش relation للملفات.

3) Controller: /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ExpenseController.php — store() وupdate() بيستخدموا validateData() اللي بيـvalidate الحقول دي بس ومفيش 'file'/'attachment'. مفيش $request->file()، ولا hasFile()، ولا ->store()، ولا Storage:: في أي حتة في الكنترولر. وdestroy() بيحذف المصروف والحركة البنكية بس من غير حذف أي ملف.

4) Views: form.blade.php مفيهاش input type=file ولا enctype=multipart/form-data (الـ form بيبعت POST عادي). index.blade.php وshow.blade.php مفيهمش أي رابط عرض/تنزيل مرفق.

5) بحثت على مستوى التطبيق كله عن أي subsystem للمرفقات (UploadedFile, ->store(, hasFile(, Storage::, spatie media-library) — اللي طلع بس هو ProjectFileController + جدول project_files. ده نظام مخصوص للمشاريع فقط: الجدول فيه project_id (NOT NULL, cascadeOnDelete) من غير أي أعمدة polymorphic (attachable_type/id)، والـ store بيتطلب project_id exists:projects,id. يعني مينفعش يربط ملف بمصروف، فمش بديل معقول للميزة.

الخلاصة: ميزة رفع مرفق PDF/صورة للمصروف (attachment_file) مش موجودة في النظام الجديد لا في الجدول ولا الموديل ولا الكنترولر ولا الـ views، ومفيش أي بديل عام يغطيها. عدّلت الـ severity لـ medium لأنها بيانات مساعدة/توثيقية (إيصالات) مش منطق مالي أساسي — الفقدان مؤكد لكن تأثيره تشغيلي مش حرج.
- **التوصية:** إضافة عمود attachment_file/path لجدول expenses وحقل رفع ملف في النموذج مع تخزين عبر Storage وعرض/حذف المرفق.

### ⚪ بسيط — تنبيهات المصروفات (استحقاق الدفع الآجل / تجاوز الحد) مفقودة
- **النوع:** module
- **الدليل (القديم):** جدول expense_alerts في esystem.sql (السطر 2895: expense_id, alert_type enum('due_date','min_limit','custom'), alert_date, message, is_read) يُنشأ في update_expenses_system.php السطر 102. وأعمدة التنبيه في جدول expenses: due_date (السطر 2673), min_limit (2681), alert_enabled (2683). العرض في expenses.php السطور 512-513 يُظهر جرس تنبيه أحمر لتاريخ الاستحقاق على المصروفات الآجلة.
- **الحالة في الجديد:** أكدت الفجوة بعد بحث عدائي شامل في النظام الجديد /Users/mohamed/Downloads/qarwana.

لا يوجد جدول/موديل expense_alerts:
- مفيش migration باسم alerts (راجعت كل قائمة database/migrations: أقرب ملف هو 2026_06_03_010334_create_expenses_table.php وملوش علاقة بالتنبيهات).
- مفيش app/Models/ExpenseAlert.php (راجعت كل قائمة Models).
- grep على expense_alert/ExpenseAlert رجع صفر نتائج.

أعمدة التنبيه مفقودة من المصروفات:
- migration المصروفات (/Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_010334_create_expenses_table.php) فيه بس: project_id, category, description, amount, expense_date, payment_method, bank_account_id, notes, created_by. مفيش due_date ولا min_limit ولا alert_enabled.
- موديل Expense.php (fillable + casts) مطابق ومفيهوش أي حقل تنبيه.
- ملاحظة: due_date موجود بس في جداول invoices و taxes (مش expenses)، وده feature مختلف تماماً.

الكنترولر:
- /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ExpenseController.php مفيهوش أي توليد تنبيهات؛ بيعمل CRUD + ربط بحركة بنكية (BankLedgerService) بس.

العرض:
- index.blade.php وform.blade.php وshow.blade.php مفيهمش أي عمود/جرس تاريخ استحقاق ولا تنبيه تجاوز حد. أعمدة الجدول: التاريخ/البيان/الفئة/المشروع/المبلغ/الدفع/إجراءات فقط.

مفيش بديل عام:
- مفيش module تنبيهات/notifications/reminders (الـ grep رجع بس trait Notifiable القياسي في User.php ودوال balanceDue للمقاولين/الموردين — مالهمش علاقة).
- مفيش مفهوم budget/limit/سقف على المشاريع أو المصروفات.
- routes/console.php موجود لكن مفيهوش أي scheduled task للتنبيهات، ومفيش app/Console، والوحيد هو jobs table القياسي.

صحّحت الـ severity لـ low: الميزة فعلاً مفقودة لكنها تنبيه تجميلي/مساعد (جرس على تاريخ استحقاق + تنبيه تجاوز حد) مش منطق مالي أساسي، والوظيفة الجوهرية للمصروفات (تسجيل + ربط بنكي) شغّالة بالكامل.
- **التوصية:** إضافة أعمدة due_date/min_limit/alert_enabled لجدول expenses، وإنشاء جدول expense_alerts، وعمل مهمة/خدمة لتوليد تنبيهات الاستحقاق والمصروفات غير المسددة وعرضها (شارة/قائمة تنبيهات) للمستخدم.

### ⚪ بسيط — حقول وصفية مفقودة: details, recipient, delivered_by, reference_number, profit_margin
- **النوع:** field
- **الدليل (القديم):** في جدول expenses بـ esystem.sql: details text (السطر 2667), reference_number varchar(50) (2675), recipient varchar(100) (2677), delivered_by 'اسم المسلم' (2678), profit_margin 'نسبة الربح %' (2682). تُستخدم في النموذج والعرض بـ expenses.php (recipient/reference_number/details/profit_margin) و api/expenses.php عند الإدراج (السطور 196-223).
- **الحالة في الجديد:** الادعاء صحيح: الحقول الخمسة (details, recipient, delivered_by, reference_number, profit_margin) غير موجودة على expenses في النظام الجديد.

تحققت من:
- database/migrations/2026_06_03_010334_create_expenses_table.php — الأعمدة النصية بس description و notes، بالإضافة لـ category/amount/expense_date/payment_method/project_id/bank_account_id/created_by. مفيش أي من الخمسة.
- app/Models/Expense.php — الـ $fillable: project_id, category, description, amount, expense_date, payment_method, bank_account_id, notes, created_by. ولا واحد من الخمسة.
- app/Http/Controllers/ExpenseController.php (validateData، السطور 139-151) — بيقبل بس نفس الحقول دي، مفيش validation لأي من الخمسة.
- resources/views/expenses/{form,show,index}.blade.php — grep رجّع صفر نتائج للخمسة.

ملاحظة دفاعية مهمة (ليه مش refuted): reference_number موجود فعلاً في النظام الجديد لكن على جداول تانية بس (bank_transactions, supplier_payments, contractor_payments)، مش على expenses. و profit موجود كنوع enum في PartnerTransaction مش كحقل profit_margin (نسبة ربح %). الأربعة الباقيين (details/recipient/delivered_by/profit_margin) مش موجودين خالص كحقول expense ولا أي مكافئ.

التقييم: severity منخفض لأنها حقول وصفية/اختيارية (details و notes بيتداخلوا، recipient/delivered_by وصفي يمكن يتحط في notes، reference_number متاح على جداول المدفوعات الأدق). profit_margin هو الأهم مفاهيمياً بس مش ضروري لتشغيل سجل المصروفات الأساسي.
- **التوصية:** إضافة الأعمدة المفقودة (على الأقل reference_number و recipient/delivered_by و details) لجدول expenses وموديلها ونموذجها، حسب أهميتها لتتبع مستلم/مرجع المصروف.

### ⚪ بسيط — إحصائيات وملخصات صفحة المصروفات (مدفوع/متبقي/أعداد الحالات) منقوصة
- **النوع:** report
- **الدليل (القديم):** استعلام الملخص في /Users/mohamed/Downloads/system (2)/expenses.php السطور 86-90: total_paid, total_remaining, paid_count, partial_count, unpaid_count محسوبة حسب الآجل/المدفوع، وتلوين الصفوف حسب الحالة (السطر 509).
- **الحالة في الجديد:** الكلام صحيح، الميزة فعلاً ناقصة. بعد بحث جدّي في كل المكونات:

1) ExpenseController::index في /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ExpenseController.php السطر 42 بيحسب total = Expense::...->sum('amount') وبس. مفيش أي حساب لـ total_paid / total_remaining / paid_count / partial_count / unpaid_count.

2) جدول المصروفات /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_010334_create_expenses_table.php مفهوش أعمدة paid_amount ولا payment_status أصلاً. الأعمدة الموجودة: amount, payment_method (cash/bank_transfer/check)، يعني منظومة الدفع الجزئي/الآجل غير موجودة للمصروفات (مفيش 'credit' كطريقة دفع).

3) موديل /Users/mohamed/Downloads/qarwana/app/Models/Expense.php: PAYMENT_METHODS = cash/bank_transfer/check فقط، والـ fillable مفيهوش paid_amount أو payment_status.

4) العرض /Users/mohamed/Downloads/qarwana/resources/views/expenses/index.blade.php: كارت واحد بس ('إجمالي المصروفات') بدون مدفوع/متبقي، والجدول مفهوش أعمدة حالة دفع، ومفيش تلوين صفوف حسب الحالة (اللي كان في القديم السطر 509).

5) grep على كل app/resources/routes/database لكلمات paid_amount/payment_status/partial_count/unpaid_count/total_paid/total_remaining/'credit'/متبقي/آجل: مفيش أي نتيجة متعلقة بالمصروفات. الكلمات دي موجودة بس في Invoice وContractorExtract (paid_amount/status='partial')، وهي كيانات تانية مش المصروفات.

الخلاصة: الإحصائيات التفصيلية (مدفوع/متبقي/أعداد الحالات) وتلوين الصفوف غير موجودة في صفحة المصروفات الجديدة لأن منظومة الدفع الجزئي للمصروفات أصلاً مش متبنية. 

ملاحظة على الـ severity: خفّضتها لـ low لأن الفجوة تابعة لقرار تصميمي (إلغاء منظومة الدفع الجزئي/الآجل للمصروفات كلياً)؛ من غير الأعمدة دي في DB الإحصائيات ملهاش معنى. لكنها report gap حقيقية ومؤكدة.
- **التوصية:** بعد إضافة منظومة الدفعات، توسيع index ليعرض إجمالي المدفوع والمتبقي وعدد المصروفات حسب حالة الدفع، مع تلوين الصفوف.

---

## Materials & inventory movements (المواد والمخزون وحركاته)

> النظام الجديد (qarwana) يغطّي الأساسيات فقط: CRUD للمواد مع ربطها بالمورد والمشروع (supplier_id / project_id موجودين في migration وModel)، وحركة مخزون مبسّطة (إضافة/صرف) مع تحديث current_stock وعكس الأثر عند الحذف وقفل صفّ (lockForUpdate) ومنع الصرف بأكثر من الرصيد. لكن مقارنةً بالنظام القديم فيه نقص كبير: جدول الحركات الجديد فقد نوعَي "نقل" (transfer) و"تسوية" (adjustment)، وفقد الرصيد الجاري (stock_before/stock_after)، وفقد سعر الوحدة والقيمة الإجمالية للحركة (unit_price/total_value)، وفقد الموظف المستلِم (employee_id) ومرجع الحركة (reference_type/reference_id). كذلك اختفت تماماً جداول material_purchases (سجل مشتريات المواد) وproject_materials (استهلاك/صرف المواد على المشاريع) وجدول inventory_movements بصيغته القديمة المرتبطة بأوامر الشراء. أوامر الشراء في النظام الجديد مسطّحة بلا بنود (لا يوجد purchase_order_items ولا received_quantity ولا add_to_inventory) فلا يوجد ربط تلقائي بين استلام أمر الشراء وزيادة المخزون. ولا توجد تقارير مخزون أو تقرير نواقص (low stock) أو فلترة بالمورد/المشروع أو طباعة/تصدير. باختصار: المنطق المحاسبي للمخزون (تقييم، رصيد جاري، نقل بين مشاريع، ربط الشراء بالمخزون) غير مكتمل.

### 🟠 خطير — أوامر الشراء بلا بنود (purchase_order_items) ولا استلام جزئي/كمية مستلمة
- **النوع:** module
- **الدليل (القديم):** setup_inventory_movements.php يضيف received_quantity لـpurchase_order_items وactual_delivery وapproved_by وadd_to_inventory لـpurchase_orders؛ وesystem.sql purchase_orders (أسطر 3683-3705) فيه حالة 'partial' وdiscount_percentage/discount_amount/tax_percentage/tax_amount/net_amount/paid_amount/add_to_inventory/actual_delivery/approved_by.
- **الحالة في الجديد:** الـ gap متأكد منه بعد بحث عميق. أوامر الشراء في النظام الجديد بند واحد مسطّح بس.

الأدلة:
- migration: /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_012001_create_purchase_orders_table.php — فيها total_amount فقط، ومفيش received_quantity ولا add_to_inventory ولا actual_delivery ولا approved_by ولا discount/tax/net/paid. الحالات في التعليق: draft/pending/approved/received/cancelled (بدون partial).
- Model: /Users/mohamed/Downloads/qarwana/app/Models/PurchaseOrder.php — STATUSES بدون 'partial'، fillable فيه total_amount بس، مفيش علاقة items()/hasMany. العلاقة الوحيدة للـPO هي Supplier::purchaseOrders().
- مفيش PurchaseOrderItem.php في app/Models/ ولا migration purchase_order_items خالص.
- Controller: /Users/mohamed/Downloads/qarwana/app/Http/Controllers/PurchaseOrderController.php — validateData بيقبل الحقول المسطّحة بس، مفيش بنود ولا استلام.
- Routes: /Users/mohamed/Downloads/qarwana/routes/web.php سطر 77 Route::resource عادي بدون nested receive/items (بالمقارنة بالـinvoices اللي ليها invoices/{invoice}/items سطر 90-91).
- Views form.blade.php / show.blade.php مفيهاش حقول بنود/كمية/استلام/خصم/ضريبة.

ملاحظة مهمة: كلمة 'partial' وعلاقة items() موجودين في النظام بس للـInvoice/InvoiceItem و ContractorExtract — مش للـPurchaseOrder. يعني مفيش equivalent تحت اسم تاني؛ الميزة فعلاً ناقصة من الـrebuild.
- **التوصية:** أنشئ purchase_order_items (مادة/كمية/سعر/كمية مستلَمة) وأضف حالة partial وأعمدة الخصم/الضريبة/الصافي والاعتماد (approved_by) والتسليم الفعلي (actual_delivery) لأمر الشراء.

### 🟠 خطير — عدم تحديث المخزون تلقائياً عند استلام أمر الشراء (add_to_inventory)
- **النوع:** integration
- **الدليل (القديم):** setup_inventory_movements.php يضيف عمود add_to_inventory لـpurchase_orders وجدول inventory_movements بحركة movement_type='purchase_in' مع reference_type='purchase_order' وreference_id (esystem.sql سطر 3000-3002) لربط استلام الأمر بزيادة المخزون.
- **الحالة في الجديد:** الفجوة مؤكدة بعد بحث عدواني شامل في النظام الجديد /Users/mohamed/Downloads/qarwana.

الأدلة:
1) PurchaseOrderController.php (السطور 57-83): store/update/destroy لا تلمس المخزون نهائياً، ولا يوجد أي action مثل receive/confirm. الراوت resource عادي فقط (routes/web.php:77) من غير راوت استلام مخصص.
2) InventoryMovement.php (السطر 15-17): fillable = material_id, type, quantity, movement_date, project_id, reason, notes, created_by — لا يوجد reference_type ولا reference_id لربط الحركة بأمر الشراء. حتى الـ TYPES (سطر 10-13) بس in/out، مفيش purchase_in.
3) migration inventory_movements (2026_06_03_016002): الجدول مفيهوش أعمدة reference_type/reference_id إطلاقاً.
4) migration purchase_orders (2026_06_03_012001): مفيش عمود add_to_inventory.
5) grep على مستوى الريبو لـ add_to_inventory / reference_type / reference_id / purchase_in رجع صفر نتائج في app و resources و routes و database.
6) InventoryMovement::create موجود حصرياً في InventoryMovementController (إدخال يدوي عبر store، السطر 68) — مفيش أي مكان تاني بينشئ حركة مخزون من أمر شراء.
7) لا يوجد Observers ولا Events ولا Jobs؛ مجلد app/Services فيه BankLedgerService فقط (لا علاقة له بالمخزون). AppServiceProvider يربط PurchaseOrder بـ ActivityLog للتدقيق فقط (created/updated/deleted) مش للمخزون.
8) حالة 'received' موجودة كـ label عرض فقط (PurchaseOrder STATUSES + الـ views) ومستخدمة في Supplier.php:49 لجمع المبالغ — من غير أي trigger للمخزون.

الخلاصة: تحويل أمر الشراء لزيادة مخزون عملية يدوية منفصلة تماماً (المستخدم لازم يدخل حركة in يدوية في inventory_movements) بلا أي ربط مرجعي بأمر الشراء، تماماً زي ما الادعاء بيقول. الميزة غير موجودة.
- **التوصية:** عند تغيير حالة أمر الشراء لـreceived (أو حسب add_to_inventory) أنشئ حركة مخزون داخلة لكل بند مع reference_type='purchase_order' وreference_id، وأضف العمودين دول لـInventoryMovement.

### 🟡 متوسط — غياب نوعَي حركة 'نقل' و'تسوية' (transfer / adjustment)
- **النوع:** logic
- **الدليل (القديم):** esystem.sql جدول material_movements سطر 3314: movement_type enum('in','out','transfer','adjustment'); وملف api/material_movements.php دالة handlePost فيها case 'adjustment' (يضبط القيمة مباشرة ويحسب الفرق) إضافةً لحقلَي from_project_id / to_project_id للنقل بين المشاريع (esystem.sql أسطر 3319-3320).
- **الحالة في الجديد:** تم التأكد إن الفجوة حقيقية بعد بحث شامل في النظام الجديد. الأدلة من القديم صحيحة: esystem.sql سطر 3314 enum('in','out','transfer','adjustment') وأسطر 3319-3320 from_project_id/to_project_id. في الجديد: app/Models/InventoryMovement.php أسطر 10-13 ثابت TYPES فيه 'in' و'out' بس؛ app/Http/Controllers/InventoryMovementController.php سطر 51 validation 'in:in,out' ومنطق store (أسطر 70-72) وdestroy (أسطر 88-90) بيفرّع على in/out فقط بدون أي مسار adjustment يضبط القيمة مباشرة أو يحسب فرق، ولا أي نقل بين مشاريع؛ migration 2026_06_03_016002 سطر 14 type string(10) // in | out مع project_id واحد سطر 17 وبدون from/to_project_id. الـform (resources/views/inventory_movements/form.blade.php سطر 23) والـindex بيلفّوا على TYPES فبيعرضوا in/out بس. النتائج التانية لكلمات transfer/تسوية/adjust كلها لدومينات تانية لا علاقة لها بالمخزون: BankTransfer (تحويل بين حسابات بنكية)، PartnerTransaction settlement ('تسوية' للشركاء)، وExpense transportation. MaterialController بيتعامل مع current_stock كحقل أساسي عند الإنشاء/التعديل فقط من غير تسجيل حركة تسوية بفرق. مفيش أي equivalent لنوعَي transfer/adjustment أو حقول النقل بين المشاريع في الـrebuild. الشدة medium لأنها وظيفة مخزون مساعِدة (تسوية جرد ونقل بين مشاريع) مش core محاسبي، لكنها فعلاً غايبة.
- **التوصية:** أضف نوعَي transfer وadjustment لحقل النوع، ومعاهم منطق النقل بين مشروعين (from_project_id/to_project_id) ومنطق التسوية (ضبط الرصيد مباشرةً مع تسجيل الفرق). لازم النقل يخصم من مصدر ويضيف لهدف في نفس الـtransaction.

### 🟡 متوسط — غياب تكلفة/قيمة الحركة (unit_price / total_value) في حركات المخزون
- **النوع:** field
- **الدليل (القديم):** esystem.sql material_movements أعمدة unit_price وtotal_value (سطر 3316-3317)، وinventory_movements أعمدة unit_cost/total_cost (esystem.sql سطر 3004-3005). api/material_movements.php يحسب total_value = quantity * unit_price ويخزّنه.
- **الحالة في الجديد:** الكلام صح. الحركة في النظام الجديد مافيهاش أي سعر/قيمة على مستوى الحركة. التأكيدات: migration database/migrations/2026_06_03_016002_create_inventory_movements_table.php أعمدتها (material_id, type, quantity, movement_date, project_id, reason, notes, created_by) بس — مفيش unit_price/total_value/unit_cost/total_cost. app/Models/InventoryMovement.php سطر 15-17 fillable من غير أي سعر. app/Http/Controllers/InventoryMovementController.php دالة store (سطر 49-57) بتـvalidate الكمية بس وبتعدّل current_stock، مفيش حساب قيمة. الفورم resources/views/inventory_movements/form.blade.php مفيهوش حقل سعر/قيمة. في المقابل esystem.sql: material_movements فيها unit_price سطر 3316 وtotal_value سطر 3317، وinventory_movements فيها unit_cost سطر 3004 وtotal_cost سطر 3005. حاولت أرفض الادعاء فلقيت unit_price موجود بس على Material (سعر كتالوج واحد متغير في app/Models/Material.php) وعلى InvoiceItem (دومين الفواتير، مش حركات المخزون) — مش بديل لأن السعر التاريخي لحظة الحركة مش متسجّل، فتقييم المخزون للكمية الداخلة/الخارجة فعلاً مش متسجّل على مستوى الحركة. الـseverity عدّلتها لـmedium لأن الكمية والرصيد بيتسجّلوا صح والتقييم ممكن يتقدّر تقريبياً من Material.unit_price، بس الدقة التاريخية ضايعة.
- **التوصية:** أضف unit_price وtotal_value لحركة المخزون، واحسب القيمة تلقائياً، عشان تقدر تطلع تقييم المخزون وتكلفة الصرف على المشاريع بدقة.

### 🟡 متوسط — غياب جدول مشتريات المواد material_purchases وربط الشراء بالمخزون
- **النوع:** module
- **الدليل (القديم):** esystem.sql جدول material_purchases (سطر 3338-3351): material_id, supplier_id, project_id, quantity, unit_price, total_price, purchase_date, invoice_number. وapi/materials.php عند إضافة مادة بمورد وكمية>0 يسجّل تلقائياً في material_purchases وفي supplier_transactions.
- **الحالة في الجديد:** أكدت الـgap بعد بحث عدواني كامل. مفيش جدول material_purchases ولا Model له خالص: راجعت كل الـmigrations في /Users/mohamed/Downloads/qarwana/database/migrations وكل الـModels في /Users/mohamed/Downloads/qarwana/app/Models — مفيش material_purchases ولا أي اسم مكافئ، والـgrep على "material_purchase" رجّع صفر نتائج في app وdatabase.

أقرب حاجة موجودة هي purchase_orders (migration: 2026_06_03_012001_create_purchase_orders_table.php) بس دي header بس (order_number, supplier_id, project_id, order_date, status, total_amount, notes) من غير أي جدول purchase_order_items يمسك material_id/quantity/unit_price/total_price/invoice_number لكل مادة — يعني مش بديل عن سجل الشراء لكل مادة اللي كان في material_purchases.

MaterialController::store في /Users/mohamed/Downloads/qarwana/app/Http/Controllers/MaterialController.php (السطور 58-63) فعلاً بيعمل Material::create($data) وبس، من غير أي تسجيل عملية شراء ولا حركة مخزون ولا معاملة مورد. الـcurrent_stock مجرد حقل في الفورم بيتحفظ مباشرة.

ملاحظات تخفّف الخطورة (عشان كده medium مش critical):
1. فيه نظام مخزون فعلي منفصل: جدول inventory_movements (migration 2026_06_03_016002) + InventoryMovementController بيعمل InventoryMovement::create وبيعدّل current_stock داخل DB::transaction مع lockForUpdate وفحص رصيد وعكس الأثر عند الحذف. بس ده workflow يدوي مستقل، مش مربوط أوتوماتيك بإضافة المادة.
2. فيه supplier_payments (migration 2026_06_03_012002) للحركات المالية مع المورد، بس برضه يدوي ومش بيتسجّل أوتوماتيك عند الشراء، ومفيش جدول supplier_transactions بالمعنى اللي في الـlegacy.

الخلاصة: الـjsl material_purchases والربط الأوتوماتيكي (إضافة مادة بمورد وكمية>0 يسجّل تلقائياً في material_purchases وsupplier_transactions زي api/materials.php القديم) غايبين فعلاً. الدومين الوظيفي متغطي بـworkflows يدوية منفصلة لكن السلوك الأوتوماتيكي والجدول المحدد مش موجودين.
- **التوصية:** أنشئ جدول/موديل MaterialPurchase وربطه بالمادة والمورد والمشروع ورقم الفاتورة، واربطه آلياً: عند إضافة مادة برصيد ابتدائي من مورد يتسجّل شراء + حركة مخزون داخلة + معاملة على حساب المورد (زي القديم).

### 🟡 متوسط — غياب تقرير المخزون وتقرير النواقص (low stock) ولوحة الإحصائيات
- **النوع:** report
- **الدليل (القديم):** materials.php سطر 328 يحسب $low_stock (المواد اللي current_stock <= min_stock) ويعرض عددها كـstat (سطر 365) مع badges تحذير (أسطر 518-522).
- **الحالة في الجديد:** تم التأكد بعد بحث شامل: الميزة المجمّعة فعلاً ناقصة. (1) ReportController.php (/Users/mohamed/Downloads/qarwana/app/Http/Controllers/ReportController.php) بيتعامل بس مع إيرادات/مصروفات/ربحية المشاريع — صفر ذكر للمواد أو المخزون. (2) DashboardController.php سطور 23-35 فيه stats لكل حاجة (إيرادات، بنوك، مشاريع، عملاء، مقاولين، موردين، موظفين، فواتير غير مدفوعة) بس مفيش عدّاد للمواد ولا للنواقص (low stock)، وdashboard.blade.php grep رجع 0 للمواد/المخزون. (3) MaterialController::index بيفلتر بالاسم والتصنيف فقط — مفيش فلتر 'أصناف تحت الحد'. (4) اللي موجود فعلاً: badge على مستوى الصف بس في materials/index.blade.php سطر 47-49 وmaterials/show.blade.php سطر 27 عن طريق current_stock <= min_stock مباشرة. (5) Material model مفيهوش scope أو accessor لـ lowStock. (6) routes/web.php فيه reports.index (مالي) وresource materials عادي بس — مفيش route لتقرير مخزون/نواقص. يعني النظام القديم كان بيجمّع عدد النواقص كـstat مع تحذيرات، الجديد سقّط التجميع والفلتر والإحصائية وساب الـbadge للصف بس. خفّضت الـseverity لـmedium لأن منطق النقص نفسه (المقارنة + الـbadge) موجود ومتطبّق في صفحتين، فاللي ناقص هو التجميع/التقرير/الفلتر مش الميزة من الصفر.
- **التوصية:** أضف تقرير مخزون (قيمة المخزون الإجمالية + قائمة الأصناف تحت الحد الأدنى) وفلتر low-stock في صفحة المواد، ويُفضّل كارت إحصائي بعدد النواقص.

### 🟡 متوسط — غياب فلاتر المورد/المشروع في قائمة المواد
- **النوع:** ui
- **الدليل (القديم):** api/materials.php يدعم فلترة project_id (incl. 'general'/0 = مخزون عام) وsupplier_id (incl. 0 = بدون مورد) وcategory؛ وmaterials.php سطر 23 فيه filter_project_id وقائمة allProjectsForFilter للفلترة بالمشروع.
- **الحالة في الجديد:** الـ gap مؤكد بعد بحث كامل. MaterialController::index (الأسطر 26-40) في /Users/mohamed/Downloads/qarwana/app/Http/Controllers/MaterialController.php بيفلتر بالاسم (search) والـ category بس — مفيش أي فلتر project_id ولا supplier_id ولا تمييز 'مخزون عام'. الـ project_id/supplier_id في الأسطر 99-100 موجودين في validateData لـ store/update بس، مش في الفلترة. والـ view /Users/mohamed/Downloads/qarwana/resources/views/materials/index.blade.php (الأسطر 9-18) فيه input بحث وselect تصنيف فقط. الراوت Route::resource('materials',...) عادي من غير listing بديل. مفيش model scopes ولا أي ذكر لـ filter_project / allProjectsForFilter / 'مخزون عام' / general في الـ app أو الـ views. مهم: الـ schema بيدعم الميزة فعليًا — migration 2026_06_03_011001_create_materials_table.php الأسطر 16-17 فيها project_id و supplier_id كـ nullable foreign keys (الـ nullable = دلالة 'مخزون عام'/'بدون مورد')، و Material::$fillable فيه الاتنين. يعني الداتا موجودة بس مش متعرّضة كفلتر في القائمة. التقييم medium مش high لأن الفلترة الأساسية (اسم + تصنيف) شغالة وده تحسين UI مش غياب وظيفة جوهرية.
- **التوصية:** أضف فلتر بالمشروع (مع خيار المخزون العام) وفلتر بالمورد في قائمة المواد زي القديم.

### 🟡 متوسط — غياب الموظف المستلِم وسبب الحركة عند الصرف (employee_id)
- **النوع:** field
- **الدليل (القديم):** esystem.sql material_movements عمود employee_id 'الموظف المستلم' (سطر 3321)، وmaterials.php سطر 70 يجلب الموظفين النشطين للسحب، وapi/material_movements.php يحفظ employee_id في الحركة.
- **الحالة في الجديد:** تم التحقق بعناية والادعاء صحيح جزئياً مع تصحيح مهم. النظام القديم (esystem.sql سطر 3321) فعلاً فيه material_movements.employee_id COMMENT 'الموظف المستلم'، وده مختلف عن created_by (سطر 3328 'المستخدم المنشئ').

في النظام الجديد:
- /Users/mohamed/Downloads/qarwana/app/Models/InventoryMovement.php سطر 15-17: fillable = material_id, type, quantity, movement_date, project_id, reason, notes, created_by — مفيش employee_id ولا أي حقل مستلِم.
- /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_016002_create_inventory_movements_table.php: الأعمدة هي material_id, type, quantity, movement_date, project_id, reason, notes, created_by فقط — لا employee_id ولا received_by/recipient.
- /Users/mohamed/Downloads/qarwana/app/Http/Controllers/InventoryMovementController.php سطر 49-58: الـ store() بيتحقق ويحفظ فقط الحقول دي ويحط created_by = المستخدم الحالي، مفيش حقل موظف.
- /Users/mohamed/Downloads/qarwana/resources/views/inventory_movements/form.blade.php: الفورم فيها المادة/النوع/الكمية/التاريخ/المشروع/السبب/ملاحظات بس — لا dropdown للموظفين.
- موديل Employee.php مفيهوش أي علاقة بـ InventoryMovement، ومفيش جدول وسيط ولا أعمدة received_by/recipient/receiver في أي مكان.

تصحيح للادعاء: جزء 'سبب الحركة' (reason) موجود فعلاً في النظام الجديد (المايجريشن سطر 18، الفورم سطر 46-47)، فالادعاء بإن reason غير كافٍ غير دقيق. الناقص فعلياً هو تسجيل الموظف المستلِم للمادة المصروفة (employee_id) — ده غائب تماماً ومش متعوّض بأي بديل. الخطورة medium لأنها بيانات تتبّع/مساءلة للعهدة وليست عطل وظيفي يكسر الصرف.
- **التوصية:** أضف employee_id (الموظف المستلم) لحركة الصرف مع علاقة belongsTo(Employee) عشان تتبّع مسؤولية المواد المصروفة.

### ⚪ بسيط — فقدان الرصيد الجاري (stock_before / stock_after) في كل حركة
- **النوع:** field
- **الدليل (القديم):** esystem.sql جدول material_movements أعمدة stock_before وstock_after (سطر 3326-3327)، وapi/material_movements.php يحفظ current_stock كـstock_before والرصيد الجديد كـstock_after في كل INSERT، وmaterials.php سطر 1627-1628 يعرضهما في جدول الحركات.
- **الحالة في الجديد:** تأكد الفقدان بعد بحث جدّي. literal grep لـ stock_before/stock_after في كل المشروع رجع صفر نتائج. التفاصيل:
- Migration: /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_016002_create_inventory_movements_table.php — أعمدة الجدول id, material_id, type, quantity, movement_date, project_id, reason, notes, created_by بس. مفيش stock_before/stock_after.
- Model: /Users/mohamed/Downloads/qarwana/app/Models/InventoryMovement.php — الـ$fillable مفيهوش أي عمود رصيد جاري.
- View: /Users/mohamed/Downloads/qarwana/resources/views/inventory_movements/index.blade.php (سطر 24) — الأعمدة: التاريخ/المادة/النوع/الكمية/المشروع/السبب. مفيش عمود رصيد قبل/بعد ولا رصيد جارٍ.
- Controller: /Users/mohamed/Downloads/qarwana/app/Http/Controllers/InventoryMovementController.php — بيحدّث Material.current_stock (رصيد إجمالي حي واحد) عبر bcadd/bcsub جوه DB transaction مع lockForUpdate، لكن عمره ما بيسجّل ولا بيعرض الرصيد قبل/بعد لكل حركة.

لكن أهم نقطة للتخفيف من الـseverity: ده قرار معماري مقصود مش سهو. الـ BankLedgerService.php (سطر 10-17, 68-89) بيوضّح إن الفريق رفض عمداً نمط balance_after المخزّن لأنه «كان بيتكسر مع ترتيب التواريخ»، وبيحسب الرصيد الجاري (running) لحظة العرض في statement() بدل تخزينه. يعني النظام الجديد عنده الكفاءة والـpattern الجاهز لاشتقاق رصيد جارٍ من الحركات — البيانات كلها موجودة (movement_date + type + quantity). الناقص هو مجرد اشتقاق/عرض الرصيد الجاري في جدول حركات المخزون (نفس فكرة statement() لكن للمواد)، مش فقدان بيانات. عشان كده الـseverity الحقيقي low: feature عرضي ينفع يتضاف بسهولة بنفس النمط الموجود، مش فقدان حرج للسلامة المالية.
- **التوصية:** أضف عمودَي stock_before وstock_after للجدول واحفظهما داخل transaction الـstore، واعرض الرصيد الجاري في كشف حركة المادة عشان يبقى فيه أثر تدقيقي (audit trail) للمخزون.

### ⚪ بسيط — غياب custom_category (فئة مخصّصة) وفصل المعدات عن المواد
- **النوع:** field
- **الدليل (القديم):** esystem.sql materials عمود custom_category varchar(100) (سطر 3295)، وmaterials.php يفصل المواد (category IN cement/steel/wood/tools/other) عن المعدات (category='equipment') في تبويبين (أسطر 34-58) مع نماذج إدخال مختلفة.
- **الحالة في الجديد:** تم التحقق والـ gap حقيقي. اللي موجود في النظام الجديد:

1) Migration: /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_011001_create_materials_table.php سطر 19 — في عمود category varchar(20) ثابت بس (cement/steel/wood/equipment/tools/other)، مفيش custom_category خالص.

2) Model: /Users/mohamed/Downloads/qarwana/app/Models/Material.php أسطر 10-13 الـ fillable مافيهوش custom_category. والـ CATEGORIES const (أسطر 24-31) فيها 'equipment' => 'معدات' لكن كقيمة من قيم category الثابتة مش كفيلد منفصل.

3) Controller: /Users/mohamed/Downloads/qarwana/app/Http/Controllers/MaterialController.php سطر 94 — validation بتجبر category تكون in:cement,steel,wood,equipment,tools,other ومش بتقبل أي فئة مخصصة نصية.

4) Views: materials/index.blade.php جدول واحد بفلتر تصنيف واحد فقط، بلا تبويب معدات منفصل. وmaterials/form.blade.php (أسطر 17-24) select تصنيف واحد، مفيش حقل نصي لـ'أخرى' ولا نموذج إدخال مختلف للمعدات.

بحث grep على custom_category في app/resources/routes/database رجع صفر نتائج في كود التطبيق (المطابقات الوحيدة في تعليقات بتعدّد قيمة equipment). مفيش تسمية بديلة (subcategory/sub_category/material_type) ولا Equipment model/table منفصل ولا nav-tabs.

تأكيد legacy: /Users/mohamed/Downloads/esystem.sql سطر 3295 فيه custom_category varchar(100) في جدول materials.

الـ severity صححتها لـ low: custom_category كان مجرد حقل نصي حر للحالة 'أخرى'، وفصل المعدات اتحوّل لقيمة category واحدة ضمن ستة — وظيفياً قابل للتعويض جزئياً، وضياع بيانات الفئة المخصصة الحرة بس هو الأثر الحقيقي.
- **التوصية:** أضف عمود custom_category للمواد (يظهر لما الفئة = أخرى)، ويُفضّل تبويب/فلتر منفصل للمعدات زي القديم لو الفصل ده مطلوب للأعمال.

### ⚪ بسيط — غياب موقع المخزن (warehouse_location) في حركات المخزون
- **النوع:** field
- **الدليل (القديم):** esystem.sql جدول inventory_movements عمود warehouse_location varchar(100) (سطر 3007) لتحديد موقع المخزن في الحركة.
- **الحالة في الجديد:** بحثت بجدية ومفيش أي أثر للحقل أو لأي مكافئ ليه.

- migration: /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_016002_create_inventory_movements_table.php — الأعمدة هي material_id, type, quantity, movement_date, project_id, reason, notes, created_by فقط. مفيش warehouse_location.
- model: /Users/mohamed/Downloads/qarwana/app/Models/InventoryMovement.php — الـ $fillable مفيهوش warehouse_location.
- grep على "warehouse_location" في كل المشروع رجع صفر نتايج.
- grep واسع على warehouse/location/مخزن/store: حقول location موجودة بس على جداول غير ذات صلة (projects و assets فقط، في /database/migrations/2026_06_03_004037_create_projects_table.php و .../011002_create_assets_table.php). مفيش جدول warehouses ولا model للمخازن.
- materials table (/database/migrations/2026_06_03_011001_create_materials_table.php) بتتبّع current_stock واحد للمادة من غير أي بُعد للموقع/المخزن.
- view النموذج /resources/views/inventory_movements/form.blade.php مفيهوش أي input لموقع المخزن.

الخلاصة: الحقل warehouse_location غايب فعلاً ومفيش تتبّع لمواقع تخزين متعددة في النظام الجديد. لكن الخطورة منخفضة: النظام بيتعامل مع رصيد مخزون مفرد لكل مادة (single-stock)، فمفيش بنية أصلاً لمخازن متعددة، وده غالباً تبسيط مقصود مش فقدان وظيفة حرجة.
- **التوصية:** لو الشركة عندها أكتر من مخزن، أضف warehouse_location (أو جدول مخازن منفصل) للحركة عشان تتبّع المواد لكل موقع.

---

## الإيرادات والتحصيلات والفواتير والضرائب (Revenues, Collections, Invoices, Taxes)

> النظام الجديد يغطي شكل مبسّط جداً من الدومين ده وفيه فجوات جوهرية. جدول revenues القديم فيه 24 عمود (منها paid_amount, remaining_amount, payment_status, due_date, deferred_check, check_number, bank_name, attachment_file, is_confirmed, source, extract_type, details) لكن الجدول الجديد اتعمل ب 8 أعمدة بس (project_id, description, amount, revenue_date, payment_method, bank_account_id, notes, created_by) — يعني التحصيل الجزئي للإيرادات (الشيكات الآجلة) اتشال بالكامل. جدول revenue_collections القديم اللي بيتابع تحصيلات الإيراد على دفعات (مع API كامل بيحدّث paid/remaining/status) مش موجود نهائياً (لا جدول ولا موديل ولا كنترولر). الفواتير: النظام الجديد حافظ على عمودي paid_amount/status بس مفيش أي آلية لتسجيل دفعة على الفاتورة (مفيش جدول payments ولا UI ولا انتقال حالة تلقائي ولا طباعة فاتورة). الضرائب: الصفحة القديمة كانت تقرير تجميعي تلقائي (ضرائب مدخلات/مخرجات وصافي مستحق) والنظام الجديد بقى CRUD يدوي بجدول taxes جديد فقدت معاه منطق التجميع التلقائي بالكامل. الخلاصة: الدومين ده من أكثر الدومينات نقصاً وبيأثر مباشرة على متابعة التدفق النقدي للشركة.

### 🟠 خطير — تتبّع تحصيلات الإيراد على دفعات (revenue_collections) مفقود بالكامل
- **النوع:** module
- **الدليل (القديم):** esystem.sql سطر 3869: CREATE TABLE `revenue_collections` (revenue_id, amount, collection_date, payment_method, reference_number, notes, created_by). و API كامل في /Users/mohamed/Downloads/system (2)/api/revenue_collections.php (GET/POST/DELETE) بيضيف تحصيل ويعيد حساب paid_amount و remaining_amount و payment_status للإيراد داخل transaction. وواجهة التحصيل في revenues.php (modal collectRevenueModal + دالة collectRevenue + collectRevenueForm).
- **الحالة في الجديد:** أكدت الـ gap بعد بحث معمّق في كل الطبقات. مفيش جدول revenue_collections ولا أي بديل:

1) Migrations (32 ملف): مفيش revenue_collections ولا أي جدول collection/installment/تحصيل/دفعات. الإيراد متعرّف في /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_010334_create_revenues_table.php كسجل مسطّح (amount واحد + payment_method واحد + bank_account_id) من غير أي حقول paid_amount / remaining_amount / payment_status. يعني الإيراد بيتسجّل مرة واحدة كامل، مفيش مفهوم دفعات جزئية أصلاً.

2) Model /Users/mohamed/Downloads/qarwana/app/Models/Revenue.php: مفيش علاقة hasMany لتحصيلات، ومفيش حقول paid/remaining/status. (ملاحظة: الـ grep الأولي على paid_amount|remaining_amount|payment_status ظهر فيه ملف Revenue كنتيجة كاذبة بسبب تطابق كلمة payment_method فقط — أكّدت بـ grep -n إن الكلمات دي مش موجودة فعلاً).

3) Controller /Users/mohamed/Downloads/qarwana/app/Http/Controllers/RevenueController.php: CRUD قياسي بس (index/show/create/store/edit/update/destroy). مفيش أي دالة collect ولا أي إعادة احتساب لـ paid/remaining. الـ store بيعمل إيداع بنكي واحد بكامل المبلغ عبر BankLedgerService.

4) Routes /Users/mohamed/Downloads/qarwana/routes/web.php سطر 66: Route::resource('revenues', ...) من غير أي action إضافي للتحصيل.

5) Views /Users/mohamed/Downloads/qarwana/resources/views/revenues/ (form/index/show): مفيش أي modal تحصيل ولا كلمة collect/تحصيل/دفعات.

6) grep على revenue_collection|RevenueCollection|collectRevenue|collect_revenue في app/database/routes/resources رجع فاضي تماماً.

أقرب نمط مشابه في النظام (supplier_payments / contractor_payments) موجود للموردين والمقاولين لكنه مش متطبّق على الإيرادات إطلاقاً، فمش بديل وظيفي. الخلاصة: ميزة تتبّع تحصيلات الإيراد على دفعات (وحساب المتبقي وحالة السداد) مفقودة بالكامل من النظام الجديد. خفّضت الـ severity من critical لـ high لأن النظام بيسجّل الإيراد الكامل صح ماليًا (الرصيد البنكي سليم)، اللي ناقص هو تتبّع الدفعات الجزئية والمتبقي، وده مهم بس مش مدمّر للبيانات المالية الأساسية.
- **التوصية:** إنشاء جدول revenue_collections + موديل RevenueCollection (hasMany من Revenue) + مسار/كنترولر لتسجيل تحصيل جزئي يعيد حساب paid_amount/remaining_amount/payment_status داخل DB::transaction زي الـ legacy، مع واجهة modal لتسجيل التحصيل وعرض سجل التحصيلات في صفحة الإيراد.

### 🟠 خطير — أعمدة الإيراد الأساسية للتحصيل والشيكات الآجلة محذوفة من جدول revenues
- **النوع:** field
- **الدليل (القديم):** esystem.sql سطر 3823 جدول revenues فيه: paid_amount, remaining_amount, payment_status enum('paid','partial','unpaid','pending'), due_date (تاريخ استحقاق الشيك الآجل), check_number, bank_name, custom_payment_method, reference_number, is_confirmed, source enum('extract','direct','retention_release','other'), extract_type enum('initial','progress','final','retention','variation','other'), details, attachment_file. والـ payment_method فيه deferred_check.
- **الحالة في الجديد:** الادعاء صحيح. جدول revenues الجديد في /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_010334_create_revenues_table.php فيه 8 أعمدة بس: project_id, description, amount, revenue_date, payment_method, bank_account_id, notes, created_by. مفيش paid_amount ولا remaining_amount ولا payment_status ولا due_date ولا check_number ولا bank_name ولا custom_payment_method ولا reference_number ولا is_confirmed ولا source ولا extract_type ولا details ولا attachment_file. و app/Models/Revenue.php الـ PAYMENT_METHODS محصورة في cash/bank_transfer/check من غير deferred_check، والـ RevenueController validateData() بيتحقق من نفس الـ 8 حقول بس، والفورم بيعرض نفس طرق الدفع. دوّرت على بدائل في النظام كله: invoices فيها paid_amount + due_date + status(partial/paid/overdue) لكن دي كيان منفصل (فواتير عملاء) ومفيهاش أعمدة الشيك الآجل (check_number/bank_name/attachment). bank_name موجود بس على bank_accounts (حسابات الشركة نفسها مش بنك مُصدِّر الشيك). reference_number موجود على bank_transactions/supplier_payments/contractor_payments مش على revenues. مفيش جدول/موديل payments أو collections أو installments. مفيش أي ذكر لـ deferred أو check_number أو attachment_file أو is_confirmed أو extract_type أو retention_release في أي migration. تتبّع الشيكات الآجلة والتحصيل الجزئي على الإيراد فعلاً مفقود. خفّضت الخطورة من critical لـ high لأن invoices و contractor_extracts بيغطوا جزء من مفهوم التحصيل الجزئي والـ bank_transactions بتسجّل reference_number للشيكات فالنظام يقدر يشتغل لكن قدرة كيان الإيراد على الشيك الآجل والتحصيل الجزئي اتفقدت.
- **التوصية:** إضافة الأعمدة الناقصة لجدول revenues (paid_amount, remaining_amount, payment_status, due_date, check_number, bank_name, custom_payment_method, reference_number, is_confirmed, source, extract_type, details, attachment_file) وإضافة deferred_check لطرق الدفع، وتحديث fillable/casts في الموديل وقواعد التحقق في RevenueController::validateData.

### 🟠 خطير — تسجيل دفعات الفواتير (payments) وانتقال حالة الفاتورة غير موجود
- **النوع:** logic
- **الدليل (القديم):** esystem.sql سطر 3505: CREATE TABLE `payments` (invoice_id, payment_date, amount, payment_method enum('cash','bank_transfer','check','credit'), reference_number, notes, created_by). وجدول invoices فيه paid_amount + status enum فيها 'partial' و 'overdue'. invoices.php بيعرض المحصّل والمتبقي ويفلتر بالحالة.
- **الحالة في الجديد:** أكدت الـ gap بعد بحث عدائي كامل في كل الطبقات. لقيت إن دفعات الموردين والمقاولين موجودة (supplier_payments / contractor_payments) لكن دفعات الفواتير (invoice payments) مش موجودة خالص:

1) Migrations: مفيش migration اسمه payments. الموجود بس supplier_payments و contractor_payments (database/migrations/2026_06_03_012002 و 2026_06_03_013002). جدول invoices (2026_06_03_015001_create_invoices_table.php) فيه فعلاً عمود paid_amount default 0 وعمود status فيه partial/overdue/paid، بس مفيش أي آلية لملئهم.

2) Models: مفيش app/Models/Payment.php (ls رجع No such file). الموجود SupplierPayment.php و ContractorPayment.php بس. وأهم حاجة: app/Models/Invoice.php مافيهوش علاقة payments() ولا أي method لتسجيل دفعة. الـ method الوحيدة recomputeTotals() بتحسب subtotal/tax/total من البنود وبتعمل save بس عمراها ما بتلمس paid_amount.

3) Controller: app/Http/Controllers/InvoiceController.php فيه CRUD قياسي بس (index/create/store/show/edit/update/destroy) ومفيش أي دالة pay/recordPayment ولا أي تحديث لـ paid_amount أو status. الـ grep على paid_amount في كل app/resources/routes رجع بس السطرين بتوع fillable و casts في الموديل — يعني العمود بيتقري ويتكتب نظرياً لكن محدش بيكتب فيه.

4) Routes: routes/web.php سطر 89-91 فيه Route::resource('invoices') + إضافة/حذف بنود الفاتورة بس. مفيش route لتسجيل دفعة على الفاتورة (زي invoices/{invoice}/payments).

5) Views: resources/views/invoices/show.blade.php بيعرض المجموع الفرعي والضريبة والإجمالي بس — مفيش عرض للمحصّل (paid_amount) ولا المتبقي (remaining)، ومفيش زر تسجيل دفعة؛ الفورمات الوحيدة هي إضافة/حذف بند. و invoices/index.blade.php بيعرض total_amount بس (سطر 39) مفيش أعمدة محصّل/متبقي، والـ badge بتاع status (سطر 33) بيعرض partial/overdue كحالات لكن مفيش أي كود بيوصّل الفاتورة للحالات دي.

6) مفيش مسار بديل: Revenue model و revenues migration مفيهومش أي ربط بـ invoice (grep رجع فاضي)، فالإيرادات مش بتُستخدم كآلية تحصيل للفواتير.

الخلاصة: الـ rebuild ناقل بنية invoices (paid_amount + status enum) لكن من غير أي logic لتسجيل الدفعات أو تحديث المحصّل/المتبقي أو نقل حالة الفاتورة لـ partial/paid/overdue. الميزة فعلاً غايبة. خفّضت الـ severity من critical المحتملة لـ high لأن جدول الفواتير نفسه شغال (إنشاء/بنود/إجماليات/ضريبة) والناقص هو التحصيل بس مش المنظومة كلها.
- **التوصية:** إنشاء جدول/موديل Payment مرتبط بالفاتورة + مسار وكنترولر لتسجيل دفعة يزوّد paid_amount وينقل status تلقائياً (sent→partial→paid، و overdue حسب due_date) داخل transaction، وإضافة واجهة تسجيل الدفعات وعرض سجلها في صفحة الفاتورة.

### 🟠 خطير — تقرير الضرائب التجميعي التلقائي (input/output VAT وصافي المستحق) مفقود
- **النوع:** report
- **الدليل (القديم):** /Users/mohamed/Downloads/system (2)/taxes.php: صفحة تجميعية بتحسب ضرائب أوامر الشراء (purchase_orders.tax_amount) + ضرائب الفواتير الصادرة (invoices.tax_amount) + خصومات الموردين (supplier_payments: vat, insurance_5_percent, social_insurance, commercial_profit_supply/works, engineering/arts/applied_professions...) وتطلع الضرائب المدفوعة (مدخلات) والمحصلة (مخرجات) وصافي الضريبة المستحقة/القابلة للاسترداد، مع فلترة بالتاريخ والمشروع وطباعة.
- **الحالة في الجديد:** مؤكَّد: الفيتشر ناقص فعلاً بعد بحث جدّي ومحاولة دحض. الصفحة القديمة /Users/mohamed/Downloads/system (2)/taxes.php تقرير تجميعي: بيحسب total_input_tax = ضرائب أوامر الشراء (po.tax_amount) + خصومات الموردين، و total_output_tax = ضرائب الفواتير (i.tax_amount)، و net_tax_payable = output − input، مع تفصيل خصومات الموردين (vat, insurance_5_percent, social_insurance, commercial_profit_supply/works, engineering_professions, arts_specialists, applied_professions, other_deductions, total_deductions) وفلترة بالتاريخ والمشروع وطباعة (سطور 36-130 + الكروت bg-input/bg-output/bg-net-payable/bg-net-refund).

في النظام الجديد qarwana:
- /Users/mohamed/Downloads/qarwana/app/Http/Controllers/TaxController.php: CRUD يدوي بحت على جدول taxes، مفيش أي تجميع.
- /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ReportController.php: بيجمّع الإيرادات مقابل المصروفات فقط (net = revenue − expense)، مفيش أي منطق ضرائب/VAT input vs output.
- /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_012001_create_purchase_orders_table.php: مفيهوش عمود tax_amount/tax_percentage/net_amount إطلاقاً (بس total_amount) — يعني مصدر بيانات ضريبة أوامر الشراء أصلاً مش موجود.
- /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_012002_create_supplier_payments_table.php: مفيهوش أي من أعمدة الخصومات القديمة (vat/insurance_5_percent/social_insurance/commercial_profit_*/engineering_professions/arts_specialists/applied_professions/other_deductions/total_deductions) — بس amount و payment_method. يعني حتى الداتا اللي التقرير بيتجمّع منها اختفت من الـ schema.
- grep شامل على app/ و resources/ و routes/web.php مالقاش أي input/output VAT ولا net tax payable ولا مفاهيم (مدخلات/مخرجات/محصلة/مستحق للحكومة/استرداد ضريبي).
- موديل Invoice فيه tax_amount لكنه مش بيتجمّع لأي تقرير ضريبي.

ملاحظة على ادعاء الـ esystem.sql: الادعاء قال مفيش CREATE TABLE taxes، لكن النظام الجديد فعلاً عنده migration بينشئ جدول taxes (2026_06_03_011004_create_taxes_table.php) بأعمدة name/tax_type/rate/base_amount/amount/period/due_date/status — بس ده جدول إدخال يدوي مش تقرير تجميعي، فمش بيدحض الجاب. الخلاصة: التقرير الضريبي التجميعي التلقائي (input/output VAT + صافي مستحق/استرداد) مفقود بالكامل.
- **التوصية:** إضافة صفحة/تقرير تجميعي للضرائب (يفضل ReportController) يجمّع tax_amount من invoices و purchase_orders وخصومات supplier_payments ويحسب صافي الضريبة المستحقة مع فلترة بالتاريخ/المشروع وطباعة، مع الإبقاء على الـ CRUD الجديد كسجل ضرائب يدوي تكميلي.

### 🟡 متوسط — ربط تحصيل الإيراد بالحساب البنكي عند التحصيل الجزئي غير مدعوم
- **النوع:** integration
- **الدليل (القديم):** api/revenues.php (سطور ~236-267): عند إضافة إيراد بطريقة bank_transfer بيعمل INSERT INTO bank_transactions و UPDATE bank_accounts SET current_balance = current_balance + ?. وعند التعديل/الحذف بيعكس القيد. التحصيلات الجزئية في revenue_collections بتغذّي paid_amount اللي بيمثّل المتحصّل فعلياً.
- **الحالة في الجديد:** الـ gap مؤكد بعد بحث جدّي. النظام القديم (/Users/mohamed/Downloads/system (2)/api/revenues.php) فيه: طريقة دفع deferred_check مع paid_amount/remaining_amount/payment_status (سطور 178-194)، وجدول revenue_collections عبر getRevenueCollections() (سطور 136-150)، وإيداع بنكي في bank_transactions + تحديث current_balance (سطور 236-267).

النظام الجديد في /Users/mohamed/Downloads/qarwana:
- app/Models/Revenue.php: طرق الدفع cash/bank_transfer/check فقط — مفيش deferred_check. الحقول المتاحة بس project_id, description, amount, revenue_date, payment_method, bank_account_id, notes, created_by. مفيش paid_amount ولا remaining_amount ولا payment_status ولا due_date ولا check_number.
- database/migrations/2026_06_03_010334_create_revenues_table.php: السكيمة مفيهاش أي أعمدة تحصيل جزئي ولا مفهوم collections.
- app/Http/Controllers/RevenueController.php (سطور 99-115): syncBankTransaction بيسجّل إيداع بقيمة $revenue->amount الكاملة فوراً عند store/update عبر BankLedgerService — مفيش تسجيل للجزء المتحصّل فعلياً.
- مفيش جدول revenue_collections ولا موديل RevenueCollection ولا migration باسم cheque/check/install/collect. قائمة الموديلات مفيهاش Cheque/Collection.
- routes/web.php سطر 66 فيه Route::resource('revenues') بس — مفيش route للتحصيلات.

نتايج grep لـ partial/cheque/paid_amount في النظام الجديد كلها تخص features تانية (Invoice/ContractorExtract/SupplierPayment) مش تحصيل الإيرادات. خفّضت الخطورة لـ medium لأن الإيداع الكامل بيتسجّل صح للدفع النقدي/التحويلي العادي؛ المشكلة محصورة في الشيكات الآجلة والتحصيل الجزئي.
- **التوصية:** عند بناء وحدة revenue_collections، ربط كل تحصيل جزئي بإيداع بنكي مستقل عبر BankLedgerService بتاريخ ومبلغ التحصيل، بدل تسجيل الإيراد كامل وقت الإنشاء لما يكون شيك آجل.

### 🟡 متوسط — فلاتر صفحة الإيرادات وإحصائيات التحصيل والتصدير ناقصة
- **النوع:** ui
- **الدليل (القديم):** revenues.php: فلترة بالمشروع + من/إلى تاريخ + حالة التحصيل (paid/pending/partial) + نوع المستخلص (initial/progress/final/retention/variation/other)، و6 كروت إحصائية (إجمالي/محصّل/قيد الانتظار/مؤكد/شيكات آجلة)، وزر تصدير Excel، وعرض المرفقات وأرقام الشيكات.
- **الحالة في الجديد:** الكلام صحيح - الميزة فعلاً ناقصة بعد بحث عميق ومحاولة دحض.

RevenueController::index (/Users/mohamed/Downloads/qarwana/app/Http/Controllers/RevenueController.php سطر 31-41): مفيش أي فلترة. الميثود مابتاخدش Request أصلاً، بتعمل Revenue::query()->with(['project','bankAccount'])->latest('revenue_date')->paginate(15) و $total = Revenue::sum('amount') وبس. كل where() في الملف خاصة بمزامنة الـ BankTransaction مش بفلترة العرض.

revenues/index.blade.php (/Users/mohamed/Downloads/qarwana/resources/views/revenues/index.blade.php): كارت إحصائي واحد فقط (إجمالي الإيرادات)، مفيش فورم فلاتر، مفيش زر تصدير، مفيش عمود حالة تحصيل/مرفقات/أرقام شيكات. عمود الاستلام بيعرض طريقة الدفع (نقدي/تحويل/شيك) مش حالة تحصيل.

نقص على مستوى الداتا: migration (/Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_010334_create_revenues_table.php) و Model (/Users/mohamed/Downloads/qarwana/app/Models/Revenue.php) مفيهمش حقل status/حالة تحصيل ولا نوع مستخلص ولا رقم شيك ولا مرفقات. Revenue::PAYMENT_METHODS فيها cash/bank_transfer/check بس.

محاولة الدحض: مفاهيم paid/partial/pending وأنواع المستخلصات اللي ظهرت في grep موجودة في Invoice و ContractorExtract - دول features منفصلة مش مكافئة لتتبع التحصيل بتاع revenues.php القديم.

التصدير: مفيش Excel/CSV/PDF نهائياً. composer.json مافيهوش maatwebsite/excel ولا dompdf ولا أي export. routes/web.php سطر 66 مجرد Route::resource('revenues') من غير route تصدير. الـ download routes الوحيدة بتاعة ProjectFiles ومالهاش علاقة بالإيرادات.

ملاحظة على الـ severity: الفلاتر والإحصائيات والتصدير ميزة UI/تشغيلية مهمة لكنها مش حرجة بمعنى فقدان داتا أو ثغرة أمان - فخفّضتها لـ medium بدل high.
- **التوصية:** إضافة فلاتر (مشروع/تاريخ/حالة تحصيل/نوع مستخلص) وكروت إحصائية للمحصّل والمتبقي وعدد الشيكات الآجلة، وزر تصدير، وعرض المرفقات وأرقام الشيكات في جدول الإيرادات.

### 🟡 متوسط — طباعة الفاتورة (print_invoice) ووجهة الفاتورة القابلة للطباعة غير موجودة
- **النوع:** ui
- **الدليل (القديم):** invoices.php سطر 237-238 و545-546: زر طباعة لكل فاتورة + دالة printInvoice بتفتح print_invoice.php?id= (وجهة طباعة كاملة للفاتورة بالبنود).
- **الحالة في الجديد:** الفجوة مؤكدة فعلاً بعد بحث جاد. مفيش أي زر طباعة أو وجهة فاتورة قابلة للطباعة في النظام الجديد:

- /Users/mohamed/Downloads/qarwana/resources/views/invoices/show.blade.php: فيه بس لينك "رجوع للفواتير"، مفيش زر print/طباعة/export/PDF.
- /Users/mohamed/Downloads/qarwana/resources/views/invoices/index.blade.php: أزرار كل فاتورة هي عرض (eye) + تعديل (pen) + حذف (trash) بس، مفيش زر طباعة (عكس invoices.php القديم اللي كان فيه زر طباعة لكل فاتورة).
- /Users/mohamed/Downloads/qarwana/routes/web.php (سطور 89-91): بس Route::resource('invoices') + بنود الفاتورة store/destroy. مفيش مسار print أو print_invoice أو download/pdf.
- /Users/mohamed/Downloads/qarwana/app/Http/Controllers/InvoiceController.php: بس دوال الـ resource القياسية (index/create/store/show/edit/update/destroy). مفيش دالة print أو printInvoice أو pdf.
- grep واسع على print|طباعة|export|pdf|تصدير في resources/views و app و routes رجّع نتيجتين بس وكلاهما عن pdf كـ امتداد ملف مسموح للرفع في ProjectFileController و project_files/index.blade.php — لا علاقة له بطباعة الفاتورة.
- composer.json: مفيش أي مكتبة PDF/طباعة (dompdf/snappy/mpdf).

الخلاصة: دالة printInvoice القديمة اللي كانت بتفتح print_invoice.php?id= ووجهة الطباعة الكاملة بالبنود مالهاش أي مكافئ في النظام الجديد. الـ severity متوسطة لأنها feature مفيدة بس مش حرجة لتشغيل النظام؛ ممكن المستخدم يطبع صفحة العرض من المتصفح كحل بديل ضعيف بس مفيش print layout مخصص.
- **التوصية:** إضافة view مخصص للطباعة (invoices/print) وزر طباعة/تصدير PDF في صفحة الفاتورة يعرض الترويسة والعميل والمشروع والبنود والإجماليات والضريبة.

### 🟡 متوسط — حقل تفاصيل الإيراد والمرفقات وتأكيد الإيراد (is_confirmed) ناقصة في الواجهة والموديل
- **النوع:** field
- **الدليل (القديم):** revenues table: details, attachment_file, is_confirmed، وفي revenues.php حقول رفع ملف PDF/صورة و dropdown للتأكيد (قيد الانتظار/مؤكد) وعرض شارة 'مؤكد'.
- **الحالة في الجديد:** الادعاء صحيح بعد بحث عدائي شامل. جدول revenues في /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_010334_create_revenues_table.php أعمدته بس: project_id, description, amount, revenue_date, payment_method, bank_account_id, notes, created_by — مفيش details ولا attachment_file ولا is_confirmed. الموديل app/Models/Revenue.php الـ fillable مافيهوش أي حقل من التلاتة ولا cast boolean للتأكيد ولا relation للمرفقات. الفورم resources/views/revenues/form.blade.php مفيهوش input لرفع ملف (الفورم نفسه مش multipart/form-data) ولا dropdown للتأكيد (قيد الانتظار/مؤكد). صفحة العرض revenues/show.blade.php مفيهاش شارة 'مؤكد' ولا عرض مرفق. الكنترولر RevenueController.php دالة validateData بتتحقق بس من الـ7 حقول دول ومفيش معالجة ملفات ولا is_confirmed. ميزة رفع الملفات الوحيدة في النظام (ProjectFile + ProjectFileController) خاصة بملفات المشاريع ومش مربوطة بالإيرادات بأي relation. ملاحظة: حقل details القديم ليه مكافئ تقريبي في النظام الجديد عن طريق description + notes، لكن رفع المرفقات ومفهوم تأكيد الإيراد ناقصين فعلاً بالكامل. عشان كده خفّضت الخطورة لـ medium: فقدان workflow تأكيد الإيراد والمرفقات مؤثّر ماليًا بس مش critical، و'التفاصيل' متغطية تقريبًا.
- **التوصية:** إضافة الأعمدة details و attachment_file و is_confirmed للجدول والموديل، ودعم رفع مرفق للإيراد وخطوة تأكيد قبل احتسابه نهائياً.

---

## Reports & financial statements (التقارير والقوائم المالية)

> النظام الجديد ضعيف جداً في هذا الـ domain. النظام القديم فيه منظومة تقارير غنية (12 تقرير متميّز): تقرير عام مع رسوم بيانية شهرية وتصدير Excel، قائمة مركز مالي كاملة (ميزانية: أصول=خصوم+حقوق ملكية مع إهلاك الأصول)، قائمة دخل احترافية (COGS + مجمل ربح + ربح تشغيلي + إهلاك + هامش ربح)، تقارير الأقسام، تقرير المقاولين التفصيلي، وكشوف حساب قابلة للطباعة والتصدير لكل كيان (موظف/شريك/مورد داخلي وخارجي/مقاول) برصيد تراكمي ورصيد افتتاحي، وتقرير تكاليف المشاريع التفصيلية مع استيراد/تصدير Excel، وسجل محاولات تسجيل الدخول. النظام الجديد فيه ReportController واحد فقط بصفحة index واحدة (ملخّص إيرادات/مصروفات + مصروفات حسب الفئة + ربحية المشاريع) — وده بيغطّي جزء بسيط من reports.php فقط. مفيش ميزانية، مفيش قائمة دخل، مفيش كشوف حساب حقيقية، مفيش أي تصدير Excel/PDF في النظام كله، ومفيش سجل دخول. النقص هنا كبير وحرج لشركة مقاولات بتحتاج قوائم مالية رسمية وكشوف حساب للموردين والمقاولين.

### 🟠 خطير — قائمة المركز المالي (الميزانية / Balance Sheet) غير موجودة نهائياً
- **النوع:** report
- **الدليل (القديم):** /Users/mohamed/Downloads/system (2)/balance_sheet.php — يحسب الأصول المتداولة (نقد في البنوك من bank_accounts.current_balance، ذمم مدينة من revenues.remaining_amount و invoices.total_amount-paid_amount، عهد وسلف الموظفين من employees.custody_balance/advance_balance)، الأصول الثابتة مع حساب الإهلاك (calc_dep_bs: straight-line و declining) من جدول assets، الخصوم المتداولة (مستحق للموردين من purchase_orders.net_amount-paid_amount، مستحق للمقاولين من contractor_extracts.net_amount-paid_amount، مصروفات معلّقة)، وحقوق الملكية (رأس المال من partner_deposits، مسحوبات وأرباح موزّعة من partner_transactions، الأرباح المحتجزة). يطبع معادلة الأصول = الخصوم + حقوق الملكية مع فرق الموازنة.
- **الحالة في الجديد:** بعد بحث عدائي حقيقي في النظام الجديد، تقرير قائمة المركز المالي (Balance Sheet) غير موجود فعلاً. ReportController.php (/Users/mohamed/Downloads/qarwana/app/Http/Controllers/ReportController.php) فيه دالة index() واحدة بس بتطلع إيرادات/مصروفات/صافي + ربحية المشاريع، من غير أي أصول/خصوم/حقوق ملكية أو معادلة الميزانية. الـ route الوحيد في routes/web.php سطر 108 هو reports.index ولا يوجد route للـ balance_sheet. مجلد resources/views/reports/ فيه index.blade.php بس. مفيش أي ذكر لـ balance_sheet/الميزانية/المركز المالي/financial_statements/حقوق الملكية/الأرباح المحتجزة في الكود. صلاحية financial_statements_view غير موجودة؛ RolesAndPermissionsSeeder.php فيه reports.view و reports.export بس. منطق الإهلاك موجود معزول في resources/views/assets/show.blade.php (straight-line فقط لأصل واحد، من غير declining-balance) وجدول assets فيه depreciation_rate/useful_life_years، لكنه عرض لقيمة دفترية لأصل منفرد ومش مُجمَّع في أي قائمة مركز مالي. توجد لبنات متفرقة فقط (إهلاك لكل أصل، totalCapital للشركاء في Partner.php، net_amount للمقاولين/الموردين) لكنها لم تُجمَّع أبداً في تقرير ميزانية بأصول متداولة وثابتة وخصوم وحقوق ملكية مع فرق الموازنة. DashboardController كمان مفيش فيه أي تجميع بنمط الميزانية.
- **التوصية:** بناء BalanceSheetController + view يحسب الأصول (نقد البنوك، ذمم مدينة، عهد/سلف الموظفين، الأصول الثابتة بعد الإهلاك)، الخصوم (مستحقات موردين/مقاولين/مصروفات معلّقة)، وحقوق الملكية (رأس مال الشركاء - مسحوبات + أرباح محتجزة)، مع فلتر 'كما في تاريخ' (as_of_date) وزر طباعة.

### 🟠 خطير — قائمة الدخل (الأرباح والخسائر / Income Statement) غير موجودة
- **النوع:** report
- **الدليل (القديم):** /Users/mohamed/Downloads/system (2)/income_statement.php — يحسب الإيرادات من revenues، التكاليف التشغيلية (COGS) = مستخلصات المقاولين المدفوعة (contractor_extracts.paid_amount) + أوامر الشراء (purchase_orders.paid_amount) + تكاليف المشاريع التفصيلية (project_costs.amount) + رواتب الموظفين (employee_transactions حيث transaction_type='salary')، مجمل الربح، تفصيل مصروفات expenses حسب الفئة (مواد/عمالة/معدات/نقل تشغيلية + إدارية/مرافق/أخرى)، إهلاك الأصول، الربح التشغيلي، صافي الربح، وهامش الربح (profit_margin). مع فلتر تاريخ ومشروع وطباعة.
- **الحالة في الجديد:** بعد بحث عدائي شامل في النظام الجديد، تأكد إن قائمة الدخل (Income Statement / الأرباح والخسائر) بمنطقها المحاسبي مش موجودة فعلاً.

الأدلة:
1) ReportController الوحيد هو /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ReportController.php — وبيحسب net بصيغة مبسطة جداً: السطور 33-34 (totalRevenue = SUM(revenues.amount)، totalExpense = SUM(expenses.amount)) والسطر 60 (net = totalRevenue - totalExpense). مفيش أي إدخال لمستخلصات المقاولين ولا أوامر الشراء ولا الرواتب (employee_transactions) ولا project_costs ولا إهلاك الأصول.

2) الـ view /Users/mohamed/Downloads/qarwana/resources/views/reports/index.blade.php بيعرض 3 كروت فقط (إجمالي إيرادات / إجمالي مصروفات / صافي) + مصروفات حسب الفئة + ربحية مشاريع (إيرادات − مصروفات). مفيش أي تبويب COGS / مجمل ربح / ربح تشغيلي / هامش ربح.

3) grep على كل app + resources + routes + database (باستثناء vendor) عن: income / قائمة الدخل / profit / gross / cogs / مجمل / هامش / operating_profit / net_profit — رجع صفر نتائج ذات صلة (الوحيد اللي طلع: توزيع أرباح الشركاء في Partner.php، ونوع ضريبة income في Tax.php — مش علاقة بقائمة دخل).

4) مفيش balance_sheet ولا financial_statements ولا قائمة مركز مالي/ميزانية في النظام الجديد (grep رجع فاضي). الـ legacy كان فيه income_statement.php + balance_sheet.php، الاتنين غير موجودين.

5) routes/web.php فيه route واحد بس: Route::get('reports', [ReportController::class,'index']) السطر 108. مفيش route لأي قائمة دخل منفصلة.

6) صلاحية legacy كانت financial_statements_view؛ النظام الجديد عنده reports.view فقط (RolesAndPermissionsSeeder.php سطر 25). 

7) الـ DashboardController كمان بيكرر نفس الصيغة المبسطة net = revenue - expense (سطر 26).

الخلاصة: 'صافي الربح' في النظام الجديد غير دقيق لشركة مقاولات لأنه بيتجاهل التكاليف التشغيلية الحقيقية (مقاولين، موردين، رواتب، project_costs، إهلاك). الـ severity مناسب high (مش critical لأنه تقرير عرض وليس عملية مالية بتعدّل بيانات، لكن مؤثر على دقة القرار المالي).
- **التوصية:** بناء IncomeStatementController يجمع كل بنود التكلفة الحقيقية (مستخلصات + أوامر شراء + رواتب + تكاليف تفصيلية + إهلاك) ويعرض هيكل قائمة دخل (إيرادات → COGS → مجمل ربح → مصروفات إدارية → ربح تشغيلي → صافي ربح + هامش). وتصحيح حساب الصافي في صفحة التقارير الحالية لأنه ناقص بنود ضخمة.

### 🟠 خطير — كشوف حساب الكيانات (موظف/شريك/مورد/مقاول) القابلة للطباعة برصيد تراكمي غير موجودة
- **النوع:** report
- **الدليل (القديم):** /Users/mohamed/Downloads/system (2)/employee_statement.php (كشف حساب موظف من employee_transactions مع إجماليات salary/advance/custody)، partner_statement.php (كشف حساب شريك مع عمود 'الرصيد بعد' balance_after تراكمي + الرصيد الحالي + تصدير CSV + طباعة)، supplier_details.php و internal_supplier_details.php (كشف مورد مع opening_balance رصيد افتتاحي وطباعة عبر printWindow.print)، contractor_details.php (كشف مقاول). كلها كشوف حساب تفصيلية بفلاتر تاريخ ونوع المعاملة وطباعة.
- **الحالة في الجديد:** تأكدت إن الـ gap حقيقي. كشوف حساب الكيانات (موظف/شريك/مورد/مقاول) القابلة للطباعة برصيد تراكمي غير موجودة في النظام الجديد. أدلة البحث: (1) الـ Controllers الأربعة ContractorController/PartnerController/EmployeeController/SupplierController فيها بس index/show/create/store/edit/update/destroy — مفيش أي method اسمه statement أو print أو export. (2) routes/web.php كلها Route::resource عادية من غير أي route مخصص للكشف أو الطباعة. (3) الدالة الوحيدة statement() في المشروع كله موجودة في app/Services/BankLedgerService.php:74 وهي خاصة بـ BankAccount فقط (كشف حساب بنكي برصيد تراكمي عبر BankAccountController::show سطر 53) — مش للكيانات. (4) الـ views: resources/views/contractors/show.blade.php (سطر 35 balanceDue رقم واحد + قائمة أحدث 10 مستخلصات/دفعات من غير عمود رصيد تراكمي/فلتر/طباعة)، partners/show.blade.php (سطر 35 currentBalance)، suppliers/show.blade.php (سطر 37 balanceDue)، employees/show.blade.php (salary/advanceBalance/custodyBalance أرقام مجمّعة بس). (5) ReportController فيه index() بس، و resources/views/reports/index.blade.php تقرير أرباح/مصروفات للفترة (إيرادات/مصروفات/صافي + مصروفات حسب الفئة + ربحية المشاريع) مش كشف حساب كيان برصيد تراكمي. (6) مفيش window.print ولا @media print ولا fputcsv ولا أي مكتبة PDF (dompdf/snappy/maatwebsite) في كود non-vendor؛ opening_balance مستخدم بس لـ bank_accounts. خلاصة: الميزة غايبة فعلاً للكيانات الأربعة، والموجود (كشف البنك) دومين مختلف. الـ severity high مش critical لأن البيانات الأساسية موجودة في جداول المعاملات ويمكن بناء الكشف منها، لكن واجهة الكشف القابل للطباعة بالرصيد التراكمي والفلاتر والتصدير غير موجودة.
- **التوصية:** إضافة دوال statement() لكل كيان (مقاول/مورد/شريك/موظف) تعرض كل المعاملات مرتّبة بالتاريخ مع رصيد افتتاحي ورصيد تراكمي (balance_after) وفلاتر تاريخ ونوع المعاملة، مع صفحة طباعة مخصّصة + تصدير Excel/PDF.

### 🟠 خطير — تقرير ورصيد تكاليف المشاريع التفصيلية (project_costs) ومنظومته كاملة غير موجودة
- **النوع:** module
- **الدليل (القديم):** /Users/mohamed/Downloads/system (2)/project_costs.php + جدول project_costs في esystem.sql (السطر 3589: amount, description, cost_date, work_item, contractor_supplier, payment_method, payment_type, receipt_check_number, bank_name, asset_category, page_number) + view project_costs_summary (السطر 3614). فيه استيراد/تصدير Excel. وتُستخدم في reports.php و income_statement.php كبند تكلفة رئيسي.
- **الحالة في الجديد:** الـ module بتاع project_costs التفصيلي مش موجود فعلاً في النظام الجديد بعد بحث جدي. بحثت في: database/migrations/ (مفيش migration باسم project_costs — الموجود create_expenses_table.php و create_purchase_orders_table.php بس)، app/Models/ (مفيش ProjectCost — في Expense.php بس)، app/Http/Controllers/ (مفيش ProjectCostController)، routes/web.php (مفيش route)، وكمان grep على كل الأعمدة المميزة (work_item, contractor_supplier, receipt_check_number, asset_category, page_number) رجع صفر نتائج. composer.json مفيش فيه مكتبة Excel (لا maatwebsite/excel ولا phpspreadsheet) فالاستيراد/التصدير ساقط برضه.

أقرب حاجة موجودة هي جدول expenses (database/migrations/2026_06_03_010334_create_expenses_table.php + app/Models/Expense.php): مربوط بـ project_id وفيه category, description, amount, expense_date, payment_method — فده يغطّي فكرة تكاليف المشروع الأساسية جزئياً لكن من غير الحقول التفصيلية ولا الـ view project_costs_summary ولا الـ Excel.

نقطة مهمة في حسابات الأرباح: في النظام القديم (income_statement.php سطر 55-73 و reports.php سطر 65-82) كان project_costs بند منفصل ومضاف فوق contractor_costs و supplier_costs و salaries و expenses — يعني مش نفس expenses. في ReportController.php الجديد (سطر 60) صافي الربح = totalRevenue − totalExpense من جدول expenses بس (ومش بيضيف حتى دفعات المقاولين/الموردين ولا المرتبات)، فالـ ledger التفصيلي ومنظومة الـ Excel فعلاً مش متعملهم replicate.

الخلاصة: الـ module غير موجود (stillMissing=true). نزّلت الـ severity من critical لـ high لأن expenses بديل جزئي معقول لتتبّع تكاليف المشروع الأساسية، لكن الـ ledger التفصيلي + الاستيراد/التصدير Excel + الـ summary view غايبين بالكامل.
- **التوصية:** إنشاء جدول project_costs + Model + Controller (CRUD) بكل الحقول الأصلية، مع إمكانية استيراد Excel، ودمج إجماليه في صفحة التقارير وقائمة الدخل وتكلفة المشروع.

### 🟡 متوسط — تقرير المقاولين التفصيلي (contractors_report) غير موجود
- **النوع:** report
- **الدليل (القديم):** /Users/mohamed/Downloads/system (2)/contractors_report.php — إحصائيات إجمالية للمستخلصات (عدد المقاولين، عدد المستخلصات، إجمالي total_amount/deductions/additions/net_amount/paid_amount من contractor_extracts) + تقرير تفصيلي مجمّع لكل مقاول (GROUP BY c.id) + تقرير المستخلصات حسب المشروع (GROUP BY p.id) مع فلاتر from_date/to_date/contractor_id/project_id/status.
- **الحالة في الجديد:** تأكد بعد بحث عدائي: التقرير غير موجود فعلاً في النظام الجديد. مسار التقارير الوحيد هو reports.index في /Users/mohamed/Downloads/qarwana/routes/web.php:108 الموجّه إلى ReportController::index. هذا الكنترولر (/Users/mohamed/Downloads/qarwana/app/Http/Controllers/ReportController.php) يحسب فقط الإيرادات والمصروفات والمصروفات حسب الفئة وربحية المشاريع (إيراد − مصروف)، ولا يلمس contractor_extracts أو الخصومات/الصافي/المدفوع نهائياً. وصفحة /Users/mohamed/Downloads/qarwana/resources/views/reports/index.blade.php تعرض نفس الشيء فقط بدون أي قسم للمقاولين. لا يوجد أي تقرير مجمّع للمقاولين (GROUP BY contractor) أو حسب المشروع للمستخلصات. التجميع المالي الوحيد للمقاول هو دالة balanceDue() في /Users/mohamed/Downloads/qarwana/app/Models/Contractor.php:42 المستخدمة في صفحة عرض المقاول الواحد وفي ويدجت 'أعلى المقاولين رصيداً مستحقاً' بالداشبورد (dashboard.blade.php:100) — وهي لقطة لمقاول واحد أو Top-N وليست تقريراً تفصيلياً قابلاً للفلترة (from/to/contractor/project/status) بإجماليات gross/deductions/net/paid وقائمة مستخلصات تفصيلية وتصدير Excel/طباعة كما في الـ legacy. بل إن جدول contractor_extracts الجديد (migration 2026_06_03_013001) لا يحتوي أصلاً على عمودي additions ولا paid_amount، فالبيانات اللازمة لجزء 'الإضافات' و'المدفوع لكل مستخلص' في التقرير القديم غير مُنمذجة. الخلاصة: المطالبة صحيحة. خفضت الخطورة إلى medium لأن البيانات الأساسية (المستخلصات/الرصيد المستحق) موجودة ويمكن بناء التقرير منها بسهولة، لكن التقرير المخصّص نفسه مفقود.
- **التوصية:** بناء تقرير مقاولين يجمّع مستخلصات contractor_extracts لكل مقاول ولكل مشروع، يعرض إجمالي/خصومات/إضافات/صافي/مدفوع/متبقّي، مع نفس الفلاتر (تاريخ/مقاول/مشروع/حالة).

### 🟡 متوسط — غياب تصدير Excel/PDF نهائياً من كل التقارير
- **النوع:** ui
- **الدليل (القديم):** تصدير Excel/CSV في: reports.php (exportReport سطر 808 + params export=excel)، section_reports.php (exportSection لكل قسم)، partner_statement.php (exportToExcel سطر 724 ينشئ CSV بـ BOM UTF-8). والطباعة (window.print) في reports.php، balance_sheet.php، income_statement.php، employee_statement.php، partner_statement.php، internal_supplier_details.php.
- **الحالة في الجديد:** الـ gap حقيقي ومؤكد بعد بحث شامل في النظام الجديد. لا يوجد أي تصدير Excel/CSV/PDF ولا أي زر طباعة في أي تقرير.

الأدلة:
1) composer.json و composer.lock: مفيش أي مكتبة تصدير (maatwebsite/excel, barryvdh/laravel-dompdf, snappy, phpoffice/phpspreadsheet, tcpdf, mpdf, fpdf). نتيجة barryvdh في الـ lock هي مجرد اسم مؤلف لباكدج CORS (fruitcake/php-cors) مش مكتبة PDF.
2) routes/web.php سطر 108: التقارير كلها route واحد بس GET reports → ReportController@index. مفيش أي route لـ export/print/pdf ولا أي تقارير منفصلة (balance_sheet, income_statement, employee_statement, partner_statement, section_reports, internal_supplier_details) اللي كانت في الـ legacy.
3) app/Http/Controllers/ReportController.php: بيرجّع View بس، مفيش CSV/StreamedResponse/Content-Disposition/BOM ولا معالجة param export=excel.
4) resources/views/reports/index.blade.php: فيه فلتر فترة وجداول بس، مفيش زر طباعة أو تصدير خالص.
5) grep على كل الـ views لكلمات print/طباعة/تصدير/export/window.print/fa-print/fa-file-excel: مفيش أي نتيجة وظيفية — الذكر الوحيد لكلمة excel هو label نصّي بيوضّح أنواع الملفات المسموح رفعها في resources/views/project_files/index.blade.php سطر 20.
6) صفحات الـ show اللي شبه كشف الحساب (partners/show, suppliers/show, employees/show) ومش فيها أي زر طباعة أو تصدير.
7) الـ download/StreamedResponse الوحيد في النظام كله موجود في app/Http/Controllers/ProjectFileController.php سطر 70-75 وبيخدم تحميل الملفات المرفوعة بس، مش تصدير تقارير.

الـ severity صُحّحت لـ medium: ده فقدان feature في الواجهة (UI/تشغيلي) مش bug أمني أو فقدان بيانات؛ الداتا نفسها موجودة وممكن عرضها على الشاشة، بس مفيش طريقة طباعة أو تصدير زي الـ legacy.
- **التوصية:** إضافة مكتبة (maatwebsite/excel أو barryvdh/laravel-dompdf) وأزرار تصدير Excel + طباعة PDF لكل التقارير والكشوف، حيث الشركة بتحتاج كشوف للموردين/المقاولين وقوائم مالية رسمية.

### 🟡 متوسط — سجل محاولات تسجيل الدخول (login_logs / login_attempts) غير موجود
- **النوع:** security
- **الدليل (القديم):** /Users/mohamed/Downloads/system (2)/login_logs.php (للأدمن فقط) + جدول login_attempts في esystem.sql (السطر 3061: username, user_id, ip_address, user_agent, status enum success/failed, failure_reason, created_at) — مع فلاتر status/ip/username/date_from/date_to ويسجّل كل محاولة دخول ناجحة/فاشلة وسببها.
- **الحالة في الجديد:** الادعاء صحيح: لا يوجد سجل محاولات تسجيل دخول في النظام الجديد بعد بحث شامل.

ما تم فحصه:
- app/Http/Controllers/Auth/LoginController.php: يستخدم RateLimiter فقط (5 محاولات لكل email+IP/دقيقة). عند فشل Auth::attempt ينادي RateLimiter::hit ويرمي ValidationException — ولا يكتب أي صف في قاعدة البيانات. لا يسجّل IP/user_agent/سبب الفشل/اسم المستخدم لأي محاولة ناجحة أو فاشلة.
- database/migrations: لا يوجد جدول login_attempts ولا login_logs إطلاقاً.
- جدول activity_logs (2026_06_03_017003_create_activity_logs_table.php) أعمدته: user_id, action, model_type, model_id, description, ip_address, created_at. الـaction محصور في created|updated|deleted فقط. لا يحتوي على user_agent ولا status (success/failed) ولا failure_reason ولا username.
- app/Models/ActivityLog.php :: record() يُستدعى من AppServiceProvider لأحداث CRUD على الموديلات فقط؛ يتطلب كائن Model و Auth::id() (الذي يكون null عند فشل الدخول)، فلا يمكنه التقاط أحداث الدخول.
- لا يوجد أي Event Listeners لأحداث Login/Failed/Logout (لا مجلد Listeners/Events ولا Event::listen في AppServiceProvider).
- routes/web.php: المسار الوحيد للسجلات هو activity-logs (محمي بـ can:users.view) ويعرض نشاط الموديلات عبر ActivityLogController، وليس محاولات الدخول. لا يوجد route لعرض سجل دخول.

الخلاصة: النظام الجديد فيه rate limiting + سجل نشاط للموديلات، لكن لا يوجد مكافئ معقول لـ login_attempts القديم (لا تتبع للفشل/السبب/user_agent/username ولا واجهة عرض بفلاتر). الميزة غائبة فعلاً. خفّضت الخطورة من high إلى medium لأن حماية brute-force (RateLimiter) ومنع الحسابات المعطلة وregenerate session موجودة، لكن غياب التدقيق الأمني (audit trail) لمحاولات الدخول يبقى نقصاً حقيقياً.
- **التوصية:** إنشاء جدول login_attempts + تسجيل كل محاولة (ناجحة/فاشلة + IP + user_agent + السبب) في LoginController، وصفحة عرض/فلترة للأدمن فقط.

### 🟡 متوسط — التقرير العام يفتقد الرسوم البيانية الشهرية والتكاليف الكاملة
- **النوع:** report
- **الدليل (القديم):** /Users/mohamed/Downloads/system (2)/reports.php — الإيرادات والمصروفات الشهرية (DATE_FORMAT %Y-%m مجمّعة) لرسم بياني زمني، وإجمالي تكاليف المشاريع الكامل = expenses + مستخلصات مقاولين + أوامر شراء + project_costs + رواتب، وفلتر حسب المشروع (project_id).
- **الحالة في الجديد:** الادعاء صحيح بعد بحث جدّي. التقرير الجديد موجود في ملف واحد فقط:
- Controller: /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ReportController.php (method index واحد فقط، لا يوجد أي controller/route بديل — راجع routes/web.php سطر 108، الـ route الوحيد reports.index).
- View: /Users/mohamed/Downloads/qarwana/resources/views/reports/index.blade.php.

ما يفعله التقرير الجديد: إجمالي إيرادات + إجمالي مصروفات + صافي، مصروفات حسب الفئة (groupBy category)، وربحية لكل مشروع عبر withSum على علاقتي revenues و expenses فقط (سطور 33-53).

المفقود فعلاً (مطابق للـ legacy reports.php):
1) لا يوجد تجميع شهري %Y-%m ولا رسم بياني زمني في التقرير. (legacy: DATE_FORMAT(...,'%Y-%m') GROUP BY + canvas monthlyChart سطور 102-120, 469-473).
2) إجمالي التكاليف الكامل غير محسوب: الـ controller يجمع amount من جدول expenses فقط، ولا يضيف مستخلصات المقاولين/مدفوعات الموردين/أوامر الشراء/project_costs/الرواتب. (legacy: total_all_project_costs = expenses + contractor_paid + supplier_paid + project_costs + salaries سطر 82). لا يوجد grep أي إشارة لـ ProjectCost/extract/purchaseOrder/salary داخل ReportController أو الـ view. علاوة على ذلك Project model فيه علاقتا revenues و expenses فقط — لا علاقات للمستخلصات/أوامر الشراء/التكاليف، ولا يوجد موديل ProjectCost ولا migration باسم project_costs أصلاً.
3) لا يوجد فلتر بمشروع محدد: الـ view فيه فقط حقول from/to (سطور 10-17)، والـ controller يقرأ $request->date('from'/'to') فقط، بلا project_id (legacy كان فيه project_id select سطر 270 + شرط AND project_id = ? في كل الاستعلامات).

تنبيه مهم لتعديل الخطورة: قدرة الرسم الشهري موجودة في النظام لكن في مكان آخر — DashboardController (/Users/mohamed/Downloads/qarwana/app/Http/Controllers/DashboardController.php سطور 38-86: sumByMonth بصيغة Y-m + chartMonths) وتُعرض في dashboard.blade.php (Chart.js trendChart). لكنها مثبّتة على آخر 6 شهور بلا فلتر تاريخ/مشروع، وليست جزءاً من التقرير العام محل التدقيق. لذلك الميزة كتقرير ما زالت مفقودة، لكن وجود بنية مشابهة في الـ dashboard يقلل الخطورة من high إلى medium.
- **التوصية:** توسيع التقرير العام ليشمل: تجميع شهري للإيراد/المصروف (chart)، إدراج كل بنود التكلفة الحقيقية في الإجمالي والصافي، وفلتر بمشروع محدد.

### ⚪ بسيط — تقرير الأقسام (section_reports) بالإحصائيات الشاملة والتصدير غير موجود
- **النوع:** report
- **الدليل (القديم):** /Users/mohamed/Downloads/system (2)/section_reports.php — لوحة إحصائيات لكل وحدة (مقاولين active/total، عملاء، مشاريع حسب الحالة وإجمالي contract_value، موظفين وإجمالي الرواتب، مصروفات، إيرادات، موردين، مدفوعات الموردين) مع زر تصدير Excel لكل قسم (exportSection).
- **الحالة في الجديد:** الكلام صح بعد بحث عدواني كامل. مفيش تقرير أقسام مجمّع ولا تصدير لكل قسم في النظام الجديد. اللي موجود: ReportController في /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ReportController.php مع view واحد /Users/mohamed/Downloads/qarwana/resources/views/reports/index.blade.php — ده تقرير مالي بس (إجمالي إيرادات/مصروفات + مصروفات حسب الفئة + ربحية المشاريع)، مش لوحة إحصائيات أقسام. الراوت الوحيد في /Users/mohamed/Downloads/qarwana/routes/web.php سطر 108 (reports.index) ومفيش أي export/download routes للتقارير (الـ download الوحيد بتاع ملفات المشاريع سطر 100). مفيش أي package للـ Excel/CSV/PDF في composer.json ولا أي streamDownload/export في الكود. الـ grep على section_report/exportSection رجع صفر. أقرب حاجة موجودة جزئياً هي الإحصائيات الموزّعة في /Users/mohamed/Downloads/qarwana/resources/views/dashboard.blade.php و DashboardController (عدد المشاريع/العملاء/المقاولين/الموردين/الموظفين، المشاريع حسب الحالة، إيرادات/مصروفات) — لكنها لوحة تحكم بدون تصدير لكل قسم وبدون إجماليات رواتب الموظفين أو مدفوعات الموردين. خفّضت الـ severity لـ low لأن أغلب الأرقام والبيانات الأساسية موجودة فعلاً في الداشبورد؛ الناقص حقيقي هو تجميع التقرير لكل قسم + خاصية التصدير (exportSection / export all)، يعني فجوة feature مش فقدان بيانات.
- **التوصية:** بناء صفحة تقارير أقسام تجمّع إحصائيات كل وحدة (عدد/نشط/إجماليات مالية) مع زر تصدير Excel لكل قسم.

---

## Auth, Profile, Login Audit & Permissions (المصادقة، الملف الشخصي، سجل الدخول، الصلاحيات)

> دومين المصادقة في النظام الجديد (Laravel) متوسط الاكتمال. الجزء الأساسي للدخول والخروج موجود وأقوى أمنياً من القديم (LoginController فيه RateLimiter و session regeneration ومنع الحسابات المعطّلة ورسالة خطأ عامة لا تكشف وجود الإيميل، وRBAC عبر Spatie مكتمل). لكن النظام الجديد يفتقد ثلاث قدرات كاملة كانت موجودة في القديم: (1) تسجيل وأرشفة محاولات تسجيل الدخول (جدول login_attempts + صفحة login_logs.php) — لا يوجد أي جدول أو شاشة بديلة، وLoginController لا يكتب أي محاولة للقاعدة. (2) صفحة الملف الشخصي / إعدادات الحساب (profile.php) بما فيها تعديل البيانات الذاتية وتغيير كلمة المرور للمستخدم نفسه — لا يوجد ProfileController ولا route ولا view. (3) رفع/حذف صورة البروفايل (avatar) — العمود موجود في users migration والـModel لكن لا توجد أي واجهة أو منطق يستخدمه فعلياً. كذلك سجل النشاط (activity_logs) الجديد لا يسجّل حدث الدخول (login) إطلاقاً ولا يحتوي على فلاتر/بحث/تصدير، بينما القديم كان يسجّل الدخول ويعرضه. هذه فجوات مهمة في التدقيق الأمني والامتثال لشركة مقاولات.

### 🟠 خطير — غياب صفحة سجل الدخول للأدمن مع الفلاتر والإحصائيات (login_logs.php UI)
- **النوع:** report
- **الدليل (القديم):** login_logs.php (للأدمن فقط، سطر 12-16) يوفّر: فلترة بالحالة والـIP واسم المستخدم ونطاق تاريخ (سطر 50-83)، Pagination (سطر 90-113)، إحصائيات آخر 24 ساعة: إجمالي/ناجح/فاشل/عدد IPs فريدة/عدد مستخدمين فريدين (سطر 116-127)، وقائمة أكثر الـIPs فشلاً (سطر 129).
- **الحالة في الجديد:** الـgap حقيقي بعد بحث عدائي كامل. الموجود في النظام الجديد هو نظام ActivityLog وهو feature مختلف تماماً (audit trail لعمليات CRUD)، مش سجل دخول.

أدلة الفحص:
1) /Users/mohamed/Downloads/qarwana/app/Http/Controllers/Auth/LoginController.php — ما بيكتبش أي حاجة في ActivityLog عند نجاح/فشل الدخول أو الخروج. مفيش تسجيل لمحاولات الدخول إطلاقاً.
2) /Users/mohamed/Downloads/qarwana/app/Providers/AppServiceProvider.php (سطور 35-37) — ActivityLog بيتعبّى فقط من events الموديلات created/updated/deleted، و ActivityLog::record() بيتجاهل console. مفيش مفهوم success/failed.
3) /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_017003_create_activity_logs_table.php — الأعمدة: action(created/updated/deleted), model_type, model_id, description, ip_address فقط. لا status ولا failure_reason ولا username ولا user_agent (كلها موجودة في legacy login_attempts).
4) /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ActivityLogController.php — index() = ActivityLog::with('user')->latest('created_at')->paginate(30) فقط؛ بدون فلاتر/بحث/إحصائيات/أكثر IPs فشلاً (مطابق للادعاء).
5) /Users/mohamed/Downloads/qarwana/resources/views/activity_logs/index.blade.php — جدول بسيط (الوقت/المستخدم/الإجراء/العنصر/IP) + pagination فقط؛ مفيش form فلترة ولا stats cards ولا تنبيه IPs مشبوهة.
6) routes/web.php سطر 106: المسار activity-logs باسم activity_logs.index؛ مفيش route باسم login_logs. مفيش مجلد views للـlogin_logs. الباكدج الوحيد المتعلق هو spatie/laravel-permission (مش activitylog/audit).

النتيجة: ميزة سجل الدخول الأمني (محاولات ناجحة/فاشلة + فلاتر + إحصائيات 24 ساعة + أكثر IPs فشلاً) غير موجودة. severity رفعتها high لأنها feature أمنية (رصد brute-force) مش مجرد تقرير عادي.
- **التوصية:** بناء LoginLogController + view (مقصور على can:users.view أو صلاحية مخصصة) بنفس فلاتر القديم (status/ip/username/date range) + بطاقات إحصائيات 24 ساعة + قائمة الـIPs الأكثر فشلاً، لتمكين المدير من رصد المحاولات المشبوهة.

### 🟡 متوسط — غياب تسجيل وأرشفة محاولات تسجيل الدخول (Login Attempts / Login Audit Log)
- **النوع:** module
- **الدليل (القديم):** جدول login_attempts في esystem.sql سطر 3061 (الأعمدة: username, user_id, ip_address, user_agent, status enum('success','failed'), failure_reason, created_at) ومليان بيانات فعلية (INSERT من سطر 3077). دالة log_login_attempt() في login.php سطر 21-35 تُسجّل كل محاولة ناجحة/فاشلة مع سبب الفشل. setup_login_logs.php ينشئ الجدول مع الفهارس وForeign Key.
- **الحالة في الجديد:** الادعاء صحيح بعد بحث عدائي كامل. لا يوجد أي تسجيل/أرشفة لمحاولات الدخول في النظام الجديد:

1) لا migration لجدول login_attempts/login_logs. ملفات database/migrations لا تحتوي إلا على جداول الأعمال + activity_logs، ولا يوجد حتى migration لجدول sessions (التخزين غير DB).

2) LoginController في /Users/mohamed/Downloads/qarwana/app/Http/Controllers/Auth/LoginController.php يستخدم RateLimiter::tooManyAttempts/hit/clear فقط (أسطر 31-54)، ولا يكتب أي سجل للقاعدة لا عند النجاح ولا عند الفشل ولا عند الحساب المعطّل. destroy() كذلك لا يسجّل خروج.

3) النظام الوحيد للتدقيق هو activity_logs، وهو غير مكافئ: الجدول (2026_06_03_017003_create_activity_logs_table.php) أعمدته action تأخذ created|updated|deleted فقط، وموديل ActivityLog (app/Models/ActivityLog.php) عبر record() يُسجّل أحداث Eloquent (created/updated/deleted) لموديلات محددة. والـAUDITED list في AppServiceProvider.php (أسطر 13-19) لا تتعامل مع أحداث المصادقة إطلاقاً — أحداث الدخول ليست موديلات. لا أعمدة status/failure_reason/user_agent/username لمحاولة دخول.

4) لا يوجد أي listener لأحداث Laravel auth: grep على Illuminate\Auth\Events / Login::class / Failed::class / Authenticated::class / Logout::class / Event::listen / EventServiceProvider رجع صفر. لا توجد view لمحاولات الدخول (موجود فقط auth/login.blade.php) ولا route لسجل دخول (routes/web.php يحتوي activity-logs فقط).

الخلاصة: وظيفة log_login_attempt() القديمة وجدول login_attempts (بأعمدة status/failure_reason/ip/user_agent والبيانات الفعلية) غير موجودة ولا أي مكافئ. الشدة medium (وليست critical) لأن النظام الجديد ما زال يوفّر حماية brute-force عبر RateLimiter، لكن أُفقد البُعد التدقيقي/الأمني التاريخي (forensics) لتتبع محاولات الدخول الفاشلة وعناوين IP وuser-agent.
- **التوصية:** إنشاء migration وجدول login_attempts (نفس أعمدة القديم: username, user_id, ip_address, user_agent, status, failure_reason, created_at) + Model LoginAttempt. تسجيل كل محاولة داخل LoginController::store (ناجحة وفاشلة مع failure_reason) عبر event listener على Login/Failed أو يدوياً. هذا ضروري للتدقيق الأمني وتتبّع محاولات الاختراق.

### 🟡 متوسط — غياب صفحة الملف الشخصي / إعدادات الحساب (Profile page)
- **النوع:** module
- **الدليل (القديم):** profile.php (33KB) صفحة كاملة: تعديل الاسم/الإيميل/الهاتف ذاتياً (سطر 105-140 مع التحقق من تكرار الإيميل)، تغيير كلمة المرور بالتحقق من كلمة المرور الحالية (سطر 143-174)، عرض آخر تسجيل دخول وتاريخ الإنشاء/التحديث، وسجل آخر 10 نشاطات للمستخدم (سطر 190-199).
- **الحالة في الجديد:** أكدت الغياب بعد بحث جدي. لا يوجد ProfileController في /Users/mohamed/Downloads/qarwana/app/Http/Controllers (مجلد Auth فيه LoginController فقط). routes/web.php لا يحتوي أي route للبروفايل، وما فيش أي استخدام لـ route('profile...'). SettingController.php بيتعامل مع إعدادات الشركة/الموقع فقط (site_name, company_name, company_phone... محمي بـcan:settings.view/edit) ومش بروفايل المستخدم. UserController.php للأدمن فقط (محمي بـcan:users.view/create/edit/delete) - فيه فرع $isSelf في update() لكنه لسه محتاج صلاحية users.edit فالموظف العادي مش هيقدر يعدّل بياناته، وكمان بيكتب الباسورد على طول من غير التحقق من كلمة المرور الحالية اللي كانت موجودة في legacy profile.php (سطر 143-174). مفيش مجلد resources/views/profile ولا أي blade للبروفايل/الحساب. القائمة المنسدلة للمستخدم في layouts/app.blade.php (سطور 109-118) فيها زر تسجيل خروج بس - مفيش لينك للملف الشخصي. الميزات المحددة في legacy (التعديل الذاتي للاسم/الإيميل/الهاتف مع فحص تكرار الإيميل، تغيير الباسورد بالتحقق من الحالي، عرض آخر دخول وتواريخ الإنشاء/التحديث، وآخر 10 نشاطات للمستخدم) مالهاش مقابل self-service في النظام الجديد. خفّضت الـseverity لـmedium لأن الأدمن يقدر يعدّل أي مستخدم وActivityLogController موجود، فالوظائف موجودة جزئياً للأدمن لكن مش كـself-service للمستخدم العادي.
- **التوصية:** إضافة ProfileController + route (auth) + view يتيح لأي مستخدم مسجّل تعديل اسمه/إيميله/هاتفه وتغيير كلمة مروره (مع التحقق من كلمة المرور الحالية current_password)، وعرض آخر دخول وسجل نشاطه. هذه وظيفة self-service أساسية يستخدمها كل الموظفين.

### 🟡 متوسط — سجل النشاط الجديد لا يسجّل حدث تسجيل الدخول (login action)
- **النوع:** logic
- **الدليل (القديم):** في login.php سطر 93 يتم استدعاء log_activity('login', 'users', $user['id'], 'تسجيل دخول ناجح')، وجدول activity_log في esystem.sql فيه سجلات action='login' فعلية (سطر 46, 49). profile.php سطر 178-188 يعتمد على ذلك لعرض 'آخر تسجيل دخول'.
- **الحالة في الجديد:** الثغرة مؤكدة بعد بحث شامل. ActivityLog في النظام الجديد لا يسجّل حدث login/logout إطلاقاً:

1) app/Models/ActivityLog.php — الثابت ACTIONS (سطور 21-25) فيه created/updated/deleted فقط، ولا login. الدالة record() (سطر 39) توقيعها record(string $action, Model $model) أي تتطلب Eloquent Model، فبنيوياً مش قادرة تسجّل حدث مش-موديل زي الدخول.

2) app/Providers/AppServiceProvider.php — boot() (سطور 34-38) بيربط أحداث Eloquent created/updated/deleted فقط على موديلات AUDITED. مفيش أي listener لـ Illuminate\Auth\Events\Login / Logout / Authenticated.

3) app/Http/Controllers/Auth/LoginController.php — store() (سطر 38 Auth::attempt) وdestroy() (سطر 62 Auth::logout) ما بيستدعوش ActivityLog إطلاقاً؛ نجاح الدخول (سطر 57) مجرد redirect.

4) مفيش آلية بديلة: لا يوجد app/Listeners، ولا EventServiceProvider؛ bootstrap/providers.php يسجّل AppServiceProvider فقط، وbootstrap/app.php مفيهوش withEvents/listen. نتائج grep لـ Event/listen كلها false positives من validateData()/->update().

5) مفيش بديل last_login: هجرات users مفيهاش عمود last_login/last_seen/login_at، ولا يوجد أي view أو منطق لـ'آخر تسجيل دخول' — فميزة profile.php القديمة (التي كانت تقرأ سجلات login من activity_log) بلا مقابل.

6) migration database/migrations/2026_06_03_017003_create_activity_logs_table.php نفسها بتوثّق العمود بتعليق // created | updated | deleted، مؤكدة إن login مكانش مقصود أصلاً.

النتيجة: صفر سجلات دخول/خروج في النظام الجديد. الخطورة medium (فقدان رؤية تدقيق/أمان وميزة آخر دخول، مش كسر لوظيفة أساسية).
- **التوصية:** إضافة تسجيل حدث login/logout في سجل النشاط (عبر event listener على Illuminate\Auth\Events\Login و Logout) أو ضمن جدول login_attempts، حتى يظهر 'آخر تسجيل دخول' في البروفايل ويكتمل مسار التدقيق.

### ⚪ بسيط — غياب رفع/حذف صورة البروفايل (Avatar upload) رغم وجود العمود
- **النوع:** ui
- **الدليل (القديم):** العمود avatar في جدول users (esystem.sql سطر 4184) ومستخدم فعلياً (المستخدم 9 لديه 'avatar_1_1766182285.webp'). setup_avatar.php ينشئ العمود ومجلد uploads/avatars. profile.php سطر 47-103 فيه منطق رفع كامل (تحقق من النوع JPG/PNG/GIF/WEBP والحجم 2MB، اسم فريد، حذف القديمة) + modal رفع/حذف الصورة (سطر 867-926).
- **الحالة في الجديد:** الـclaim صحيح تماماً. بحثت أدورياً في كل النظام الجديد (/Users/mohamed/Downloads/qarwana) ومفيش أي منطق رفع/حذف/عرض للـavatar.

أدلة البحث:
- grep -rni "avatar" على كل الكود رجّع نتيجتين بس: العمود في database/migrations/0001_01_01_000000_create_users_table.php:21 ($table->string('avatar')->nullable()) والـ#[Fillable] في app/Models/User.php:14. مفيش controller ولا view ولا request يستخدمه.
- app/Http/Controllers/UserController.php: دوال store() وupdate() بتعمل validate لـ name/email/password/role/phone/is_active فقط. مفيش $request->file()، مفيش avatar، مفيش معالجة صور خالص.
- resources/views/users/form.blade.php: الفورم <form method="POST"> عادي (مش multipart/form-data) وفيه حقول الاسم/الإيميل/الباسوورد/الهاتف/الدور/نشط فقط، مفيش input type=file. وindex.blade.php وshow.blade.php مفيهمش أي ذكر لصورة/image/photo/upload.
- الـfile upload الوحيد في النظام كله هو ProjectFileController (ملفات المشاريع عبر Storage::disk('local')) ولا علاقة له بصور المستخدمين.
- resources/views/layouts/app.blade.php: الـtopbar dropdown بيعرض أيقونة fa-user-circle + {{ $u->name }} + badge الدور فقط، مفيش <img> للأفاتار ولا رابط profile/account.
- مفيش route لـ profile/account في routes/web.php، ومفيش مجلد public/uploads/avatars ولا storage/app/avatars.

الخلاصة: العمود avatar موجود كـschema ميت فقط، والميزة الكاملة اللي كانت في profile.php القديم (تحقق نوع/حجم، اسم فريد، حذف القديمة، modal رفع/حذف) مالهاش أي مقابل في النظام الجديد.

صححت الـseverity لـ low: الميزة ناقصة فعلاً لكن أثرها cosmetic/تجميلي (صورة بروفايل اختيارية) مش وظيفة جوهرية، والـapp بيعتمد على أيقونة افتراضية بدالها.
- **التوصية:** إضافة رفع/حذف الصورة داخل ProfileController مع validation (image|mimes:jpg,png,gif,webp|max:2048) والتخزين في storage، وعرض الـavatar في الـheader/البروفايل وبدل الحرف الأول كما في القديم.

### ⚪ بسيط — غياب فلترة/بحث/تصدير في شاشة سجل النشاط (Activity Log filters)
- **النوع:** ui
- **الدليل (القديم):** النمط في login_logs.php (فلاتر status/ip/username/date range + pagination + إحصائيات) يعكس توقّع وجود قدرة فلترة وبحث على السجلات في النظام القديم.
- **الحالة في الجديد:** تأكد الغياب بعد بحث عدائي شامل. الكونترولر /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ActivityLogController.php سطر 19: ActivityLog::with('user')->latest('created_at')->paginate(30) فقط، بدون أي قراءة لـ request() ولا where() ولا filter/search. الفيو /Users/mohamed/Downloads/qarwana/resources/views/activity_logs/index.blade.php مجرد جدول + {{ $logs->links() }} من غير أي نموذج فلترة (مستخدم/إجراء/نوع كيان/مدى تاريخي) ولا حقل بحث ولا زر تصدير. الموديل /Users/mohamed/Downloads/qarwana/app/Models/ActivityLog.php مفيهوش أي scopeFilter/scopeSearch. مفيش route تاني للأكتيفيتي (routes/web.php سطر 106 هو الوحيد، index بس). مفيش أي مكتبة تصدير (maatwebsite/excel/dompdf) في الـ app، والـ StreamedResponse/download الوحيد في ProjectFileController لتحميل ملفات المشاريع ومش له علاقة بالسجل. فعلاً مفيش مكافئ. ملاحظة: تصحيح الخطورة لـ low لأن الشاشة محمية بصلاحية users.view (للمديرين فقط) والبيانات معروضة كاملة paginated، فالغياب راحة استخدام مش فجوة وظيفية حرجة.
- **التوصية:** إضافة فلاتر بسيطة (المستخدم، نوع الكيان model_type، الإجراء action، نطاق التاريخ) وإمكانية بحث على شاشة activity_logs، اختيارياً تصدير، لتسهيل المراجعة.

---

## الإعدادات، استيراد/تصدير البيانات، سجل النشاطات، والأدوات المساعدة (Settings, Data Import/Export, Activity Log, Misc Utilities)

> النظام الجديد ناقص بشكل كبير في هذا المجال. أخطر فجوة هي الغياب الكامل لوحدة استيراد/تصدير البيانات (data_import_export.php) التي كانت في النظام القديم وتدعم استيراد وتصدير 6 كيانات (المقاولين، العملاء، المشاريع، الموظفين، المصروفات، الإيرادات) من/إلى Excel مع معاينة وتحرير وتحقق وتسجيل عمليات في جدول import_logs، بالإضافة لاستيراد تكاليف المشاريع (cost_import_logs). النظام الجديد لا يحتوي على أي مكتبة Excel/CSV/PDF (لا phpspreadsheet ولا maatwebsite/excel ولا dompdf في composer.json)، ولا أي Controller أو جدول لوحدة الاستيراد/التصدير إطلاقاً، ولا حتى زر طباعة أو تصدير في التقارير. الإعدادات أيضاً مختزلة: النظام القديم يدعم إعدادات مالية (نسبة الضريبة، الاستقطاع، التأمينات) وإعدادات تنبيهات (تنبيه المخزون، تذكير الدفعات، تنبيهات البريد) ومعلومات النظام وإحصائيات قاعدة البيانات، بينما الجديد يحفظ 5 مفاتيح عامة فقط (اسم الموقع/الشركة، الهاتف، البريد، العنوان) بدون أي إعدادات مالية أو تنبيهات أو عملة أو منطقة زمنية. سجل النشاطات (activity_logs) موجود لكنه يسجّل أحداث Eloquent (created/updated/deleted) فقط ولا يسجّل تسجيل الدخول/الخروج ولا عمليات الاستيراد، بعكس النظام القديم، وصفحة العرض بدون أي فلترة أو بحث.

### 🟠 خطير — وحدة استيراد/تصدير البيانات (Excel) غائبة بالكامل
- **النوع:** module
- **الدليل (القديم):** /Users/mohamed/Downloads/system (2)/data_import_export.php (واجهة كاملة برفع، سحب وإفلات، معاينة، تحرير مباشر، تحقق، استيراد)؛ /Users/mohamed/Downloads/system (2)/api/excel_import_export.php (actions: parse/validate/import/export)؛ /Users/mohamed/Downloads/system (2)/includes/ExcelImportHelper.php (exportContractors/Clients/Employees/Projects/Expenses/Revenues/Suppliers/Supplier_payments + getColumnMapping)؛ جدول import_logs في esystem.sql (السطر 2944) بأعمدة entity_type, filename, total_rows, imported_rows, updated_rows, failed_rows, status, error_log, completed_at
- **الحالة في الجديد:** بعد بحث عدائي شامل في /Users/mohamed/Downloads/qarwana، الوحدة غائبة فعليًا. (1) لا يوجد أي ImportController/ExportController/ExcelController ضمن 32 ملف في app/Http/Controllers؛ ولا يوجد أي method للتصدير/الاستيراد داخل ReportController أو SettingController أو DashboardController. الـ download الوحيد هو app/Http/Controllers/ProjectFileController.php:75 (Storage::disk('local')->download) وهو تنزيل مرفق ملف مرفوع وليس تصدير بيانات. (2) composer.lock لا يحتوي maatwebsite/excel ولا phpoffice/phpspreadsheet ولا box/spout ولا league/csv ولا openspout — لا توجد أي مكتبة Spreadsheet. (3) لا يوجد model ImportLog ضمن 28 ملف في app/Models. (4) لا توجد migration لجدول import_logs، وgrep لـ import_log/imported_rows/failed_rows/total_rows في database/ رجع فاضي. (5) routes/web.php لا يحتوي أي route للاستيراد/التصدير؛ المسار الوحيد المشابه هو project-files/{project_file}/download (تنزيل مرفق). (6) كل الـ views لا تحتوي واجهة استيراد/تصدير؛ كلمة excel الوحيدة هي label لأنواع الملفات المسموح رفعها في resources/views/project_files/index.blade.php:20 ("pdf, word, excel")، وgrep لـ تصدير/استيراد في كل الـ views رجع فاضي. الادعاء مؤكد: وحدة استيراد/تصدير Excel غائبة بالكامل عن النظام الجديد.
- **التوصية:** بناء وحدة استيراد/تصدير Excel كاملة: تثبيت maatwebsite/excel، إنشاء ImportController/ExportController، نماذج Import/Export لكل كيان (مقاولين/عملاء/مشاريع/موظفين/مصروفات/إيرادات)، تنزيل قالب Excel، معاينة وتحرير قبل الاستيراد، تحقق من الصحة (valid/warning/error)، خيار 'تحديث السجلات الموجودة'، وجدول import_logs لتسجيل كل عملية. هذه ميزة أساسية لإدخال بيانات شركة مقاولات بكميات كبيرة.

### 🟡 متوسط — استيراد تكاليف المشاريع من Excel (cost import) غائب
- **النوع:** module
- **الدليل (القديم):** /Users/mohamed/Downloads/system (2)/api/project_costs_import.php (استيراد ملف تكاليف لمشروع محدد مع memory_limit 512M و max_execution_time 300، صلاحية project_costs_import)؛ جدول cost_import_logs في esystem.sql (السطر 2388) بأعمدة project_id, user_id, filename, total_rows, imported_rows, failed_rows, status, error_log, import_date
- **الحالة في الجديد:** تم التأكيد إن الميزة فعلاً غايبة من النظام الجديد بعد بحث شامل في /Users/mohamed/Downloads/qarwana. التفاصيل:

1) Controllers (app/Http/Controllers): 33 كنترولر، مفيش أي CostImportController. الـ ProjectController.php فيه CRUD عادي بس (index, show, create, store, edit, update, destroy + validateData) ومفيش أي method لرفع/استيراد ملف (مفيش hasFile/UploadedFile/store).

2) Models (app/Models): 28 موديل، مفيش CostImportLog.

3) Migrations (database/migrations): 32 ملف، مفيش migration لجدول cost_import_logs (الجداول كلها مسرودة ومفيش حاجة قريبة).

4) Services: ملف واحد بس BankLedgerService.php، مفيش خدمة استيراد تكاليف.

5) routes/web.php: مفيش أي route فيه cost أو import أو upload.

6) Seeders: RolesAndPermissionsSeeder.php مفيهوش صلاحية project_costs_import (ولا أي صلاحية فيها cost/import).

7) composer.json: مفيش أي مكتبة Excel/CSV import (لا maatwebsite/excel ولا phpspreadsheet ولا league/csv).

8) Views: بحثت في resources/views/projects (form, index, show) ومفيش cost/import. النتيجة الوحيدة اللي ظهرت في البحث العام كانت resources/views/welcome.blade.php وهي مجرد boilerplate بتاع Tailwind CSS (كلمة import جوه تعريفات CSS) مش كود ميزة.

9) الملف القديم متأكد منه موجود: /Users/mohamed/Downloads/system (2)/api/project_costs_import.php (15926 bytes).

الخلاصة: مفيش أي مكافئ معقول لميزة استيراد تكاليف المشاريع من Excel في النظام الجديد. الـ severity عدّلتها لـ medium لأنها feature استيراد bulk مساعِدة (يمكن إدخال التكاليف يدوياً) مش وظيفة أساسية بتوقف النظام، لكنها فعلاً ناقصة بالكامل.
- **التوصية:** بناء وظيفة استيراد تكاليف المشاريع من Excel على مستوى المشروع مع تسجيل العملية في جدول cost_import_logs، خصوصاً للملفات الكبيرة (رفع memory_limit). ضرورية لإدخال جداول الكميات/التكاليف الواردة من المهندسين.

### 🟡 متوسط — تصدير التكاليف إلى Excel/CSV غائب
- **النوع:** report
- **الدليل (القديم):** /Users/mohamed/Downloads/system (2)/api/costs_export.php (export types: excel, projects, project_detail مع فلترة from_date/to_date/project_id)
- **الحالة في الجديد:** تم التحقق عدائياً والـ gap مؤكد. الـ ReportController في /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ReportController.php فيه method واحد بس (index) بيرجّع view من غير أي تصدير. الـ view في /Users/mohamed/Downloads/qarwana/resources/views/reports/index.blade.php مافيهوش أي زر print/export/pdf/excel/csv (الـ grep رجّع بس hits غير متعلقة في project_files/index.blade.php وهي رفع/تحميل ملفات المشاريع). الـ route الوحيد للتقارير في /Users/mohamed/Downloads/qarwana/routes/web.php هو reports.index. الـ ExpenseController فيه CRUD عادي بس من غير export action. مفيش أي مكتبة تصدير في composer.json/composer.lock (لا maatwebsite/excel ولا dompdf ولا barryvdh/laravel-dompdf ولا phpspreadsheet ولا league/csv ولا fastexcel). ملاحظة: ظهور "Barryvdh" في composer.lock هو مجرد اسم مؤلف داخل ميتاداتا حزمة fruitcake/php-cors (dependency تابعة للـ framework) مش مكتبة تصدير مثبّتة؛ و sebastian/exporter دي حزمة اختبارات (PHPUnit). الاستدعاء الوحيد لـ download() في كل التطبيق هو ProjectFileController:75 لتحميل ملفات مرفوعة، ولا علاقة له بتصدير التكاليف/التقارير. مفيش Artisan commands أصلاً (فولدر app/Console/Commands فاضي/غير موجود). الخلاصة: وظيفة تصدير التكاليف Excel/CSV الموجودة في api/costs_export.php القديم (excel/projects/project_detail مع فلترة from_date/to_date/project_id) مالهاش أي مقابل في النظام الجديد. عدّلت الـ severity من المفترض (غالباً high) لـ medium لأن البيانات نفسها معروضة فعلاً في التقرير بفلترة من/إلى، وفلترة project_id متاحة في صفحة المصروفات، فالناقص هو الـ output format (تنزيل ملف) فقط مش البيانات نفسها.
- **التوصية:** إضافة تصدير تقرير التكاليف/المصروفات إلى Excel وCSV مع نفس الفلاتر (from/to/project) الموجودة في costs_export.php القديم، لأن المحاسبين يحتاجون نقل الأرقام لبرامج خارجية.

### 🟡 متوسط — تسجيل الدخول/الخروج وعمليات الاستيراد في سجل النشاطات غائب
- **النوع:** security
- **الدليل (القديم):** بيانات activity_log في esystem.sql (السطر 30+) تسجّل action='login'/'logout' مع ip_address (مثال السطر 'تسجيل دخول ناجح'، '154.182.105.87')؛ excel_import_export.php سطر 190 يسجّل log_activity('import',...). إجمالي 43 ملف يستدعي log_activity بأفعال create/update/delete/login/logout/import
- **الحالة في الجديد:** بعد بحث عدائي شامل، الادعاء صحيح والميزة غائبة فعلاً.

تسجيل الدخول/الخروج (login/logout):
- LoginController في /Users/mohamed/Downloads/qarwana/app/Http/Controllers/Auth/LoginController.php لا يستدعي ActivityLog إطلاقاً. ميثود store() (السطر 21-58) تعمل Auth::attempt + session()->regenerate() بدون أي تسجيل نشاط، و destroy() (السطر 60-67) تعمل Auth::logout() بدون تسجيل.
- لا يوجد EventServiceProvider ولا مجلد app/Listeners (الموجود فقط AppServiceProvider.php). grep على أحداث Illuminate\Auth\Events (Login/Logout/Authenticated) وعلى Event::listen و $listen رجع صفر نتائج.
- التسجيل المركزي الوحيد في AppServiceProvider.php (السطر 34-38) مربوط فقط بأحداث Eloquent created/updated/deleted على 13 موديل، ولا يشمل المصادقة.
- ثابت ACTIONS في ActivityLog.php (السطر 22-26) فيه 3 أفعال فقط: created/updated/deleted. لا login/logout/import. وميثود record() (السطر 37) تستقبل Model فقط فلا تصلح لحدث دخول مجرد، كما أنها تتجاهل عمليات الـconsole.
- البحث عن نصوص login/logout/دخول/خروج في كل app رجع ملف واحد فقط هو LoginController نفسه (بدون أي logging).

عمليات الاستيراد (import):
- لا يوجد فعل import في ActivityLog.
- لا توجد أي وظيفة استيراد أصلاً: grep على import/->import(/fromCollection/ToModel/WithHeadingRow/Excel/csv رجع صفر منطق استيراد، وروابط web.php لا تحتوي كلمة import، ولا يوجد مجلد/كلاس Import، وحزمة maatwebsite/excel غير مثبتة في vendor ولا مذكورة في composer.json.

الخلاصة: الـ activity log الجديد لا يسجّل تسجيل الدخول/الخروج (وبالتالي فقدان تتبّع محاولات الدخول الناجحة/الفاشلة مع الـ IP الموجود في الـ legacy)، ولا يوجد منطق استيراد ليُسجَّل. صحّحت الخطورة من المتوقع كونها أمنية إلى medium: غياب audit للمصادقة فجوة أمنية حقيقية لكن النظام الجديد يضيف حماية brute-force (RateLimiter) ومنع الحسابات المعطّلة، مما يخفّف الأثر جزئياً؛ وجزء الـ import غير قابل للتطبيق حالياً لعدم وجود الميزة.
- **التوصية:** تسجيل أحداث login/logout (مع IP) وعمليات الاستيراد في activity_logs، وتوسيع const ACTIONS لتشمل login/logout/import. مهم أمنياً لتتبع من دخل النظام ومتى، وهو موجود في النظام القديم.

### ⚪ بسيط — الإعدادات المالية (نسبة الضريبة/الاستقطاع/التأمينات) غائبة من الإعدادات
- **النوع:** field
- **الدليل (القديم):** /Users/mohamed/Downloads/system (2)/settings.php سطور 106-117 يحفظ tax_rate (افتراضي 14%)، retention_rate (10%)، insurance_rate (1%) في system_settings، وتُستخدم كقيم افتراضية عند إنشاء المستخلصات والفواتير (سطر 474-477)
- **الحالة في الجديد:** الادعاء صحيح جزئياً بس مبالغ في الخطورة. الجزء الصحيح: SettingController.php (/Users/mohamed/Downloads/qarwana/app/Http/Controllers/SettingController.php) فعلاً بيدعم 5 مفاتيح بس (site_name, company_name, company_phone, company_email, company_address - سطر 14)، وملف resources/views/settings/edit.blade.php صفحة واحدة بدون تبويب مالي، ومفيش tax_rate/retention_rate/insurance_rate كإعدادات عامة في جدول settings. اتأكدت من الملفين مباشرة.

لكن: (1) دليل الـlegacy غلط — نسب tax_rate/retention_rate/insurance_rate في settings.php القديم بتتقري بس جوه فورم الإعدادات نفسه (سطور 446/456/466) ومش متربطة كقيم افتراضية فعلية. فورم الفاتورة القديم بيـhardcode value="0" (invoices.php سطر 315 و469)، ومفيش أي ملف بيقرا $settings['tax_rate'] كdefault عند إنشاء فاتورة/مستخلص. يعني السلوك المدّعى (سطر 474-477 تُستخدم كقيم افتراضية) مكانش موجود أصلاً.

(2) النظام الجديد بيغطي نفس الوظيفة بشكل مختلف (per-record بدل global setting): الفواتير فيها عمود tax_rate حقيقي مع validation (InvoiceController.php سطر 104) وحساب (Invoice.php recomputeTotals سطور 66-71) — نفس سلوك الـlegacy بالظبط. فيه موديول ضرائب كامل مستقل: Tax model + migration 2026_06_03_011004_create_taxes_table.php (حقل rate و tax_type: vat/income/withholding/stamp/other) + TaxController.php — أغنى من الـlegacy. والمستخلصات فيها حقل deductions (مكافئ الاستقطاع) في ContractorExtract + migration 013001.

الناقص فعلاً وبس: إعداد عام واحد لنسب افتراضية (ضريبة 14% / استقطاع 10% / تأمين 1%) يتخزن مرة ويتعاد استخدامه، وحقل insurance_rate مخصص للتأمينات مفيش له مكافئ. ده فعلاً مفقود لكنه راحة استخدام بسيطة (ومكانش شغال في الأصل)، مش نقص وظيفي مالي حقيقي.
- **التوصية:** إضافة تبويب 'الإعدادات المالية' لحفظ نسبة الضريبة والاستقطاع (محتجزات الضمان) والتأمينات، واستخدامها كقيم افتراضية في شاشات المستخلصات والفواتير. مهم لاتساق الحسابات في شركة مقاولات.

### ⚪ بسيط — إعدادات العملة والمنطقة الزمنية غائبة
- **النوع:** field
- **الدليل (القديم):** /Users/mohamed/Downloads/system (2)/settings.php سطور 86-99 و390-408 يحفظ currency (ج.م/ر.س/د.إ/د.ك/$) و timezone (Cairo/Riyadh/Dubai/Kuwait)؛ موجودة فعلياً في بيانات system_settings (id 53 currency='ج.م'، id 54 timezone='Africa/Cairo')
- **الحالة في الجديد:** الادعاء صحيح بعد بحث معمّق. SettingController.php سطر 14: const KEYS = ['site_name','company_name','company_phone','company_email','company_address'] فقط — مفيش currency ولا timezone. الدالتين edit() و update() بتقرا/بتكتب الـ KEYS دي بس، فحتى لو الجدول key/value عام (database/migrations/2026_06_03_017002_create_settings_table.php مجرد key+value) مفيش أي مسار في الـ UI لقراءة أو حفظ عملة أو منطقة زمنية. settings/edit.blade.php فيه 5 حقول بيانات شركة بس، لا قائمة عملة (ج.م/ر.س/د.إ/د.ك/$) ولا قائمة timezone. رمز العملة 'ج'/'ج.م' متحطوط hardcoded في الـ views (dashboard.blade.php سطر 34، invoices/expenses/revenues...الخ بعد number_format) مش بيقرا من إعداد. الـ timezone ثابت في config/app.php سطر 68: 'timezone' => 'UTC' (مش حتى Africa/Cairo زي legacy) ومفيش helper ولا Setting::get('timezone') ولا date_default_timezone_set في app. الـ currency الوحيدة الموجودة هي عمود BankAccount.currency='EGP' (عملة كل حساب بنكي - مفهوم مختلف تماماً، مش إعداد عملة/locale عام للنظام). مفيش helpers ولا seeder defaults للعملة/المنطقة الزمنية. التصنيف low لأن العملة الفعلية ثابتة (مصري) والـ timezone بيأثر على عرض التواريخ بس - مش بيكسر منطق مالي.
- **التوصية:** إضافة حقلي العملة والمنطقة الزمنية للإعدادات واستخدام رمز العملة المختار في كل عروض المبالغ بدل قيمة مثبتة.

### ⚪ بسيط — صفحة سجل النشاطات بدون فلترة أو بحث
- **النوع:** ui
- **الدليل (القديم):** بنية activity_log تتضمن user_id, action, table_name, record_id, description, ip_address, created_at — قابلة للفلترة بالمستخدم/النوع/التاريخ في النظام القديم
- **الحالة في الجديد:** تم التأكد من الثغرة بعد بحث شامل. ActivityLogController في /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ActivityLogController.php له دالة index() واحدة فقط محتواها: ActivityLog::with('user')->latest('created_at')->paginate(30) — لا يوجد where ولا when ولا قراءة request() ولا أي فلترة بالمستخدم أو الفعل أو التاريخ أو نوع الكيان. الـ view في /Users/mohamed/Downloads/qarwana/resources/views/activity_logs/index.blade.php عبارة عن جدول ثابت + ترقيم صفحات فقط، بدون أي فورم فلترة أو حقل بحث (grep على request/filter/where/when/search رجع فاضي). الموديل /Users/mohamed/Downloads/qarwana/app/Models/ActivityLog.php لا يحتوي على أي query scopes للفلترة. الهجرة 2026_06_03_017003_create_activity_logs_table.php تؤكد أن الجدول يحتوي على كل الأعمدة القابلة للفلترة (user_id, action, model_type, model_id, ip_address, created_at) بل وعليها indexes على created_at و [model_type, model_id] — أي أن الفلترة مدعومة بالكامل على مستوى قاعدة البيانات لكنها غير مُنفّذة في الواجهة/الكنترولر. لا يوجد Livewire component أو كنترولر بديل يتعامل مع فلترة سجل النشاطات في أي مكان بالنظام. الخطورة منخفضة لأنها ميزة UI إضافية وليست عطلاً وظيفياً.
- **التوصية:** إضافة فلاتر (المستخدم، الفعل، نوع الكيان، نطاق التاريخ) وبحث في صفحة سجل النشاطات لتسهيل التدقيق.

### ⚪ بسيط — إحصائيات قاعدة البيانات ومعلومات النظام في صفحة الإعدادات غائبة
- **النوع:** ui
- **الدليل (القديم):** /Users/mohamed/Downloads/system (2)/settings.php تبويب 'معلومات النظام' (سطور 552-667): إصدار PHP/قاعدة البيانات/الخادم/المنطقة الزمنية + عدّادات (المستخدمين/المشاريع/العملاء/المقاولين/الموظفين/الموردين)
- **الحالة في الجديد:** تم التحقق بعمق والادعاء صحيح جزئياً مع تخفيف الخطورة. صفحة الإعدادات في النظام الجديد (/Users/mohamed/Downloads/qarwana/resources/views/settings/edit.blade.php) فعلاً فورم بسيط فقط: site_name/company_name/company_phone/company_email/company_address. و SettingController::edit (/Users/mohamed/Downloads/qarwana/app/Http/Controllers/SettingController.php سطر 24-32) يمرّر مصفوفة settings فقط بدون أي تبويب معلومات نظام أو إحصائيات.

معلومات الخادم/النظام (إصدار PHP، نظام التشغيل، الخادم، إصدار قاعدة البيانات، المنطقة الزمنية، upload_max_filesize، مسار النظام، وضع التطوير): غائبة تماماً من كل التطبيق. بحثت عن phpversion/php_uname/ini_get/upload_max_filesize/ATTR_SERVER_VERSION/getServerVersion/date_default_timezone_get/SERVER_SOFTWARE في app + resources/views + routes فلم أجد أي شيء. الوحيد الموجود app()->version() في welcome.blade.php (صفحة Laravel الافتراضية غير مرتبطة). لا يوجد أي view أو controller باسم system/info.

أما عدّادات الكيانات (المشاريع/العملاء/المقاولين/الموظفين/الموردين) فموجودة لكن في مكان مختلف وليس في الإعدادات: DashboardController (/Users/mohamed/Downloads/qarwana/app/Http/Controllers/DashboardController.php سطور 28-34) يحسب projects/clients/contractors/suppliers/employees/invoices. ملاحظتان: (1) عدّاد المستخدمين (User::count) غير موجود في الـ Dashboard، (2) هذه إحصائيات مالية/تشغيلية في لوحة التحكم وليست لوحة إحصائيات قاعدة بيانات إدارية داخل الإعدادات.

الخلاصة: تبويب معلومات النظام والخادم غائب فعلاً (مؤكَّد)، لكن جزء عدّادات الكيانات مغطّى تقريباً في الـ Dashboard. لذلك الميزة الأصلية غائبة لكن الخطورة منخفضة لأنها معلومات تشخيصية تجميلية للأدمن (يوفّرها Laravel أصلاً عبر php artisan about / صفحة debug)، والجزء العملي (العدّادات) موجود بشكل آخر.
- **التوصية:** إضافة تبويب 'معلومات النظام' يعرض إصدارات PHP/Laravel/DB وإحصائيات سريعة لعدد السجلات في الكيانات الرئيسية (اختياري لكنه مفيد للمدير).

---

## Banking & payment methods (الحسابات البنكية وطرق الدفع)

> النواة الأساسية لكشف الحساب والحركات والتحويلات موجودة ومبنية بشكل أفضل من القديم: BankLedgerService.php يحسب الرصيد الجاري اشتقاقاً من المصدر بدل تخزين balance_after (إصلاح لباج قديم)، ويستخدم DB transaction مع lockForUpdate، ويدعم الإيداع/السحب/التحويل بين حسابين والحذف مع إعادة الاحتساب. لكن النظام الجديد ناقص في عدة جوانب مهمة لشركة مقاولات: لا يوجد إطلاقاً جدول/إدارة طرق الدفع المخصّصة (custom_payment_methods) فطرق الدفع صارت ثوابت PHP ثابتة لا تُدار من الواجهة؛ جدول الحركات فقَد حقولاً جوهرية (التصنيف category، المستفيد beneficiary، رقم الشيك، تاريخ القيمة، المرفق، الربط الصريح بالمصروف/الإيراد/المشروع، علم المطابقة is_reconciled)؛ كشف الحساب بلا فلترة ولا تصدير ولا طباعة ولا بطاقات إجماليات؛ والمطابقة البنكية والشيك الآجل والتحصيل الجزئي وتكامل السحب مع عهدة الموظف كلها غير موجودة. باختصار الجزء "الدفتري" مكتمل لكن طبقات الإدارة والتقارير والتكاملات والمرونة ناقصة.

### 🟡 متوسط — حقول جوهرية محذوفة من جدول الحركات البنكية (تصنيف، مستفيد، شيك، تاريخ قيمة، مرفق، ربط صريح بالكيانات)
- **النوع:** field
- **الدليل (القديم):** esystem.sql bank_transactions سطر 1807–1831: transaction_type enum(deposit,withdrawal,transfer_in,transfer_out,fee,interest,other)، value_date، check_number، beneficiary، category enum(salary,supplier,client,rent,utilities,tax,loan,investment,transfer,other)، related_expense_id، related_revenue_id، related_project_id، related_entity_type/id، attachment_file. البيانات الفعلية تستخدمها (سطر 1846 beneficiary='محمد ميهوب' related_entity_type='custody'، سطر 1841 related_entity_type='revenue').
- **الحالة في الجديد:** أكدت الفجوة بعد بحث جدي. الجدول الجديد bank_transactions في /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_005743_create_bank_transactions_table.php فيه فقط: bank_account_id, type(string 20 = deposit|withdrawal), amount, transaction_date, description, reference_number, related_type/related_id (عام), created_by. والموديل /Users/mohamed/Downloads/qarwana/app/Models/BankTransaction.php TYPES فيها deposit/withdrawal بس. الكنترولر /Users/mohamed/Downloads/qarwana/app/Http/Controllers/BankTransactionController.php بيvalidate الأربع حقول دي بس، وفورم وكشف الحساب في /Users/mohamed/Downloads/qarwana/resources/views/bank_accounts/show.blade.php مفيهوش غيرها.

غير موجود فعلاً (الادعاء صح): beneficiary (المستفيد) — صفر نتيجة في app/resources/database؛ value_date — صفر؛ check_number — صفر؛ attachment_file — مفيش مرفقات للحركات البنكية (في project_files بس مربوط بالمشاريع في 2026_06_03_017001)؛ category enum للحركات البنكية — مش موجود (الـcategory موجودة لـ expenses/materials/assets بس). ومفيش is_reconciled/notes.

تخفيف جزئي للادعاء: (1) transfer_in/transfer_out اتعملهم جدول منفصل bank_transfers (2026_06_03_016001) + BankTransfer.php وبيولّد حركتين related_type='bank_transfer'. (2) الربط الصريح بالكيانات (related_expense_id/related_revenue_id/related_project_id/related_entity_type) محفوظ وظيفياً عن طريق related_type/related_id العام، وفعلاً بيتعبّى أوتوماتيك من ExpenseController/RevenueController/ContractorPaymentController/SupplierPaymentController/BankTransferController. عشان كده خفّضت الـseverity من high لـ medium: جزء الربط بالكيانات متغطّي، لكن الحقول الجوهرية (beneficiary, category, value_date, check_number, attachment) متشالت فعلاً والبيانات القديمة كانت بتستخدمها.
- **التوصية:** إضافة الأعمدة: beneficiary، value_date، check_number، category، attachment_file، وعرضها في فورم الإضافة وكشف الحساب، مع إتاحة الربط الصريح بمصروف/إيراد/مشروع في الواجهة (الموجود الآن related_type/related_id غير معروض ولا مُدار).

### 🟡 متوسط — كشف الحساب بدون فلترة ولا تصدير ولا طباعة ولا بطاقات إجماليات
- **النوع:** ui
- **الدليل (القديم):** bank_accounts.php: فلترة بالحساب + نوع العملية + المشروع + من/إلى تاريخ (سطر 313–360)، بطاقات إجماليات total_deposits/total_withdrawals/total_transactions (سطر 100–104)، زر تصدير Excel exportStatement() (سطر 365–366 و968)، وفلترة GET في api/bank_transactions.php سطر 48–82.
- **الحالة في الجديد:** تم التحقق بشكل عدائي والادعاء صحيح: الفيتشر فعلاً ناقصة في النظام الجديد.

الأدلة:
1) /Users/mohamed/Downloads/qarwana/app/Http/Controllers/BankAccountController.php — في show() (سطر 51-56) بيستدعي $this->ledger->statement($bank_account) من غير أي بارامترات فلترة (لا تاريخ من/إلى ولا نوع حركة ولا مشروع)، وبيمرر كل الصفوف للـ view.
2) /Users/mohamed/Downloads/qarwana/app/Services/BankLedgerService.php — دالة statement(BankAccount $account) (سطر 74-89) signature بتاخد الحساب بس، بترجّع كل الحركات orderBy(transaction_date, id) من غير whereBetween ولا where('type'). مفيش أي حساب لإجماليات الفترة.
3) /Users/mohamed/Downloads/qarwana/resources/views/bank_accounts/show.blade.php — بيعرض كارت واحد (الرصيد الحالي + الافتتاحي) وفورم إضافة حركة وجدول الحركات بس. مفيش فورم فلترة، مفيش بطاقات إجماليات إيداع/سحب/عدد حركات للفترة، مفيش زر تصدير، مفيش طباعة. الزر الوحيد "رجوع للحسابات".
4) grep على export|excel|csv|print|طباعة|تصدير في app + bank views + routes رجع فاضي تماماً.
5) grep على total_deposits|total_withdrawals|total_transactions|whereBetween|reconcile|filter في كنترولرات/خدمة البنوك رجع فاضي.
6) grep على maatwebsite|Excel|Exportable|exportStatement في app/composer.json رجع فاضي — مفيش مكتبة تصدير أصلاً.
7) routes/web.php فيه bank-accounts resource + POST transactions بس، مفيش راوت statement/export/print.
8) الـ"إجمالي" الوحيد الموجود في index.blade.php (سطر 9) = إجمالي أرصدة الحسابات النشطة، مش بطاقات إجماليات كشف الحساب.

ملاحظة على الـ severity: خفّضتها لـ medium لأن الوظيفة الجوهرية (كشف الحساب بالرصيد الجاري الصحيح) موجودة وفعلاً أحسن من القديم (الرصيد مشتق لحظياً بدل balance_after المخزّن). الناقص هو طبقة UX: الفلترة + بطاقات الإجماليات + تصدير Excel + طباعة. مش حرجة لكن feature gap حقيقية مقارنة بالقديم.
- **التوصية:** إضافة فلاتر (من/إلى تاريخ، نوع الحركة، بحث في البيان)، وبطاقات إجمالي الإيداعات/السحوبات/عدد الحركات للفترة، وزر تصدير Excel/CSV وطباعة الكشف — وظائف أساسية للمراجعة ومطابقة البنك.

### 🟡 متوسط — تكامل السحب البنكي مع عهدة الموظف غير موجود
- **النوع:** integration
- **الدليل (القديم):** api/bank_transactions.php سطر 166–205: عند السحب مع is_custody + custody_employee_id يُنشأ تلقائياً قيد employee_transactions بنوع custody ويربط الحركة (related_entity_type='custody'). البيانات الفعلية تثبت الاستخدام (bank_transactions سطر 1846). والفورم فيه toggleEmployeeCustody (bank_accounts.php سطر 991).
- **الحالة في الجديد:** الفجوة مؤكدة فعلاً بعد بحث جاد ومحاولة دحض. التفاصيل:

1) BankTransactionController::store (/Users/mohamed/Downloads/qarwana/app/Http/Controllers/BankTransactionController.php سطر 25-31) بيـvalidate بس type/amount/transaction_date/description/reference_number — مفيش is_custody ولا custody_employee_id، ومفيش أي إنشاء EmployeeTransaction.

2) جربت أدحض الادعاء عن طريق آلية الربط البوليمورفي related_type/related_id الموجودة فعلاً على جدول bank_transactions (/Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_005743_create_bank_transactions_table.php سطر 23-24). الآلية دي شغالة فعلاً وبتربط الحركة البنكية بكيانات تانية، لكن القيم المستخدمة بس: revenue, expense, supplier_payment, contractor_payment, bank_transfer (في RevenueController/ExpenseController/SupplierPaymentController/ContractorPaymentController/BankTransferController). مفيش ولا قيمة custody أو employee_transaction خالص.

3) EmployeeTransaction بيتعمل create في مكان واحد بس: EmployeeTransactionController::store (سطر 62) عن طريق فورم يدوي (resources/views/employee_transactions/form.blade.php) — أبداً مش بيتعمل أوتوماتيك من سحب بنكي. الفورم ده مفيهوش حقل بنك خالص.

4) فورم السحب البنكي (resources/views/bank_accounts/show.blade.php سطر 21-36) مفيهوش أي select لموظف أو عهدة.

5) grep على is_custody / custody_employee / toggleEmployeeCustody / related_entity_type رجع فاضي تماماً عبر كل ملفات php و blade.

ملاحظة منصفة: نوع 'custody' و'custody_return' موجودين في EmployeeTransaction::TYPES (app/Models/EmployeeTransaction.php سطر 27-28)، والبنية التحتية للربط (related_type/related_id) موجودة — يعني ينفع تتعمل بسهولة. بس التكامل الأوتوماتيكي المحدد (سحب بنكي بفلاج عهدة → إنشاء قيد عهدة تلقائي مربوط بالحركة) غير موجود. عشان كده الـseverity متوسطة مش عالية: القطعتين موجودتين بس الجسر بينهم ناقص.
- **التوصية:** إعادة التكامل: عند سحب بنكي بخيار 'عهدة موظف' يُنشأ EmployeeTransaction بنوع custody مربوط بالحساب والمشروع وبالحركة البنكية داخل نفس DB transaction، لتفادي الإدخال المزدوج وضمان تطابق رصيد العهد.

### 🟡 متوسط — اختفاء الشيك الآجل والتحصيل الجزئي (deferred_check / payment_status / paid_amount)
- **النوع:** logic
- **الدليل (القديم):** update_revenues_system.php وrevenues.php: طريقة 'deferred_check' (شيك آجل) مع payment_status (paid/pending) وpaid_amount وremaining_amount وdue_date وcheck_number وbank_name — منطق التحصيل الجزئي في revenues.php سطر 73–76 و508 و560.
- **الحالة في الجديد:** مؤكد ناقص في النظام الجديد بعد بحث عدائي شامل. /Users/mohamed/Downloads/qarwana/app/Models/Revenue.php: PAYMENT_METHODS = cash/bank_transfer/check فقط، مفيش deferred_check، والـ fillable مفيهوش payment_status/paid_amount/remaining_amount/due_date/check_number/bank_name. migration /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_010334_create_revenues_table.php أعمدته: id, project_id, description, amount, revenue_date, payment_method, bank_account_id, notes, created_by, timestamps — مفيش أي عمود تحصيل/شيك. /Users/mohamed/Downloads/qarwana/app/Http/Controllers/RevenueController.php: validation payment_method in:cash,bank_transfer,check، ومفيش action تحصيل، وبيسجل إيداع بنكي بكامل amount دايماً (مفيش paid/remaining). الـ views (form/index/show) بتعرض الـ 3 طرق بس ومفيش زرار 'تسجيل تحصيل' ولا حقول due_date/check_number/bank_name. routes/web.php فيها Route::resource('revenues') بس من غير route تحصيل. ومفيش جدول revenue_collections ولا custom_payment_methods (اللي كانوا موجودين في legacy update_revenues_system.php). تنويه مهم (تفنيد جزئي): مفهوم 'التحصيل الجزئي' نفسه موجود لكن على فاتورة العميل مش على الإيراد — /Users/mohamed/Downloads/qarwana/app/Models/Invoice.php فيه paid_amount وstatus 'partial' (مدفوعة جزئياً) وdue_date، لكن ده domain تاني (فواتير) ومش بديل للإيراد، وكمان InvoiceController نفسه مفيهوش logic بيحدّث paid_amount/status، ومفيش deferred_check ولا check_number/bank_name في أي حتة. فالميزة المطلوبة (شيك آجل + تحصيل جزئي على الإيرادات) ناقصة فعلاً. الخطورة medium مش critical لأن تسجيل الإيراد الأساسي شغال ومفهوم الدفع الجزئي ممثّل جزئياً في الفواتير.
- **التوصية:** إضافة طريقة deferred_check وحقول payment_status/paid_amount/remaining_amount/due_date/check_number/bank_name مع منطق المُحصَّل مقابل المعلّق — مهم لتتبّع شيكات العملاء الآجلة.

### ⚪ بسيط — طرق الدفع المخصّصة (custom_payment_methods) محذوفة بالكامل — لا جدول ولا إدارة
- **النوع:** module
- **الدليل (القديم):** esystem.sql سطر 2407–2413 CREATE TABLE custom_payment_methods (name, description, is_active). تُستخدم ديناميكياً في revenues.php سطر 96 SELECT * FROM custom_payment_methods WHERE is_active=1، وعمود custom_payment_method على revenues (update_revenues_system.php سطر 49) مع خيار 'other' في فورم الإيراد (revenues.php سطر 671 و1152).
- **الحالة في الجديد:** الـgap مؤكّد بعد بحث عدائي شامل في qarwana. مفيش أي أثر لطرق دفع مخصّصة ديناميكية:

1) لا جدول ولا migration: مفيش جدول custom_payment_methods. الـmigrations الوحيدة المتعلقة بطرق الدفع بتضيف عمود نصّي ثابت بس: $table->string('payment_method', 20)->default('cash') مع تعليق // cash/bank_transfer/check في:
- /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_010334_create_revenues_table.php (سطر 20)
- /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_010334_create_expenses_table.php
- /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_012002_create_supplier_payments_table.php
- /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_013002_create_contractor_payments_table.php
مفيش جدول lookup لطرق الدفع ولا عمود is_active.

2) لا عمود custom_payment_method: grep شامل على كل المشروع (باستثناء vendor) لـ custom_payment/customPayment رجع فاضي تماماً. migration الـrevenues مفهوش العمود ده خالص.

3) لا موديل: مفيش CustomPaymentMethod.php ولا أي ملف *PaymentMethod* في /Users/mohamed/Downloads/qarwana/app/Models (اتأكدت من قائمة الموديلات كلها).

4) لا شاشة إدارة/تعطيل: SettingController موجود (/Users/mohamed/Downloads/qarwana/app/Http/Controllers/SettingController.php) لكنه key/value ثابت محصور في 5 مفاتيح بس (site_name, company_name, company_phone, company_email, company_address) — مش بيدير طرق دفع نهائياً، فمش بديل معقول.

5) الثوابت الثابتة مؤكّدة: const PAYMENT_METHODS = [cash, bank_transfer, check] مكرّرة في Revenue.php (سطر 10)، Expense.php (سطر 20)، SupplierPayment.php (سطر 10)، ContractorPayment.php (سطر 10). الـController بيعمل validation in:cash,bank_transfer,check والـforms بتعمل @foreach على الثابت. مفيش مصدر ديناميكي ولا خيار other مدعوم بطريقة مخصّصة.

الخلاصة: الـfeature غايبة فعلاً. صحّحت الـseverity لـlow لأنها مجرد lookup table صغيرة (طرق دفع قابلة للإضافة/التعطيل) — قيمتها التشغيلية محدودة والطرق الأساسية الثلاثة موجودة، فالأثر منخفض مش critical.
- **التوصية:** إنشاء جدول وموديل custom_payment_methods (name, description, is_active) مع شاشة إعدادات لإدارتها (إضافة/تعطيل)، وإضافة عمود custom_payment_method للإيرادات/المدفوعات، ودمج هذه الطرق المخصّصة مع القائمة الثابتة في كل فورمات الدفع — هذه أهم فجوة لأنها وظيفة كان المستخدم يتحكّم بها بنفسه واختفت تماماً.

### ⚪ بسيط — المطابقة البنكية (Reconciliation) غير موجودة
- **النوع:** logic
- **الدليل (القديم):** esystem.sql bank_transactions سطر 1827 is_reconciled tinyint(1) DEFAULT 0 'تمت المطابقة'، وميزة 'كشف حساب مشابه للبنوك' في setup_bank_accounts.php سطر 161.
- **الحالة في الجديد:** حاولت أدحض الادعاء بحث واسع، والنتيجة إن ميزة "المطابقة البنكية" (reconciliation / is_reconciled) فعلاً مش موجودة في النظام الجديد — بس لازم نفصل بين حاجتين خلطهم الادعاء في نفس البند:

1) عمود/منطق المطابقة (is_reconciled): مفقود فعلاً.
- migration /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_005743_create_bank_transactions_table.php فيه بس: type, amount, transaction_date, description, reference_number, related_type, related_id, created_by — مفيش is_reconciled ولا أي flag مكافئ.
- Model /Users/mohamed/Downloads/qarwana/app/Models/BankTransaction.php مفيهوش is_reconciled في fillable ولا casts.
- /Users/mohamed/Downloads/qarwana/app/Services/BankLedgerService.php و /Users/mohamed/Downloads/qarwana/app/Http/Controllers/BankTransactionController.php مفيهمش أي منطق مطابقة/toggle.
- grep واسع على (reconcil|مطابقة|تسوية|matched|cleared) عبر app/database/resources/routes رجع فاضي. الـ hit الوحيد كان 'settlement' => 'تسوية' في /Users/mohamed/Downloads/qarwana/app/Models/PartnerTransaction.php وده تصفية حساب شريك مش مطابقة بنكية.
- view /Users/mohamed/Downloads/qarwana/resources/views/bank_accounts/show.blade.php مفيهوش أي checkbox/UI للمطابقة.

2) لكن الجزء التاني من دليل الـ legacy (ميزة "كشف حساب مشابه للبنوك" في setup_bank_accounts.php سطر 161) ده مش مفقود — موجود في الجديد:
- BankLedgerService::statement() بيحسب running balance لحظياً.
- route bank_accounts.show + view bank_accounts/show.blade.php (@section title 'كشف حساب') + لينك "كشف حساب" في index.blade.php سطر 46.
فالـ statement موجود، اللي ناقص هو flag المطابقة بس.

تأكيد دليل الـ legacy: esystem.sql سطر 1827 فعلاً `is_reconciled` tinyint(1) DEFAULT 0 COMMENT 'تمت المطابقة'.

خفّضت الـ severity لـ low: is_reconciled في الـ legacy مجرد flag بسيط (مفيش جدول bank_statements ولا منطق auto-match ولا workflow مطابقة فعلي في القديم)، والنظام الجديد عنده كشف حساب كامل + reference_number لكل حركة اللي بيخدم نفس غرض التتبع. فهي ميزة ناقصة فعلاً بس تأثيرها محدود.
- **التوصية:** إضافة is_reconciled (ويفضّل reconciled_at/reconciled_by) وإمكانية تعليم الحركة كمطابَقة من كشف البنك، مع فلتر 'غير مطابَق' لتسهيل التسوية الشهرية.

### ⚪ بسيط — رسوم التحويل لا تُسجَّل كحركة/تصنيف مستقل من نوع fee
- **النوع:** logic
- **الدليل (القديم):** api/bank_transfers.php سطر 138–153: لو fees>0 يُنشأ قيد منفصل transaction_type='fee' بمبلغ الرسوم مع transfer_out للمبلغ الأساسي (سطر 120–134)، فتظهر الرسوم كبند مستقل في الكشف.
- **الحالة في الجديد:** بعد بحث معمّق الادعاء صحيح: مفيش نوع حركة مستقل 'fee'. الأدلة:

1) /Users/mohamed/Downloads/qarwana/app/Models/BankTransaction.php سطر 10-13: TYPES فيها deposit و withdrawal بس، لا يوجد 'fee'.

2) /Users/mohamed/Downloads/qarwana/app/Http/Controllers/BankTransferController.php سطر 59-67: بيعمل post واحد بس type='withdrawal' و amount = bcadd(amount, fees) (المبلغ + الرسوم مدموجين) مع وصف 'تحويل إلى ... (شامل رسوم)'. مفيش قيد ثاني للرسوم. الإيداع في الوجهة (سطر 68-76) بالمبلغ الأساسي فقط. يعني الرسوم بتتبلع جوّه السحب كقيمة واحدة — مطابق تماماً لوصف الادعاء.

3) /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_005743_create_bank_transactions_table.php سطر 17: type string(20) والتعليق "deposit | withdrawal" — مفيش أي تصنيف fee على مستوى الـ DB.

4) قيمة fees بتتخزّن كعمود منفصل على جدول bank_transfers بس (migration 2026_06_03_016001 سطر 16، و BankTransfer model سطر 11)، وبتتعرض في صفحة قائمة التحويلات فقط (/Users/mohamed/Downloads/qarwana/resources/views/bank_transfers/index.blade.php سطر 25). لكنها مش بتظهر كبند مستقل في كشف الحساب (bank_accounts/show.blade.php سطر 68-69 بيعرض deposit/withdrawal بس).

5) BankLedgerService::statement (app/Services/BankLedgerService.php) بيحسب الرصيد الجاري من deposit/withdrawal فقط، فالرسوم مش قابلة للتصنيف/التجميع كنوع fee في أي تقرير. مفيش أي groupBy حسب type أو sum('fees') في أي تقرير.

ملاحظة على الشدّة: خفّضتها لـ low لأن المبلغ المالي صح (الرسوم بتتخصم فعلاً من الرصيد ومش بتختفي حسابياً)، وقيمة الرسوم متسجّلة ومرئية على مستوى التحويل. الناقص هو تمثيل الرسوم كبند/تصنيف مستقل 'fee' في الكشف وقابليته للتقرير — ده فرق وظيفي/تقريري حقيقي لكنه مش بيكسر صحة الأرصدة.
- **التوصية:** إنشاء حركة منفصلة بتصنيف 'رسوم' لمبلغ fees لتظهر مستقلة في الكشف والتقارير وتسهيل تتبّع مصاريف الرسوم البنكية.

### ⚪ بسيط — حقول الحساب البنكي الناقصة: نوع الحساب وSWIFT
- **النوع:** field
- **الدليل (القديم):** esystem.sql bank_accounts سطر 1780 swift_code، سطر 1785 account_type enum(current,savings,business).
- **الحالة في الجديد:** الـ claim صحيح بعد بحث شامل في كل طبقات النظام الجديد.

الـ Migration /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_005743_create_bank_accounts_table.php بيحتوي على: name, bank_name, account_number, iban, branch, currency, opening_balance, current_balance, is_active, notes, created_by, timestamps — مفيش swift_code ولا account_type.

تأكيد إضافي في باقي الطبقات:
- Model /Users/mohamed/Downloads/qarwana/app/Models/BankAccount.php: الـ $fillable (سطر 11-14) مافيهوش swift_code ولا account_type.
- Controller /Users/mohamed/Downloads/qarwana/app/Http/Controllers/BankAccountController.php: دالة validateData (سطر 83-94) مافيهاش validation rules للحقلين دول.
- View /Users/mohamed/Downloads/qarwana/resources/views/bank_accounts/form.blade.php: الفورم مافهوش input لـ SWIFT ولا dropdown لنوع الحساب (current/savings/business).
- بحث grep على كل المشروع (app, resources, routes, database) عن swift و account_type رجع نتائج في الـ vendor (fakerphp/monolog) بس، مفيش أي استخدام فعلي في كود التطبيق.

ملاحظة على الـ severity: خفّضتها لـ low لأن الحقلين دول informational/optional بطبيعتهم (نوع الحساب وSWIFT للتحويلات الدولية) ومش بيأثروا على منطق الـ ledger أو الأرصدة في النظام الجديد، لكن الغياب فعلي ومؤكد.
- **التوصية:** إضافة swift_code وaccount_type (جاري/توفير/تجاري) للهجرة والفورم — تأثير محدود لكنها مفيدة للتحويلات الدولية وتصنيف الحسابات.

---
