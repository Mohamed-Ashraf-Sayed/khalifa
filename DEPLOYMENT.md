# دليل النشر — نظام القروانة

دليل تشغيل النظام على بيئة إنتاج (Hostinger / VPS / أي استضافة PHP 8.4+).

## 1) المتطلبات
- PHP **8.4+** + الإضافات: `bcmath, pdo_mysql, mbstring, gd, zip, openssl, fileinfo`
- MySQL/MariaDB · Composer 2 · صلاحية كتابة على `storage/` و`bootstrap/cache/`

## 2) الإعداد الأساسي
```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
# اضبط في .env: APP_ENV=production, APP_DEBUG=false, APP_URL=https://yourdomain
#   DB_CONNECTION=mysql + DB_HOST/DB_DATABASE/DB_USERNAME/DB_PASSWORD
php artisan migrate --force          # الأدوار + الصلاحيات + حساب المدير
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
- اضبط **جذر الدومين (Document Root) على مجلد `public/`** فقط.
- **غيّر كلمة سرّ المدير** فوراً بعد أول دخول (`admin@alqarwana.com`).

## 3) البريد الإلكتروني (لإرسال الفواتير + التذكيرات)
في `.env` اضبط SMTP الحقيقي بدل `log`:
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourprovider.com
MAIL_PORT=587
MAIL_USERNAME=...   MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@yourdomain"   MAIL_FROM_NAME="${APP_NAME}"
```
زر **«إرسال بالبريد»** في الفاتورة يرسلها PDF مرفق للعميل. زر **«واتساب»** يفتح محادثة بالرسالة جاهزة (بدون أي إعداد).

## 4) الطابور (Queue)
`QUEUE_CONNECTION=database` — شغّل عامل الطابور كخدمة دائمة:
```bash
php artisan queue:work --tries=3 --timeout=90
```
على Hostinger استخدم **Supervisor** أو cron يعيد تشغيل `queue:work` (أو `queue:listen`).

## 5) المهام المجدولة (تذكيرات + تعليم الفواتير المتأخرة)
أضف هذا الـ cron مرة واحدة (يشغّل كل المهام المجدولة):
```
* * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1
```
المهام المسجّلة يومياً: `app:mark-overdue` (تعليم الفواتير المتأخرة) و`app:send-reminders` (إشعارات للمدير/المدراء بالمستحقات والمخزون المنخفض).

## 6) النسخ الاحتياطي
- يدوياً من الواجهة: **النسخ الاحتياطي → إنشاء نسخة الآن** (يستخدم `spatie/laravel-backup`).
- جدولة تلقائية (اختياري): أضف `Schedule::command('backup:run --only-db')->daily();` في `routes/console.php`.
- النسخ تُخزّن على قرص `local` تحت `storage/app/private/{APP_NAME}/`.

## 7) الأمان
- فعّل **المصادقة الثنائية (2FA)** للحسابات الحسّاسة من «الملف الشخصي».
- HTTPS إجباري · `APP_DEBUG=false` · صفحات الأخطاء العربية المخصّصة مفعّلة.
- الصلاحيات تُدار من «الأدوار والصلاحيات» (admin يتجاوز الكل).
- الملفات والمرفقات تُخزّن خارج `public/` وتُنزّل عبر مسارات محميّة.

## 8) التحديثات اللاحقة
```bash
git pull && composer install --no-dev -o
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
```

## ملاحظات
- كل المبالغ بـ bcmath والأرصدة مشتقّة من المصدر (راجع README).
- لا تُشغّل `migrate:fresh` على الإنتاج (يمسح البيانات) — استخدم `migrate` فقط.
