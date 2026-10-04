# Deploying Academy FMS (Hostinger shared hosting)

This guide covers a production install on Hostinger shared hosting (Premium/Business): `.htaccess` front-controller + HTTPS, the scheduler cron, and the nightly automated database backup.

---

## 1. Server requirements

| Requirement | Notes |
|---|---|
| PHP 8.3+ | hPanel → Websites → PHP Configuration |
| Extensions | `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `curl`, `dom`, `gd` (required by dompdf) |
| MySQL 8 / MariaDB | create a database + user in hPanel → Databases → MySQL |
| No shell required | all steps below work with File Manager + hPanel Cron Jobs |

---

## 2. Build the upload locally

Run this on your machine **before** uploading so the server never needs Composer, Node or dev dependencies:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan test          # must be green before shipping
```

Then zip the project **excluding** `.git`, `node_modules`, `tests`, `scripts`, `.phpunit.result.cache`.

> Keep `vendor/` **in** the zip — shared hosting usually has no Composer. Keep `.env` **out** (you will create it on the server).

---

## 3. File layout

Never put the whole project inside the web root. Only `public/` should be reachable:

```
/home/USER/academy/           ← project root (NOT web-accessible)
├── .env
├── artisan
├── bootstrap/
├── config/
├── database/
├── storage/                   ← private: logs, framework cache, backups
├── vendor/
└── public/                    ← contents copied to public_html

/home/USER/public_html/        ← document root (only this is served)
├── index.php                  ← edited below
├── .htaccess                  ← from public/.htaccess
├── build/                     ← Vite assets
└── everything else from public/
```

Upload `academy/` next to `public_html`, then copy the contents of `public/` into `public_html`.

> If hPanel lets you set the document root for your domain (or you use a subdomain → *Document Root* field), point it at `/home/USER/academy/public` and **skip the index.php edits entirely**.

---

## 4. Edit `public_html/index.php`

Only the three paths change (`../` becomes `../academy/`):

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../academy/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../academy/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../academy/bootstrap/app.php';

$app->handleRequest(Request::capture());
```

`storage_path()` and `base_path()` are derived from `bootstrap/app.php`, so logs, cache and `.env` keep resolving to `/home/USER/academy/` — nothing else needs changing.

---

## 5. `.htaccess`

`public/.htaccess` (copied to `public_html/.htaccess`) already handles the front controller, `OPTIONS`/Authorization headers and `-Indexes`. Append these blocks to it:

```apache
# ---- Force HTTPS (remove this block if it causes a redirect loop) ----
<IfModule mod_rewrite.c>
    RewriteCond %{HTTPS} !=on
    RewriteCond %{HTTP:X-Forwarded-Proto} !https
    RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
</IfModule>

# ---- Security headers ----
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
</IfModule>

# ---- Never serve hidden/dot files ----
<FilesMatch "^\.">
    Require all denied
</FilesMatch>
```

Laravel already generates `https://` links when `APP_ENV=production` (`AppServiceProvider` calls `URL::forceScheme('https')`); the rewrite rule above makes sure plain `http://` requests are upgraded too. If you ever hit an infinite loop, delete the HTTPS block — Hostinger's free SSL is applied at the server level anyway.

---

## 6. `.env` on the server

Create `/home/USER/academy/.env` (File Manager → *Show hidden files*, or `cp .env.example .env` over SSH):

```dotenv
APP_NAME="Academy FMS"
APP_ENV=production
APP_KEY=                       # filled by key:generate in step 7
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_strong_password

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true

CACHE_STORE=database
QUEUE_CONNECTION=database
LOG_CHANNEL=daily
LOG_LEVEL=warning

MAIL_MAILER=smtp
```

**Hardening:** `APP_ENV=production` + `APP_DEBUG=false` must both be set — otherwise every stack trace leaks env values, SQL and the `APP_KEY` to visitors. `SESSION_SECURE_COOKIE=true` stops the session cookie being sent over plain HTTP.

---

## 7. First run

Preferred: hPanel → *Advanced → SSH Access* (Business/Cloud) or *Terminal*.

```bash
cd /home/USER/academy
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --class=UserSeeder --force    # admin login only
# optional demo data: php artisan db:seed --class=DemoDataSeeder --force
php artisan storage:link             # only if you expose public files
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan about                    # sanity check
```

No SSH? Run `migrate --force` from your machine by temporarily pointing `.env` at the Hostinger database (`DB_HOST=localhost` → your site's DB host from hPanel), then delete the local `.env` copy. Do **not** ship a `.env` inside `public_html`.

> Seeded logins: `admin@academy.pk` / `password` and `partner@academy.pk` / `password`.
> **Change both passwords immediately** after the first login (Profile) — never ship the default.

---

## 8. Permissions

```bash
chmod -R 775 storage bootstrap/cache
chmod 644 .env
```

Anything Laravel writes (sessions, logs, compiled views, backups) lives under `storage/`, which sits **outside** the document root.

---

## 9. Cron — run the scheduler every minute

The nightly database dump is registered in `routes/console.php`:

```php
Schedule::command('app:backup --keep=14')->dailyAt('01:30')->withoutOverlapping();
```

It only fires if the scheduler is ticked from cron.

**hPanel → Advanced → Cron Jobs → Add New:**

| Field | Value |
|---|---|
| Common frequency | *Every minute* (or schedule `* * * * *`) |
| Command | `cd /home/USER/academy && php artisan schedule:run >> /dev/null 2>&1` |

Notes:

- If `php` is not on cron's PATH, use the absolute binary (`which php` over SSH, often `/usr/bin/php83` or `/usr/local/bin/php`).
- `>> /dev/null 2>&1` keeps cron quiet; Laravel's own log still records failures.
- `withoutOverlapping()` uses the `database` cache, so `CACHE_STORE=database` and a migrated cache table are required.

**Backup-only cron (alternative)** if you prefer not to run the full scheduler:

```
30 1 * * * cd /home/USER/academy && php artisan app:backup --keep=14 >> /dev/null 2>&1
```

---

## 10. Daily backups: where they land, how to restore

- Command: `php artisan app:backup --keep=14`
- Output: `storage/app/private/backups/academy-YYYY-MM-DD-HHMMSS.sql`
- Retention: newest 14 dumps are kept, older ones are pruned automatically.
- Dumps are plain SQL built over PDO — **no `mysqldump` binary needed**, which shared hosting rarely provides.
- Manual run: `php artisan app:backup` (optionally `--path=` / `--keep=`).

**Do not rely on the server alone.** Download `backups/` through File Manager (or `scp`) to an off-site location weekly.

**Restore:**

```bash
cd /home/USER/academy
php artisan down
mysql -u your_user -p your_db < storage/app/private/backups/academy-2026-10-01-013000.sql
php artisan up
```

Or import the `.sql` through hPanel → phpMyAdmin → Import.

---

## 11. Post-deploy smoke test

```
https://yourdomain.com/       → redirects to /login
https://yourdomain.com/login   → 200, styles load (public/build uploaded)
https://yourdomain.com/up      → 200
```

Then sign in, open Dashboard / Students / Payments / Reports, download one receipt PDF, and confirm `storage/logs/laravel.log` stays clean.

Remember: after any change to `routes/*.php` or `config/*.php` on the server, re-run `php artisan route:cache` / `config:cache` (they are frozen while cached). Deploying a new build? Re-run step 2 and re-upload `public/build`.

---

## 12. Security checklist

- [ ] `APP_ENV=production` and `APP_DEBUG=false` in the server `.env`
- [ ] `APP_URL=https://yourdomain.com` (Laravel forces `https://` on every generated URL)
- [ ] `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`
- [ ] Free SSL active in hPanel; HTTP→HTTPS redirect in `.htaccess`
- [ ] `.env`, `composer.json`, `tests/`, `scripts/` and `storage/` are **outside** `public_html`
- [ ] `.env` file mode `644` or tighter; never committed (`.gitignore` already covers it)
- [ ] `storage/` and `bootstrap/cache/` are `775`, not world-writable
- [ ] Only `public/` contents were uploaded; `vendor/` was installed with `--no-dev`
- [ ] Admin accounts use strong passwords; registration route disabled — portal users are created by an admin in Settings
- [ ] Database user has only `SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX` on that one database
- [ ] Nightly backup cron is live and a dump was downloaded off-server this week
- [ ] `php artisan about` shows `Environment: production` and `Debug: false`
