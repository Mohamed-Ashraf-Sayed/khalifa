---
name: qarwana-production-deploy
description: "How the Qarwana ERP is deployed to production (Hostinger) — server paths, PHP, DB, redeploy steps"
metadata: 
  node_type: memory
  type: project
  originSessionId: 37e7a12c-987a-4b8b-9dbe-81bbfec77eef
---

The Qarwana ERP ([[qarwana-gap-implementation]]) is deployed LIVE at **https://khk.etqanly.com** (Hostinger shared hosting, CloudLinux+LiteSpeed).

**Server access:** `ssh -p 65002 u300082146@147.93.54.183` (password auth; use `sshpass`). SSH/web run as user u300082146. ⚠️ The SSH password and admin password were shared in chat on 2026-06-08 — user was advised to rotate both.

**Layout (shared-hosting Laravel split):**
- App root: `~/domains/khk.etqanly.com/laravel/` (full Laravel app).
- Web docroot: `~/domains/khk.etqanly.com/public_html/` = a COPY of Laravel `public/` with `index.php` paths edited to `__DIR__."/../laravel/..."`; `public_html/storage` is a symlink → `../laravel/storage/app/public`.
- DB: **SQLite** at `~/domains/khk.etqanly.com/laravel/database/database.sqlite` (not MySQL). `.env`: APP_ENV=production, APP_DEBUG=false, SESSION_DRIVER=file, CACHE_STORE=file, QUEUE_CONNECTION=sync, APP_URL=https://khk.etqanly.com.
- Seeded with DatabaseSeeder only (roles + admin + chart of accounts) — **NO DemoSeeder** (clean production data).

**PHP: the app needs 8.4** (codebase/console uses 8.4-parseable syntax). Server web PHP set to 8.4 in hPanel. CLI default is 8.2 — ALWAYS run artisan/composer with `/opt/alt/php84/usr/bin/php`. Composer is `composer.phar` inside the laravel dir (system has no usable /usr/bin/composer); run `php84 composer.phar ...`. NOTE: composer.json had `config.platform.php=8.4.1` (from local 8.5); on server it was lowered via `composer config platform.php 8.3.0` + a manual edit to `vendor/composer/platform_check.php` (80401→80300) so it also tolerates 8.3.

**Redeploy / update steps:**
1. `rsync -az --delete -e "ssh -p 65002" --exclude .git --exclude node_modules --exclude vendor --exclude .env --exclude 'database/*.sqlite' --exclude 'storage/logs/*' --exclude bootstrap/cache ./ u300082146@147.93.54.183:domains/khk.etqanly.com/laravel/`
2. On server in laravel/: `php84 composer.phar install --no-dev -o` (if deps changed); `php84 artisan migrate --force`; `php84 artisan optimize:clear && config:cache && route:cache && view:cache`.
3. If `public/` assets changed, re-copy into `public_html/` and re-fix `index.php` paths (or it stays since index.php is custom).
4. Quoting tip: to run PHP on the server avoiding ssh quote-hell, base64-encode a script locally and `echo <b64> | base64 -d | /opt/alt/php84/usr/bin/php`.

**Pending/optional:** Hostinger SSL (Let's Encrypt) is active; HTTP→HTTPS forced. Could migrate SQLite→MySQL later (create DB+user in hPanel, update .env, `migrate --seed`). Other Hostinger domains on same account: al-qayrawana.com (WordPress), phi-rose.com, etkan/etqanly.
