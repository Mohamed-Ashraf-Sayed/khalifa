# تقرير النواقص — الجولة الثانية (بعد الـ10 موجات)

**تاريخ:** 2026-06-04  
**الطريقة:** 6 وكلاء تدقيق + تحقق عكسي (65 وكيل).  
**الإجمالي:** 44 نقص مؤكد متبقّي.

---

## Enterprise/professional features a large company expects but are MISSING

> النظام المُعاد بناؤه ناضج وظيفياً (47 جدول، 44 كنترولر) لكنه لسه ناقص الطبقة المؤسسية اللي شركة مقاولات كبيرة بتعتمد عليها يومياً. فحصت routes/web.php، app/Http/Controllers، app/Models، database/migrations، app/Services، database/seeders، resources/views/layouts/app.blade.php، routes/console.php و .env. النتيجة: لا يوجد مركز إشعارات/تنبيهات في الهيدر (فواتير متأخرة، دفعات مستحقة، مخزون منخفض، أقساط أرباح شركاء مستحقة)، ولا بحث عام (global search)، ولا soft-deletes/سلة محذوفات واسترجاع، ولا bulk actions على القوائم. إدارة الأدوار والصلاحيات موجودة كـ seeder فقط بدون شاشة إدارة (RoleController مفقود) والصلاحيات خشنة (CRUD فقط بدون نطاق على مستوى المشروع/السجل). إدارة المستخدمين ناقصة (مفيش reset/force password، مفيش last_login). مفيش 2FA/أمان متقدم، ومفيش بنية بريد/إشعارات فعلية (MAIL_MAILER=log، لا Mailable ولا Notification ولا جدول notifications رغم Notifiable)، ومفيش jobs ولا scheduled reminders (console.php فيه inspire بس وKernel مش موجود). سجل النشاط (activity_logs) بيسجل action/model/id بس من غير قيم قديمة→جديدة (مفيش audit حقيقي). المرفقات على المشاريع فقط (مفيش polymorphic attachable). مفيش multi-branch/multi-company ولا فترات مالية. الداشبورد ثابت آخر 6 شهور بدون date-range ولا drill-down. ومفيش backup/export-all للنظام كله.

### 🟠 عالي — شاشة إدارة الأدوار والصلاحيات (Roles & Permissions UI) مفقودة — الأدوار من seeder فقط
- **النوع:** security
- **الدليل:** الشركة الكبيرة محتاجة الأدمن يعدّل صلاحيات الأدوار وينشئ أدوار جديدة من غير ما يلمس الكود. spatie/permission متاح بالكامل.
- **الحالة الحالية:** CONFIRMED MISSING after adversarial search. No RoleController/PermissionController among the 49 controllers in app/Http/Controllers/. routes/web.php has zero role/permission routes (only Route::resource('users', UserController::class) at line 113). No role/permission views (find + grep both empty). No Permission::create / givePermissionTo / syncPermissions / Role::create anywhere in app/, routes/,
- **التوصية:** إضافة RoleController (CRUD للأدوار) + matrix UI لتعيين الصلاحيات (checkboxes per permission) لكل دور، وإمكانية إسناد صلاحيات مباشرة للمستخدم (givePermissionTo) من شاشة المستخدم بدل الاعتماد على الـ seeder.

### 🟠 عالي — Soft-deletes وسلة محذوفات (Trash/Restore) غير موجودة — الحذف نهائي
- **النوع:** logic
- **الدليل:** في بيانات مالية حساسة (فواتير، مستخلصات، مدفوعات، مشاريع) الحذف النهائي خطر تشغيلي ومحاسبي؛ المؤسسات بتتطلب استرجاع وأرشفة.
- **الحالة الحالية:** CONFIRMED missing after adversarial search of /Users/mohamed/Downloads/qarwana (61 migrations, 43 models). (1) grep over database/migrations: ZERO softDeletes()/deleted_at. (2) grep over app/Models: ZERO use of SoftDeletes trait; verified Invoice.php imports only Model/BelongsTo/HasMany. (3) grep over routes/: ZERO trash/restore/forceDelete/withTrashed/onlyTrashed routes; no archive/recycle migrat
- **التوصية:** إضافة softDeletes() لجداول الكيانات الأساسية والمالية، وtrait SoftDeletes للموديلز، وصفحة 'سلة المحذوفات' بصلاحية restore/forceDelete (للأدمن فقط).

### 🟠 عالي — تذكيرات مجدولة ومهام خلفية (Scheduled Reminders / Queue Jobs) غير مفعّلة
- **النوع:** integration
- **الدليل:** التنبيه التلقائي بالفواتير المتأخرة/الدفعات المستحقة/الأقساط لازم يشتغل بدون تدخل بشري عبر cron + queue — أساس أي نظام مؤسسي.
- **الحالة الحالية:** CONFIRMED MISSING after adversarial search. routes/console.php has only the default 'inspire' command — no $schedule/withSchedule, no ->daily()/->everyMinute()/->command()/->job() anywhere (bootstrap/app.php registers no scheduled tasks either). No app/Console/ directory exists at all (app subdirs are only Providers, Models, Http, Services) — no Kernel.php, no Commands. No app/Jobs/ directory; zer
- **التوصية:** إضافة scheduled command (مثلاً invoices:remind) يشتغل يومياً يولّد الإشعارات/الإيميلات للمتأخرات والدفعات المستحقة وأقساط أرباح الشركاء، وتشغيله عبر schedule:run في الـ cron.

### 🟠 عالي — مصادقة ثنائية وإعدادات أمان متقدمة (2FA / Security) مفقودة
- **النوع:** security
- **الدليل:** حماية حسابات المالية/الإدارة بمصادقة ثنائية أصبحت معياراً، خصوصاً مع صلاحيات على المعاملات المالية.
- **الحالة الحالية:** CONFIRMED MISSING after adversarial search. No 2FA anywhere: composer.json/composer.lock have 0 hits for fortify/jetstream/google2fa/pragmarx/TOTP; User model (app/Models/User.php) has no two_factor_secret/recovery_codes/confirmed_at columns or casts; only migration of note is 2026_06_04_000070_create_login_attempts_table.php (no 2FA/OTP/recovery table); routes/web.php has no 2FA/verify routes (on
- **التوصية:** تفعيل Laravel Fortify أو pragmarx/google2fa لإضافة 2FA اختياري/إجباري للأدوار الحساسة، مع تبويب أمان في إعدادات المستخدم (تفعيل 2FA، سياسة كلمة المرور).

### 🟠 عالي — نسخ احتياطي وتصدير كامل للنظام (Backup / Export-all) مفقود
- **النوع:** integration
- **الدليل:** حماية بيانات الشركة المالية تتطلب نسخ احتياطي دوري وإمكانية تصدير كامل عند التدقيق أو الترحيل.
- **الحالة الحالية:** CONFIRMED MISSING after a genuine adversarial search. Verified facts:

1) composer.json require contains only laravel/framework, laravel/tinker, spatie/laravel-permission. No spatie/laravel-backup, no mysqldump wrapper, no dompdf/excel export lib.

2) routes/web.php (lines 73-76) has only the 4 data-port routes: index, {entity}/template, {entity}/export, {entity}/import. No backup, dump, snapshot,
- **التوصية:** إضافة spatie/laravel-backup مع جدولة يومية للنسخ الاحتياطي (DB + الملفات)، وزر 'تصدير كامل' للأدمن يولّد أرشيف بكل البيانات الأساسية.

### 🟡 متوسط — مركز إشعارات وتنبيهات في الهيدر (Notifications Center) مفقود تماماً
- **النوع:** module
- **الدليل:** شركة مقاولات بتدير عشرات الفواتير والمستخلصات والدفعات؛ النظام نفسه بيحسب: invoices_unpaid في DashboardController، وحالات overdue، وpartner_profit_schedules بتواريخ استحقاق، وlow-stock report للمواد، وحالة دفع المستخلصات. الداتا موجودة بس مفيش أي سطح بينبّه عليها.
- **الحالة الحالية:** CONFIRMED MISSING after adversarial search. No app/Notifications dir, no Notification model, no NotificationController, no notifications migration in database/migrations/. routes/web.php returns ZERO matches for notification/alert/reminder/markAsRead/unread/تنبيه/اشعار. The header in resources/views/layouts/app.blade.php (lines 111-129) is ONLY a user-profile dropdown (profile + logout); the "aler
- **التوصية:** إضافة جدول notifications (php artisan notifications:table) + NotificationController، وعرض جرس بعدّاد في app.blade.php يجمع: فواتير متأخرة/مستحقة، دفعات موردين/مقاولين قادمة، أقساط أرباح شركاء مستحقة (partner_profit_schedules)، ومخزون منخفض. مع صفحة قائمة كاملة وزر markAsRead/markAllAsRead.

### 🟡 متوسط — تتبّع تغييرات على مستوى الحقل (Field-level Audit: قيم قديمة→جديدة) مفقود
- **النوع:** security
- **الدليل:** في النظام المالي لازم نعرف مين غيّر مبلغ فاتورة من كام لكام ومتى — مش بس 'حصل تعديل'. ده مطلب رقابي/تدقيقي أساسي.
- **الحالة الحالية:** CONFIRMED MISSING after adversarial search. The claim holds across every surface I checked.

Evidence:
1) Migration /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_017003_create_activity_logs_table.php defines only: user_id, action(20), model_type(100), model_id, description(500), ip_address, created_at. There is NO old_values / new_values / properties / changes JSON column. No CHE
- **التوصية:** توسيع activity_logs بعمودي old_values وnew_values (json)، وتعبئتها من model events باستخدام getDirty()/getOriginal()، وعرض diff في صفحة السجل. أو تبني spatie/laravel-activitylog بخاصية logOnly + الـ properties.

### 🟡 متوسط — بحث عام عبر الكيانات (Global Search) في الهيدر مفقود
- **النوع:** ui
- **الدليل:** مع عشرات الآلاف من السجلات (عملاء/موردين/مقاولين/فواتير/مشاريع) الموظف محتاج صندوق بحث واحد يوصّله لأي سجل بسرعة — معيار أساسي في أنظمة ERP.
- **الحالة الحالية:** CONFIRMED MISSING after adversarial search. (1) routes/web.php (181 lines) has zero 'search'/'globalSearch'/'find'/'lookup' route references; no route named *search exists (grep EXIT:2 no-match). (2) No SearchController and no search/globalSearch/quickSearch method in any of the ~44 controllers. (3) Header in resources/views/layouts/app.blade.php (lines 109-136) contains only @yield('title') and a
- **التوصية:** إضافة route /search + SearchController يبحث في الكيانات الأساسية (clients, projects, contractors, suppliers, invoices, materials) ويرجّع نتائج مجمّعة، مع صندوق بحث في الهيدر (autocomplete).

### 🟡 متوسط — مرفقات متعددة على باقي الكيانات (Polymorphic Attachments) ناقصة
- **النوع:** module
- **الدليل:** الفواتير، المستخلصات، أوامر الشراء، العقود، الموردين، الموظفين كلهم محتاجين مستندات مرفقة (عقد موقّع، فاتورة ضريبية، صورة شيك). حالياً المرفقات على المشاريع فقط.
- **الحالة الحالية:** CONFIRMED MISSING after exhaustive search. (1) grep for morphs|attachable|fileable|polymorph|morphMany|morphTo|morphOne across all migrations AND entire app/ directory returned ZERO results — no polymorphic relation exists anywhere. (2) project_files migration (2026_06_03_017001) uses foreignId('project_id')->constrained('projects') exclusively; ProjectFile model only has belongsTo(Project) and be
- **التوصية:** إنشاء جدول attachments بـ morphs('attachable') + AttachmentController عام، وربطه بكل الكيانات المالية/التعاقدية بدل القَصْر على المشاريع.

### 🟡 متوسط — تعدد الفروع/الشركات وفترات مالية (Multi-branch / Fiscal Periods) غير مدعوم
- **النوع:** accounting
- **الدليل:** شركات المقاولات الكبيرة بتشتغل بفروع/كيانات قانونية متعددة وتقفل سنوات مالية؛ بدون فترة مالية مفيش 'قفل' للبيانات المرحّلة.
- **الحالة الحالية:** CONFIRMED MISSING after exhaustive search across migrations, models, controllers, routes, and views.

Findings:
- Migrations: searched all 42+ migration files for company_id / branch_id / fiscal_year_id / period_id / accounting_period_id / tenant_id FK columns -> ZERO matches. No companies, branches, fiscal_years, or accounting_periods table exists. The ONLY 'branch' reference is bank_accounts.bra
- **التوصية:** إضافة جدول fiscal_periods مع حالة (مفتوحة/مقفولة) ومنع التعديل على المعاملات داخل فترة مقفولة. وإذا متوقع تعدد كيانات، إضافة company_id/branch_id على الكيانات الأساسية مع scoping.

### ⚪ بسيط — بنية بريد إلكتروني/إشعارات فعلية (Email/Mail) غير مطبّقة
- **النوع:** integration
- **الدليل:** إرسال الفاتورة للعميل بالإيميل، تنبيه الموظف بمهمة، إرسال رابط استعادة كلمة المرور — كلها متوقعة في النظام المؤسسي.
- **الحالة الحالية:** CONFIRMED MISSING after an exhaustive adversarial search. No app/Mail or app/Notifications directories exist (app/ has only Http, Models, Providers, Services). No class extends Mailable or Notification anywhere in app/ (grep for extends Mailable/Notification/MailMessage = 0 hits). No Mail::/Notification:: facade calls and no ->notify() calls. The only relevant code is `use Illuminate\Notifications
- **التوصية:** إعداد SMTP، وإنشاء Mailable للفاتورة (مع PDF مرفق) وNotification عبر القناتين database+mail، وربطها بالأحداث (إرسال فاتورة، استحقاق دفعة).

### ⚪ بسيط — إجراءات جماعية على القوائم (Bulk Actions) مفقودة
- **النوع:** ui
- **الدليل:** في قوائم بمئات السجلات، الحذف/التصدير/تغيير الحالة لمجموعة دفعة واحدة بيوفّر وقت كبير — متوقع في كل قائمة ERP.
- **الحالة الحالية:** CONFIRMED missing. Genuine adversarial search found no bulk/mass/batch capability anywhere in /Users/mohamed/Downloads/qarwana. routes/web.php (and all routes): zero matches for bulk|mass|batch; every destroy route is single-model-bound (e.g. materials.destroy/{material}). Controllers: no bulk*/batch*/destroyMany/deleteMany methods; the only whereIn() calls are status/category filters in ReportCon
- **التوصية:** إضافة checkbox + 'تحديد الكل' في القوائم الرئيسية مع شريط إجراءات جماعية (حذف/تصدير/تغيير حالة) وroutes bulkDestroy/bulkUpdate محمية بالصلاحيات.

### ⚪ بسيط — لوحة تحكم بدون نطاق تاريخ أو drill-down
- **النوع:** report
- **الدليل:** المدير المالي محتاج يحدد فترة (ربع سنة/سنة) ويضغط على رقم ليوصل للتفاصيل — معيار في dashboards المؤسسية.
- **الحالة الحالية:** PARTIALLY CONFIRMED but claim is overstated; severity downgraded to low. CONFIRMED: DashboardController.php (__invoke(): View, no Request param; line 38 hardcodes now()->subMonths for a fixed 'آخر 6 شهور' trend; route web.php:62 is parameterless) — the dashboard itself has NO date-range or quarter/year period selector. REFUTED parts: (1) The claim 'الأرقام عرض فقط بدون روابط drill-down' is factual
- **التوصية:** إضافة فلتر date-range للوحة التحكم (from/to) يعيد حساب كل الإحصائيات، وجعل بطاقات الأرقام روابط للقوائم المفلترة المقابلة.

---

## Cross-module INTEGRATION & flow-completeness gaps

> الربط بين الموديولات أقوى مما يبدو في بعض النقاط: مدفوعات الموردين/المقاولين/المصروفات/التحصيلات تستدعي فعلياً BankLedgerService وتحدّث رصيد البنك (تحققت من ContractorPaymentController و PartnerDepositController)، وصفحات عرض المورد والمقاول تربط بأوامر الشراء/المستخلصات/الدفعات، وصفحة عرض إيداع الشريك تعرض جدول صرف الأرباح. لكن تبقى فجوات تكامل حقيقية ومؤثرة: (1) لوحة التحكم (DashboardController) لا تُظهر إطلاقاً التحصيلات/الأقساط المستحقة، أرباح الشركاء المستحقة (partner_profit_schedules)، الأصناف منخفضة المخزون، ولا الفواتير/المستخلصات المتأخرة — رغم وجود البيانات والمنطق جاهزاً. (2) لا توجد شاشة "Project 360": موديل Project لا يحتوي علاقات بالتكاليف/العقود/الفواتير/أوامر الشراء/المستخلصات/الشركاء رغم وجود project_id في كل تلك الجداول، وصفحة عرض المشروع تعرض الفريق والمواد فقط. (3) جدول طرق الدفع المخصّصة (custom_payment_methods) معزول تماماً — كل قوائم اختيار payment_method في الـviews تستخدم ثوابت PAYMENT_METHODS الثابتة ولا تُدمج الطرق المخصّصة. (4) سجل النشاط لا يميّز الإجراءات المهنية (اعتماد/استلام أمر شراء، تسوية إيداع، صرف ربح) — يسجّلها كـ"updated" عام فقط، و PartnerDeposit/InventoryMovement/الدفعات أصلاً خارج قائمة AUDITED. (5) تقييم المخزون غير متّسق: استلام أمر الشراء يحدّث الكمية فقط ولا يحدّث material.unit_price، فقيمة المخزون تبقى مبنية على آخر سعر يدوي لا على التكلفة الفعلية للشراء. (6) لا يوجد تقرير ربحية مشروع موحّد يجمع الإيرادات الفعلية + المصروفات + المستخلصات + استهلاك المواد + تكاليف BOQ في P&L واحد.

### 🟠 عالي — غياب شاشة Project 360: موديل Project بلا علاقات بالتكاليف/العقود/الفواتير/أوامر الشراء/المستخلصات/الشركاء
- **النوع:** integration
- **الدليل:** أهم توقّع في نظام مقاولات هو رؤية المشروع موحّدة: إيراداته، مصروفاته، عقوده، فواتيره، أوامر شرائه، مستخلصات مقاوليه، شركاؤه، واستهلاك مواده في مكان واحد. الجداول التالية كلها تحمل project_id (تأكدت من الـmigrations): expenses, revenues, purchase_orders, contractor_extracts, invoices, project_contracts, project_costs، وpartners مربوط بمشروع (add_project_to_partners).
- **الحالة الحالية:** CONFIRMED missing. Verified against /Users/mohamed/Downloads/qarwana.

1) app/Models/Project.php (lines 45-85) defines relations ONLY for: client, manager, creator, revenues, expenses, projectEmployees, assignedEmployees, materialConsumptions. There is NO projectCosts / contracts / invoices / purchaseOrders / extracts / partners relation. Confirmed by grep of the model.

2) ProjectController::show
- **التوصية:** أضف علاقات HasMany في موديل Project لـ projectCosts و contracts (ProjectContract) و invoices و purchaseOrders و extracts (ContractorExtract)، وعلاقة بالشركاء. وسّع show() ليحمّلها ويعرض في صفحة المشروع تبويبات/بطاقات: العقود، الفواتير، أوامر الشراء، المستخلصات، التكاليف (BOQ)، الشركاء، مع روابط لكل سجل وملخص مالي علوي (قيمة العقد مقابل إجمالي التكاليف الفعلية والإيرادات والربح المتوقع).

### 🟡 متوسط — لوحة التحكم لا تُظهر المستحقات والتنبيهات الحرجة (تحصيلات/أرباح شركاء/مخزون منخفض/متأخرات)
- **النوع:** integration
- **الدليل:** شركة مقاولات كبيرة تتوقع أن تكون لوحة التحكم مركز قرار: تُظهر الأقساط/التحصيلات المستحقة قريباً، أرباح الشركاء المستحقة الصرف، الأصناف تحت حد الطلب، والفواتير/المستخلصات المتأخرة. كل هذه البيانات والمنطق موجود فعلاً (RevenueCollection، PartnerProfitSchedule بحقول due_date/is_paid، Material::isLowStock()/scopeLowStock()، إعداد low_stock_alert في SettingController).
- **الحالة الحالية:** CONFIRMED missing. DashboardController.php (/Users/mohamed/Downloads/qarwana/app/Http/Controllers/DashboardController.php) imports only BankAccount, Client, Contractor, Employee, Expense, Invoice, Project, Revenue, Supplier. It computes totals (revenue/expense/net), bank balance, project/client/contractor counts, unpaid-invoice count, 6-month charts, and top contractors by balance. It NEVER refere
- **التوصية:** أضف إلى DashboardController استعلامات: PartnerProfitSchedule::where('is_paid',false)->where('due_date','<=',now()->addDays(30)) للأرباح المستحقة، Material->lowStock()->count() مع قائمة بأقل 5 أصناف، Invoice المتأخرة (due_date<now وغير مدفوعة)، ContractorExtract المعتمدة غير المدفوعة، والتحصيلات/الأقساط القادمة من invoice_payments/expense_payments. اعرضها كبطاقات تنبيه (alert cards) قابلة للنقر تنقل لصفحة الكيان المفلتر، واربط ظهور تنبيه المخزون بإعداد low_stock_alert.

---

## Accounting-grade depth (الأرصدة الافتتاحية، إقفال الفترات، المحتجزات، شهادات الخصم، سجل الشيكات، حدود الائتمان، مراكز التكلفة، والدفتر العام)

> النظام الجديد متقدّم جداً في احتساب الأرصدة المُشتقّة لكل كيان (مورّد/مقاول/شريك/بنك) بدل عمود balance_after المُعرّض للـdrift، وعنده تقارير ميزانية وقائمة دخل وضرائب VAT بفلاتر تاريخ. لكن من ناحية العمق المحاسبي فيه فجوات حقيقية موروثة وعملية لشركة مقاولات: (1) الأرصدة الافتتاحية للموردين والمقاولين والشركاء مش موجودة خالص — والأخطر إن واجهة كشف حساب المورّد/المقاول/الشريك فيها سطر "رصيد افتتاحي" متهيّأ بصفر ثابت (hardcoded 0.00) من غير عمود يغذّيه، فأي ترحيل لبيانات قديمة هيضيّع أرصدة افتتاحية حقيقية (شفنا في الـSQL القديم رصيد 35150 كـ transaction_type='opening_balance'). (2) مفيش تتبّع لمبالغ محتجز الضمان (retention) وتحريرها رغم وجود retention_percent اسمياً على العقد فقط. (3) مفيش شهادات خصم وإضافة قابلة للطباعة رغم وجود كل أعمدة الاستقطاعات. (4) مفيش سجل شيكات (دورة حياة الشيك: قيد/إيداع/تحصيل/ارتداد). (5) مفيش حدّ ائتماني (credit_limit) ولا شروط سداد (payment_terms) رغم وجودهم في القديم. (6) الميزانية العمومية "as of now()" بدون تاريخ ولا إقفال فترة/سنة مالية. (7) مفيش مراكز تكلفة مستقلة. أما الدفتر العام مزدوج القيد فهو "للدراسة" مش إلزامي لـSME. تعدّد العملات مش مطلوب لأن القديم كله EGP.

### 🟠 عالي — الأرصدة الافتتاحية للموردين/المقاولين/الشركاء غير موجودة (وكشف الحساب بيعرض صفر ثابت)
- **النوع:** accounting
- **الدليل:** في esystem.sql القديم: suppliers فيها `opening_balance` + `current_balance`، contractors فيها `opening_balance`، و supplier_payments عندها transaction_type enum يشمل 'opening_balance' مع صف فعلي برصيد 35150.00 ('رصيد افتتاحي'). دي بيانات حقيقية لازم تترحّل.
- **الحالة الحالية:** CONFIRMED MISSING after a genuine adversarial search. Every claim element checks out:

(1) No opening_balance/current_balance columns. database/migrations/2026_06_03_005002_create_suppliers_table.php, .../2026_06_03_005001_create_contractors_table.php, and .../2026_06_03_005004_create_partners_table.php define no such columns. Grep for "opening_balance" across all migrations returns ONLY bank_acco
- **التوصية:** ضيف عمود opening_balance (decimal 15,2) على suppliers و contractors و partners، واعرضه في الفورم. عدّل balanceDue()/activeCapital() و statement() عشان تبدأ الرصيد الجاري من opening_balance بدل صفر، واربط سطر 'رصيد افتتاحي' في الـ3 كشوف بالقيمة الفعلية. وأضف خطوة ترحيل تقرأ صفوف opening_balance من القديم.

### 🟠 عالي — مفيش تتبّع لمبالغ محتجز الضمان (Retention) وتحريرها
- **النوع:** accounting
- **الدليل:** في القديم revenues عندها source enum يشمل 'retention_release' و extract_type يشمل 'retention'، و retention_percent على مستخلصات/عقود. محتجز الضمان (عادة 5-10%) بند جوهري في أي مقاولات: بيتخصم من كل مستخلص ويتحرّر بعد فترة الضمان، ولازم يظهر كذمّة/مستحق منفصل.
- **الحالة الحالية:** CONFIRMED missing after thorough search. retention_percent exists only as a nominal % on the contract (migration 2026_06_04_000043_add_fields_to_project_contracts.php:19, ProjectContract.php) plus a global retention_rate default in SettingController.php:25 — never applied to compute/accumulate a held amount. contractor_extracts (migrations 2026_06_03_013001 + 2026_06_04_000003) has only a generic 
- **التوصية:** أضف احتساب retention_amount لكل مستخلص (للعميل = أصل مدين مؤجّل، للمقاول = التزام محتجز)، وحالة release_date/released. أضف تقرير محتجزات قائمة، وبند 'محتجزات الضمان' في الميزانية، وآلية تحرير تولّد revenue/payment عند انتهاء فترة warranty_months.

### 🟠 عالي — مفيش سجل شيكات بدورة حياة (قيد/إيداع/تحصيل/ارتداد)
- **النوع:** accounting
- **الدليل:** check_number منتشر في القديم على bank_transactions، contractor_transactions، partner_transactions، supplier_payments، revenues. شركة مقاولات بتتعامل بشيكات آجلة بكثرة، ومحتاجة تعرف الشيكات تحت التحصيل/المستحقة الدفع وتواريخ استحقاقها وحالتها.
- **الحالة الحالية:** CONFIRMED MISSING. Adversarial search across app/, database/migrations/, resources/views/, routes/web.php failed to refute the claim. check_number exists ONLY as a free-text string(50) nullable column on revenues, supplier_transactions, revenue_collections, bank_transactions, partner_transactions. There is NO dedicated cheques table/model/controller/migration (verified: no Cheque model in app/Mode
- **التوصية:** أضف جدول cheques (نوع وارد/صادر، رقم، بنك، due_date، status: pending/deposited/cleared/bounced/cancelled، الجهة، المبلغ، المرجع المرتبط) مع تقرير 'شيكات تحت التحصيل' و'شيكات مستحقة الدفع' بتاريخ الاستحقاق، وربط تحصيل الشيك تلقائياً بحركة بنكية.

### 🟠 عالي — مفيش إقفال فترة/سنة مالية ولا ميزانية بتاريخ محدّد (as-of)
- **النوع:** accounting
- **الدليل:** أي نظام مالي جدّي محتاج يقفل الفترة عشان يمنع التعديل الرجعي بعد التقفيل وعشان الميزانية تتقارن بين سنوات. والميزانية لازم تتعمل 'كما في تاريخ' معيّن مش لحظة العرض بس.
- **الحالة الحالية:** CONFIRMED MISSING after adversarial search. No fiscal-period / year-end / period-locking infrastructure exists anywhere in the rebuild: no fiscal_periods migration, and a broad grep for fiscal|period_clos|closing_balance|year_end|lock|close|freeze|posted|finalize|cutoff|carry_forward across app/, database/migrations/, resources/, config/ returned only (a) DB row-level lockForUpdate (concurrency co
- **التوصية:** أضف بارامتر as-of-date للميزانية يقيّد كل المجاميع لحد التاريخ ده. أضف جدول fiscal_periods بحالة open/closed وقفل التعديلات على الحركات اللي تاريخها داخل فترة مقفولة، مع رصيد افتتاحي مرحّل لحقوق الملكية بين السنوات.

### 🟠 عالي — مفيش مراكز تكلفة مستقلة (Cost Centers) عبر النظام
- **النوع:** accounting
- **الدليل:** في المقاولات بيُحتاج تجميع المصروفات/الإيرادات على أبعاد غير المشروع (إدارة، فرع، نوع نشاط) لتحليل الربحية. القديم عنده costs_by_work_item و costs_by_contractor كأبعاد تجميع للتكاليف.
- **الحالة الحالية:** VERIFIED MISSING. Genuine adversarial search found no standalone Cost Centers dimension.

Grep evidence (all zero): grep -rni "cost_center|costcenter|cost center" across *.php/*.blade.php = 0 hits. No "dimension", "division", "segment", "activity_type", or analytical "cost_dimension" anywhere. No CostCenter model (app/Models has none) and no CostCenterController.

Dimensions that DO exist on finan
- **التوصية:** أضف جدول cost_centers بسيط وربط اختياري (cost_center_id) على expenses و revenues و project_costs، مع فلتر/تجميع في التقارير. لو الـwork_item كافي للمستخدم الحالي ممكن تكون أولوية أقل.

### 🟡 متوسط — مفيش شهادات خصم وإضافة قابلة للطباعة (نموذج 41 — WHT)
- **النوع:** report
- **الدليل:** supplier_payments في الجديد فيها كل أعمدة الاستقطاعات (vat، insurance_5_percent، social_insurance، commercial_profit_supply/works، engineering/applied_professions، total_deductions) — اتعملت في الموجات السابقة. الاستقطاعات دي قانوناً لازم يطلع بيها للمورّد/المقاول شهادة خصم وإضافة (نموذج 41 خصم وتحصيل) عشان يخصمها من ضريبته.
- **الحالة الحالية:** CONFIRMED MISSING after adversarial search. The deduction data exists but there is no printable/exportable withholding-and-collection certificate (نموذج 41).

What exists (tried to refute):
- SupplierPayment model (/Users/mohamed/Downloads/qarwana/app/Models/SupplierPayment.php) DOES have DEDUCTION_FIELDS: vat, insurance_5_percent, social_insurance, commercial_profit_supply, commercial_profit_work
- **التوصية:** أضف view + route لطباعة 'شهادة خصم وإضافة' لكل مدفوعة مورّد/مقاول تجمّع الاستقطاعات حسب النوع مع بيانات الجهة الضريبية (الرقم الضريبي، الفترة، الإجمالي)، وتقرير مجمّع ربع سنوي بالخصومات لكل مورّد للإقرار الضريبي.

### ⚪ بسيط — مفيش حدّ ائتماني (credit_limit) ولا شروط سداد (payment_terms) للموردين
- **النوع:** field
- **الدليل:** في esystem.sql جدول suppliers فيه `credit_limit` و `payment_terms`، وفيه أكواد PHP بتستخدمهم (install_suppliers_system.php، supplier_details.php، suppliers_external.php). الحدّ الائتماني وشروط السداد بنود إدارة مخاطر أساسية مع الموردين.
- **الحالة الحالية:** Confirmed missing after adversarial search. grep for "credit_limit" and "payment_terms" across app/, database/, routes/, resources/ returned ZERO hits. The suppliers migration (/Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_005002_create_suppliers_table.php) defines only: name, company_name, type, phone, phone2, email, address, tax_number, commercial_register, notes, is_active, cr
- **التوصية:** أضف credit_limit و payment_terms على suppliers (وممكن contractors)، واعرض تحذير/منع عند تجاوز الحدّ الائتماني وقت أمر الشراء، واستخدم payment_terms لحساب due_date تلقائياً وتقرير أعمار الديون (aging).

### ⚪ بسيط — تعدّد العملات لكل حركة (multi-currency) — للدراسة فقط
- **النوع:** accounting
- **الدليل:** currency موجود في القديم بس كقيمة افتراضية EGP على bank_accounts و system_settings فقط (مفيش سعر صرف ولا حركات بعملات مختلفة فعلاً في الداتا).
- **الحالة الحالية:** CONFIRMED MISSING. Adversarial grep across app/, database/, resources/, routes/, config/ confirms: (1) The string 'currency' appears ONLY on bank_accounts — migration 2026_06_03_005743_create_bank_accounts_table.php line 21: $table->string('currency',10)->default('EGP'); plus BankAccount model fillable, BankAccountController (new BankAccount default 'EGP' + validation max:10), and DemoSeeder (sets
- **التوصية:** غالباً overkill لشركة محلية بتشتغل EGP. متعملش multi-currency كامل دلوقتي؛ لو فيه تعاملات استيراد بالدولار اكتفِ بحقل currency + exchange_rate على أوامر الشراء فقط.

### ⚪ بسيط — الدفتر العام مزدوج القيد (Chart of Accounts + GL/Journal) — للدراسة لا إلزامي
- **النوع:** accounting
- **الدليل:** النظام كله مبني على أرصدة مُشتقّة لكل كيان (مورّد/مقاول/بنك) بدل قيد يومية موحّد. ده شغّال كويس لـSME لكن بيمنع كشوف محاسبية معيارية (ميزان مراجعة، دفتر أستاذ موحّد) ويخلّي الميزانية بتطلع 'فرق تسوية' (settlementDifference موجود فعلاً في الكود لأن النموذج مش متوازن بطبيعته).
- **الحالة الحالية:** CONFIRMED MISSING after exhaustive adversarial search. No double-entry GL exists in the rebuild. (1) No chart_of_accounts/accounts/journal_entries/general_ledger/ledger_entries migration among all 61 migrations (all are entity-specific: suppliers, contractors, bank_transactions, etc.). (2) No Account/ChartOfAccount/JournalEntry/GeneralLedger model — only BankAccount.php (a physical bank account). 
- **التوصية:** للمستخدم الحالي (SME مقاولات) دفتر القيد المزدوج الكامل غالباً overkill ومخاطرة إعادة بناء كبيرة. الأولوية الفعلية هي سدّ فجوات الأرصدة الافتتاحية والمحتجزات والإقفال أولاً، وسيبان قرار الـGL كمرحلة مستقبلية لو احتاجوا ميزان مراجعة معياري أو مراجع خارجي طلبه.

---

## UX / operational polish gaps

> الواجهة عندها أساس كويس ومتسق في حاجات كتير: كل صفحات الـindex فيها pagination و empty-states، وكل فورمات الحذف فيها confirm()، وفيه flash بسيط للنجاح/الخطأ، وتنسيق التواريخ متسق (Y-m-d في 98 موضع)، وأغلب صفحات الطباعة فيها @media print، وأزرار الرجوع موجودة في كل صفحات show. لكن فيه فجوات UX حقيقية وعالية القيمة لشركة مقاولات كبيرة: (1) القائمة الجانبية مش متاحة خالص على الموبايل/التابلت — متخفية تحت 768px من غير أي زر hamburger، يعني التنقّل بيتعطّل تماماً على الهاتف. (2) الـpagination بيتعرض بستايل Tailwind الافتراضي بتاع Laravel 13 جوه نظام مبني كله على Bootstrap 5 RTL من غير useBootstrapFive()، فبيطلع مكسور/بدون تنسيق في كل صفحات القوائم. (3) مفيش تحقّق inline لأي حقل (@error غير مستخدم في أي من الـ30 فورم)، فالأخطاء بتظهر كقائمة عامة فوق بس من غير تمييز الحقل اللي غلط. (4) صفحات الطباعة كلها بتطبع الـsidebar والtopbar لأنهم مش معلّمين no-print في اللاي-آوت. (5) مفيش صفحات 404/403/500 مخصّصة (بتطلع صفحات Laravel الإنجليزية البيضا). (6) صفحات مالية مهمة (دفعات المقاولين/الموردين، الحسابات/التحويلات البنكية، إيداعات الشركاء، المستخدمين) من غير أي بحث/فلترة. (7) مفيش breadcrumbs ولا loading states ولا toast/auto-dismiss ولا أعمدة قابلة للترتيب في أي مكان، والـfavicon موجود بس مش مربوط في الـhead.

### 🟠 عالي — القائمة الجانبية مش متاحة خالص على الموبايل/التابلت (مفيش زر hamburger)
- **النوع:** ui
- **الدليل:** شركة مقاولات لازم مديري المواقع والمهندسين في الموقع يدخلوا من الموبايل/التابلت. المعيار في أي نظام إداري إن فيه زر hamburger يفتح القائمة على الشاشات الصغيرة (offcanvas/toggle).
- **الحالة الحالية:** CONFIRMED MISSING. Adversarial search across the full rebuild found no equivalent. /Users/mohamed/Downloads/qarwana/resources/views/layouts/app.blade.php is the only layout and the only place .sidebar/<aside> is defined. Line 36 matches the claim exactly: @media (max-width: 768px) { .sidebar { inset-inline-start: -260px; } .main { margin-inline-start: 0; } } — sidebar is pushed fully off-screen be
- **التوصية:** ضيف زر hamburger في الـtopbar يظهر تحت 768px، وحوّل الـsidebar لـoffcanvas بتاع Bootstrap 5 (موجود أصلاً في الـbundle) أو أضف class بيتفعّل بالـJS مع backdrop، عشان التنقّل يشتغل على الموبايل.

### 🟡 متوسط — مفيش تحقّق inline على مستوى الحقل في أي فورم (الأخطاء قائمة عامة فوق بس)
- **النوع:** ui
- **الدليل:** المعيار في إدخال البيانات المؤسسي إن كل حقل غلط يتعلّم بـis-invalid وتظهر رسالته تحته (@error) مع إعادة تعبئة old(). الفورمات هنا طويلة (purchase_orders, invoices, projects, contractor_extracts) فالمستخدم محتاج يعرف الحقل اللي غلط بالظبط.
- **الحالة الحالية:** CONFIRMED missing. I searched adversarially and could not refute it. Findings at /Users/mohamed/Downloads/qarwana:

- @error directive: 0 occurrences across all blade files (grep -rl '@error' resources/views = 0). Repo-wide sweep (resources, app, public; excluding vendor/node_modules) also yields 0.
- is-invalid: 0 occurrences. invalid-feedback: 0 occurrences.
- errors->has() / field-scoped errors
- **التوصية:** ضيف @error('field') class is-invalid + <div class=invalid-feedback> تحت كل input في فورمات (خصوصاً الطويلة)، واتأكد إن القيم بترجع بـold() لكل حقل بعد فشل التحقق.

### 🟡 متوسط — صفحات الطباعة كلها بتطبع الـsidebar والtopbar (مفيش no-print في اللاي-آوت)
- **النوع:** ui
- **الدليل:** أي مستند بيتطبع (كشف حساب، فاتورة، تقرير) لازم يطلع نضيف من غير عناصر التنقّل. شركة المقاولات بتطبع كشوف حساب موردين/مقاولين وفواتير للعملاء.
- **الحالة الحالية:** CONFIRMED (gap is real), with minor inaccuracies in the claim's "all pages" wording.

Layout: /Users/mohamed/Downloads/qarwana/resources/views/layouts/app.blade.php — its <style> block (lines 12-37) has NO @media print and the .sidebar (width:260px, lines 18-22) and .topbar (line 34) elements carry no `no-print` class. grep for `no-print`/`@media print` in the layout = 0. So there is no centralize
- **التوصية:** ضيف في اللاي-آوت @media print{ .sidebar,.topbar{display:none!important} .main{margin:0!important} }، وأضف @media print لإخفاء الأزرار في materials/report وproject_costs/report وbank_accounts/show.

### 🟡 متوسط — صفحات مالية حسّاسة من غير أي بحث/فلترة (دفعات المقاولين والموردين بالذات)
- **النوع:** ui
- **الدليل:** دفعات المقاولين/الموردين بتكبر لآلاف الصفوف. المحاسب لازم يفلتر بمقاول/مورد معيّن أو نطاق تاريخ. باقي الصفحات المالية المتشابهة (الإيرادات مثلاً) عندها فلاتر فعلاً، فدي صفحات أقل من نظرائها.
- **الحالة الحالية:** CONFIRMED MISSING after adversarial search. ContractorPaymentController::index (lines 32-43, /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ContractorPaymentController.php) does ContractorPayment::query()->with(...)->latest('payment_date')->paginate(15) with zero Request filtering — no where on contractor_id, no date range. SupplierPaymentController::index (lines 31-42, .../SupplierPaymentC
- **التوصية:** ضيف فلترة GET في دفعات المقاولين/الموردين (مقاول/مورد + نطاق تاريخ + طريقة دفع)، وفلتر دور/حالة لصفحة المستخدمين، وبحث/نطاق تاريخ للتحويلات البنكية والحسابات.

### ⚪ بسيط — الـPagination بستايل Tailwind الافتراضي جوه نظام Bootstrap RTL (بيطلع مكسور)
- **النوع:** ui
- **الدليل:** Laravel 11+/13 الافتراضي بتاعه للـpaginator هو قالب Tailwind. النظام كله مبني على Bootstrap 5.3 RTL، فلازم يتنادى Paginator::useBootstrapFive() في الـboot أو تتنشر vendor/pagination بستايل Bootstrap.
- **الحالة الحالية:** CONFIRMED MISSING. Tried hard to refute; every claim holds. (1) composer.json pins laravel/framework ^13.8 whose default paginator view is pagination::tailwind. (2) AppServiceProvider::boot() (the only registered provider in bootstrap/providers.php) has NO Paginator::useBootstrapFive()/useBootstrap()/defaultView — full grep of app/, bootstrap/, config/, routes/ for useBootstrap|Paginator::|default
- **التوصية:** ضيف Paginator::useBootstrapFive(); في AppServiceProvider::boot() عشان الترقيم يتنسّق صح مع Bootstrap 5 RTL في كل الصفحات دفعة واحدة.

### ⚪ بسيط — مفيش صفحات خطأ مخصّصة (404/403/419/500) — بتطلع صفحات Laravel الإنجليزية البيضا
- **النوع:** ui
- **الدليل:** نظام عربي RTL مؤسسي لازم يعرض صفحات خطأ متناسقة مع الهوية وباللغة العربية لما الصفحة مش موجودة أو الصلاحية مرفوضة أو الـsession منتهية (419).
- **الحالة الحالية:** CONFIRMED MISSING after adversarial search. No custom error pages exist.

Evidence:
1. /Users/mohamed/Downloads/qarwana/resources/views/errors/ does NOT exist (ls fails). A full directory listing of resources/views shows 38 module dirs but no "errors" dir.
2. find for any "errors" directory or 404/403/419/500/minimal .blade.php files across the project (excluding vendor) returns nothing.
3. This i
- **التوصية:** أنشئ resources/views/errors/{404,403,419,500}.blade.php عربية RTL متناسقة مع الـbranding وفيها زر رجوع للوحة التحكم.

---

## Legacy parity REMAINDER

> بعد مراجعة كل جداول esystem.sql وملفات الـ .php القديمة مقابل الـ rebuild الحالي (44 controller / 47 جدول)، لسه فيه فجوات parity حقيقية مش موجودة في قائمة الـ waves 1-10. أهمها وحدة كاملة "الموردين الداخليين" (internal_supplier_extracts + مدفوعاتها بخصومات المهن الهندسية/الفنية/التطبيقية) الموجودة في القديم بملفين كبيرين (suppliers_internal.php و internal_supplier_details.php 84KB) ومفقودة تماماً (مفيش model/migration/route). كمان استيراد تكاليف المشاريع من Excel مع cost_import_logs مش موجود (الـ rebuild بيعمل export بس)، والـ VIEWs التجميعية (costs_by_work_item / costs_by_contractor / project_costs_summary) كتقارير مش متبنية، وجدول expense_alerts مع منطق min_limit/alert_enabled، و material_purchases كسجل مشتريات مستقل، وحالة تأكيد الإيراد is_confirmed، وحساب إهلاك الأصول (طريقة القسط الثابت/المتناقص + salvage_value + القيمة الدفترية + التخريد disposal)، و custom_category للمواد، وأنواع معاملات المقاول (سلفة/خصم/استرداد)، وتقرير العملاء + اتجاه الإيراد/المصروف الشهري. باقي العناصر اللي في الـ done-list متحققة فعلاً (custody balance بتتحسب dynamically، avatar بيتخدم صح، login_attempts بنفس أعمدة القديم).

### 🟡 متوسط — الحساب البنكي الافتراضي للراتب على الموظف (employees.bank_account_id)
- **النوع:** field
- **الدليل:** جدول employees في القديم فيه bank_account_id ('الحساب البنكي الافتراضي للراتب') — عشان صرف الراتب يروح للحساب الافتراضي للموظف.
- **الحالة الحالية:** Confirmed MISSING after adversarial search. The employees create migration (database/migrations/2026_06_03_005003_create_employees_table.php) has only: employee_code, name, national_id, job_title, department, salary, phone, email, hire_date, is_active, notes, created_by — no bank_account_id. No migration alters employees (grep for Schema::table('employees' returns nothing). The Employee model (app
- **التوصية:** إضافة bank_account_id (nullable) على الموظف كحساب راتب افتراضي يتعبّى تلقائي في معاملة الراتب.

### ⚪ بسيط — تقارير التكاليف التجميعية (costs_by_work_item / costs_by_contractor / project_costs_summary)
- **النوع:** report
- **الدليل:** القديم معرّف 3 VIEWs: costs_by_work_item (تجميع amount حسب بند العمل لكل مشروع), costs_by_contractor (تجميع حسب المقاول/المورد), project_costs_summary (إجمالي البنود/التكلفة/أول وآخر تاريخ لكل مشروع) — تقارير جاهزة للتكاليف.
- **الحالة الحالية:** PARTIALLY REFUTED — core aggregations exist, only the summary rollup is missing. The current rebuild DOES have a full project_costs module (migration 2026_06_04_000040_create_project_costs_table.php, model app/Models/ProjectCost.php, ProjectCostController, views project_costs/*).

costs_by_work_item AND costs_by_contractor equivalents EXIST: ProjectCostController::report() (/Users/mohamed/Download
- **التوصية:** إضافة تقارير aggregate: تكاليف حسب بند العمل، تكاليف حسب المقاول/المورد، وملخّص تكاليف لكل المشاريع (count + sum + min/max cost_date) كصفحات تقارير.

### ⚪ بسيط — تنبيهات المصروفات (expense_alerts + min_limit/alert_enabled)
- **النوع:** logic
- **الدليل:** القديم فيه جدول expense_alerts (alert_type: due_date/min_limit/custom, alert_date, message, is_read) وأعمدة على expenses: min_limit (الحد الأدنى للتنبيه) و alert_enabled — منطق تنبيهات استحقاق/حد أدنى للمصروفات.
- **الحالة الحالية:** CONFIRMED missing after adversarial search. Grep for expense_alert|alert_type|alert_date|min_limit|alert_enabled across all .php matched ONLY GAPS_REPORT.md, never code. No expense_alerts migration/table/model exists (no Alert model; only default Notifiable trait on User.php). Verified all 6 expense migrations: expenses HAS due_date (added in 2026_06_04_000013_add_installment_columns_to_expenses.p
- **التوصية:** إضافة عمودي min_limit و alert_enabled على expenses + جدول expense_alerts + توليد تنبيهات عند اقتراب due_date أو نزول الرصيد تحت الحد الأدنى، وعرضها كـ notifications غير مقروءة.

### ⚪ بسيط — تأكيد الإيراد (revenue is_confirmed)
- **النوع:** field
- **الدليل:** جدول revenues في القديم فيه is_confirmed، و revenues.php بيعرض badge 'مؤكد/قيد الانتظار' (أسطر 546, 705, 1008, 1176-1178) وإحصائية confirmed_count — حالة تأكيد الإيراد بعد التحصيل الفعلي.
- **الحالة الحالية:** CONFIRMED MISSING after a genuine adversarial search. grep for is_confirmed / confirmed_count returns ZERO non-vendor PHP hits (only an unrelated 'confirmed' password rule in ProfileController.php:46). The revenues table is built by /Users/mohamed/Downloads/qarwana/database/migrations/2026_06_03_010334_create_revenues_table.php plus add_collection_columns (...000010) which add paid_amount, payment
- **التوصية:** إضافة عمود is_confirmed + زر/route لتأكيد الإيراد وعرض حالة 'مؤكد/قيد الانتظار' وإحصائية عدد المؤكد، زي ما القديم بيعمل.

---

## Reports & Analytics still missing

> منظومة التقارير في الـ rebuild ناضجة جزئياً: فيه ميزانية عمومية، قائمة دخل، تقرير ضرائب (VAT مخرجات/مدخلات)، كشوف حساب (مقاول/مورّد/موظف/شريك/بنك)، وتقارير مقاولين/تكلفة-مشروع/مخزون، بالإضافة لاتجاه 6 شهور (إيراد×مصروف) على الـ dashboard. لكن تظل فجوات جوهرية لشركة مقاولات كبيرة: لا يوجد قائمة تدفق نقدي (Cash Flow)، ولا تقرير أعمار ديون مدينة/دائنة (AR/AP Aging) رغم وجود remaining()/balanceDue()، وتقرير ربحية المشاريع الحالي مجرد (إيراد−مصروف) ولا يستغل projects.contract_value الموجود فعلاً للمقارنة (متعاقد×تكلفة فعلية×محصّل)، ولا يوجد كشف مرتبات/Payroll رغم وجود employees.salary وemployee_transactions.salary_month، ولا تقرير مقارنة فترات/اتجاهات كصفحة تقرير، ولا موازنة مقابل فعلي (Budget vs Actual)، ولا توقّع أرباح الشركاء رغم وجود partner_profit_schedules. الأهم على مستوى البنية: لا توجد مكتبة PDF حقيقية إطلاقاً في composer.json (لا dompdf/snappy/browsershot) فالكشوف طباعة-متصفح فقط، ولا يوجد أي تصدير Excel/xlsx في كل المشروع — التصدير CSV فقط بينما الـ legacy reports.php كان فيه "تصدير Excel" صراحةً.

### 🟡 متوسط — غياب قائمة التدفق النقدي (Cash Flow Statement)
- **النوع:** report
- **الدليل:** أي شركة مقاولات تحتاج قائمة تدفق نقدي (تشغيلي/استثماري/تمويلي) لإدارة السيولة المرتبطة بالمستخلصات والدفعات المقدمة. البيانات متاحة بالكامل: bank_transactions (مع category/value_date)، revenue_collections، supplier_payments، expense_payments، partner_deposits.
- **الحالة الحالية:** CONFIRMED MISSING after adversarial search. ReportController.php (/Users/mohamed/Downloads/qarwana/app/Http/Controllers/ReportController.php) exposes only index/balanceSheet/incomeStatement/taxReport — no cash-flow method. routes/web.php has only reports.index, reports.balance_sheet, reports.income_statement, reports.taxes — no cash-flow route. resources/views/reports/ contains only balance_sheet/
- **التوصية:** إضافة ReportController::cashFlow + route reports.cash_flow + view: تجميع التدفقات الداخلة (تحصيلات إيرادات/فواتير + إيداعات شركاء) مقابل الخارجة (مدفوعات موردين/مقاولين/مصروفات/مرتبات) بفترة قابلة للفلترة، مع رصيد نقدي أول/آخر الفترة من bank_accounts.

### 🟡 متوسط — غياب تقرير أعمار الديون المدينة والدائنة (AR/AP Aging)
- **النوع:** report
- **الدليل:** تقرير أعمار الديون (0-30 / 31-60 / 61-90 / +90 يوم) أساسي لمتابعة تحصيل العملاء وسداد الموردين/المقاولين في المقاولات. المتطلبات الحسابية متاحة: Invoice::remaining()، Revenue::remaining()، Supplier::balanceDue()، Contractor::balanceDue() موجودة فعلاً.
- **الحالة الحالية:** CONFIRMED missing after exhaustive adversarial search. No AR/AP aging report (0-30/31-60/61-90/+90) exists anywhere in the rebuild.

Searches (all zero/non-aging results):
- grep "aging|aged|overdue": ONLY hits are 'overdue' used as an Invoice STATUS string (per-invoice flag + badge coloring in invoices index/show/print views and DashboardController count). Exactly matches the claim. No aggregated
- **التوصية:** إضافة تقريرين: arAging (ذمم مدينة: فواتير/إيرادات غير محصّلة مبوّبة حسب أيام التأخر عن due_date/issue_date) وapAging (ذمم دائنة: أرصدة موردين/مقاولين حسب تاريخ الاستحقاق)، مع مجاميع لكل شريحة عمرية وإجمالي.

### 🟡 متوسط — تقرير ربحية المشاريع لا يقارن المتعاقد×التكلفة الفعلية×المحصّل
- **النوع:** report
- **الدليل:** شركة المقاولات تحتاج مقارنة قيمة العقد (contract_value) مقابل التكلفة الفعلية (مستخلصات+موردين+project_costs+مرتبات مشروع) مقابل المحصّل فعلاً، لقياس الربحية والـ over/under-run. الحقول موجودة: projects.contract_value و project_contracts.contract_value و جداول project_costs/contractor_extracts.
- **الحالة الحالية:** CONFIRMED missing (with one nuance). Verified the cited block: ReportController::index (/Users/mohamed/Downloads/qarwana/app/Http/Controllers/ReportController.php:55-65) computes per-project only withSum(revenues as rev) and withSum(expenses as exp); reports/index.blade.php:84-100 "ربحية المشاليع" shows only المشروع/إيرادات/مصروفات/الصافي and never touches contract_value. No collection dimension, 
- **التوصية:** صفحة تقرير ربحية مشاريع مخصّصة بأعمدة: قيمة العقد، إجمالي المستخلصات/التكاليف الفعلية، المحصّل، نسبة الإنجاز، الربح المتوقع مقابل المحقق، وانحراف التكلفة (cost variance) لكل مشروع مع إجمالي.

### 🟡 متوسط — غياب كشف/تقرير المرتبات (Payroll Report)
- **النوع:** report
- **الدليل:** تقرير رواتب دوري (شهري) لكل الموظفين أساسي للمحاسبة وكشوف البنك. البيانات متاحة: employees.salary موجود في migration الموظفين، و employee_transactions فيه salary_month (الـ legacy employee_statement.php كان يعرض راتب/salary_month صراحةً).
- **الحالة الحالية:** CONFIRMED MISSING. Genuine adversarial search across controllers, models, migrations, views, and routes/web.php found no aggregated/periodic payroll report. ReportController exposes only: index (revenue/expense by project+category), balanceSheet, incomeStatement, taxReport (routes 171-174); plus standalone contractors/project_costs/materials reports. None aggregate salaries. EmployeeController::st
- **التوصية:** تقرير مرتبات شهري: صف لكل موظف يعرض الراتب الأساسي + البدلات + الخصومات/السلف (من employee_transactions حسب salary_month) + الصافي المستحق، مع إجمالي عام قابل للتصدير، وربطه بكشف تحويل بنكي.

### 🟡 متوسط — غياب تقرير مقارنة الفترات والاتجاهات (Period Comparison / Trends)
- **النوع:** report
- **الدليل:** الإدارة تحتاج مقارنة شهر/ربع/سنة مقابل السابق (إيراد/مصروف/ربح) واتجاهات زمنية كتقرير مستقل قابل للطباعة، لا مجرد رسمة على الـ dashboard.
- **الحالة الحالية:** CONFIRMED MISSING after adversarial search. No period-comparison/trends report exists in the rebuild.

Evidence:
- /Users/mohamed/Downloads/qarwana/routes/web.php (lines 171-174) registers exactly 4 ReportController routes: reports.index, reports.balance_sheet, reports.income_statement, reports.taxes. No comparison/trends route.
- /Users/mohamed/Downloads/qarwana/app/Http/Controllers/ReportControl
- **التوصية:** تقرير مقارنة فترتين (current vs previous) لكل من الإيراد/المصروف/الصافي/COGS/الهامش مع نسبة التغير %، وجدول اتجاه شهري متعدد الأعمدة قابل للتصدير.

### 🟡 متوسط — غياب موازنة تقديرية مقابل فعلي (Budget vs Actual)
- **النوع:** report
- **الدليل:** ضبط تكاليف المقاولات يتطلب مقارنة المصروف الفعلي بكل فئة/مشروع مقابل الموازنة المعتمدة لرصد الانحرافات. expense_categories الديناميكية موجودة لكن بلا حقل موازنة.
- **الحالة الحالية:** CONFIRMED MISSING after adversarial search. (1) expense_categories migration (database/migrations/2026_06_04_000081_create_expense_categories_table.php) has only name/code/is_active/timestamps; ExpenseCategory.php model confirms no budget field. (2) projects table (2026_06_03_004037) has contract_value but NO budget/estimated/planned column; grep for budget|estimated|planned|allocated across datab
- **التوصية:** إضافة حقول موازنة (على expense_categories و/أو projects) + تقرير Budget vs Actual بأعمدة: الموازنة، الفعلي، الانحراف، نسبة الاستهلاك %، مع تنبيه تجاوز.

### 🟡 متوسط — لا يوجد إخراج PDF حقيقي للكشوف والتقارير (طباعة متصفح فقط)
- **النوع:** report
- **الدليل:** الشركات تحتاج PDF رسمي قابل للأرشفة/الإرسال لكشوف الحساب والميزانية وقائمة الدخل، لا اعتماد على طباعة المتصفح (تختلف بالهيدر/الفوتر/الهوامش حسب الجهاز).
- **الحالة الحالية:** CONFIRMED MISSING after adversarial search. No real PDF export exists anywhere in /Users/mohamed/Downloads/qarwana.

Evidence:
1. composer.json requires ONLY laravel/framework ^13.8, laravel/tinker, spatie/laravel-permission — no PDF lib. composer.lock contains no dompdf/snappy/browsershot/mpdf/tcpdf/wkhtmltopdf package; the single "Barryvdh" grep hit is merely a co-author of fruitcake/php-cors (a
- **التوصية:** إضافة barryvdh/laravel-dompdf وتوليد PDF للكشوف والتقارير المالية (downloadPdf) بقالب موحّد فيه شعار الشركة والترويسة، مع زر 'تحميل PDF' بجانب الطباعة.

### 🟡 متوسط — لا يوجد تصدير Excel/xlsx في كل النظام (CSV فقط)
- **النوع:** report
- **الدليل:** الـ legacy reports.php كان فيه زر 'تصدير Excel' (export=excel) صراحةً، والمحاسبون يفضّلون xlsx بتنسيق وأعمدة وصيغ مقابل CSV الخام. النظام القديم حتى كان معتمداً على phpspreadsheet.
- **الحالة الحالية:** CONFIRMED missing. Adversarial search found no Excel/xlsx export anywhere in the rebuild. composer.json require block has only php, laravel/framework, laravel/tinker, spatie/laravel-permission — no phpspreadsheet/maatwebsite/box-spout/openspout. composer.lock and vendor/ have zero spreadsheet packages. config/ has no Excel facade alias. No app/Exports dir or Export classes. No spreadsheet MIME typ
- **التوصية:** إضافة maatwebsite/excel وتوفير تصدير xlsx للتقارير الرئيسية وكشوف الحساب (مع رؤوس أعمدة منسّقة ومجاميع)، مع الإبقاء على CSV كخيار.

### 🟡 متوسط — غياب تقرير أداء الموردين والمقاولين (Performance / KPIs)
- **النوع:** report
- **الدليل:** تقييم الموردين/المقاولين (حجم التعامل، نسبة الالتزام بالسداد، متوسط مدة التوريد، عدد المستخلصات المعتمدة مقابل المرفوضة) يساعد الإدارة في قرارات الترسية. البيانات متاحة: supplier_transactions/supplier_payments و contractor_extracts بحالاتها.
- **الحالة الحالية:** CONFIRMED MISSING after a genuine adversarial search across controllers, models, routes/web.php, and views. No supplier/contractor performance (KPI/rating/classification) report exists.

Evidence:
- ContractorController::report (app/Http/Controllers/ContractorController.php:152-185) computes ONLY totalEarned (sum of approved/partial/paid extracts net_amount), totalPaid (sum of payments), and balan
- **التوصية:** تقرير أداء يصنّف الموردين/المقاولين حسب إجمالي التعامل، عدد العمليات/المستخلصات، متوسط قيمة المستخلص، ونسبة المسدّد، مع ترتيب تنازلي لأهم المتعاملين.

### ⚪ بسيط — غياب توقّع/جدول أرباح الشركاء المستقبلي (Partner Profit Forecast)
- **النوع:** report
- **الدليل:** Wave سابقة أضافت partner_profit_schedules + جدولة صرف أرباح، لكن تقرير التوقّع المستقبلي (الأرباح المجدولة القادمة لكل شريك + ما صُرف مقابل المتبقي) غير موجود كتقرير تحليلي.
- **الحالة الحالية:** CONFIRMED missing after exhaustive search. The underlying infrastructure exists: migration database/migrations/2026_06_04_000031_create_partner_profit_schedules_table.php (due_date, amount, is_paid, paid_date, partner_transaction_id); model app/Models/PartnerProfitSchedule.php; PartnerDepositController generates schedules and disburses via payProfit(); per-deposit schedule table in resources/views
- **التوصية:** تقرير توقّع أرباح الشركاء: جدول بالأرباح المجدولة القادمة (من partner_profit_schedules) لكل شريك مع تاريخ الاستحقاق والمبلغ والمصروف/المتبقي وإجمالي التزام مستقبلي.

---
