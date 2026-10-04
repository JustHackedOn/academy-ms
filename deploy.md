# Deploying Academy FMS to Hostinger — Git-sync guide

Production deployment guide for **Hostinger Premium (shared hosting, no SSH)** using **Git auto-deployment** (push → Hostinger pulls). The repository is **self-contained**: `vendor/` and `public/build/` are committed, so no Composer/Node ever runs on the server.

> **Why self-contained?** Hostinger's Git integration *only syncs files* — it never runs a build step. Whatever is committed to the branch is what gets served. So compiled assets and PHP dependencies must live in the repo.

---

## 1. Architecture

```
Laptop ──git push──▶ GitHub (private) ──webhook──▶ Hostinger GitHub App
                                                        │ git pull
                                                        ▼
                                            /home/USER/public_html/          ◀── document root
                                            ├── .htaccess       (root router → public/)
                                            ├── .env            (server-only, NOT in git)
                                            ├── app/ bootstrap/ config/ database/
                                            ├── routes/ storage/
                                            ├── vendor/         (committed)
                                            ├── artisan
                                            └── public/         ◀── front controller + assets
                                                ├── index.php
                                                ├── .htaccess
                                                └── build/      (committed Vite assets)

Branches:
  main        = clean source of truth (vendor/ + build/ ignored) — day-to-day work
  production  = main + vendor/ + public/build/  → connected to Hostinger
```

**How requests are served:** the **root `.htaccess`** rewrites every request into `public/`. This hides `app/`, `vendor/`, `config/`, `.env`, `storage/` etc. automatically (they are never under `public/`), while `/build/*` assets are served from `public/build`.

---

## 2. Server requirements

| Requirement | Set it in |
|---|---|
| **PHP 8.3+** | hPanel → *Advanced → PHP Configuration* |
| **Extensions**: `pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, fileinfo, curl, dom, gd` | hPanel → *PHP Configuration → PHP extensions* |
| **MySQL 8 / MariaDB** — create DB + user, note the host (`localhost`) | hPanel → *Databases → MySQL Databases* |
| **No shell required** | Everything below uses Git + File Manager + hPanel Cron Jobs |

---

## 3. One-time local repo preparation

Run on your machine. This creates the `production` branch that ships the runtime artifacts.

```bash
# 3.1  First commit on a clean source branch
git branch -M main
git add -A
git commit -m "initial commit"

# 3.2  Build runtime artifacts (they will be committed, NOT ignored, on this branch)
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# 3.3  Create the deployment branch
git checkout -b production
```

Now edit **`.gitignore` on the `production` branch only**:

```diff
- /node_modules
- /public/build
+ /node_modules
+ # /public/build        ← keep assets in the deploy branch (do NOT ignore)
  ...
- /vendor
+ # /vendor              ← keep vendor in the deploy branch (do NOT ignore)

# Optional: keep dev-only files off the server
+ /tests
+ /scripts
```

Stage the runtime artifacts and commit:

```bash
git add .gitignore
git add -f vendor public/build
git commit -m "production build: vendor + compiled assets"
```

> ⚠️ Every time PHP/JS dependencies change, re-run `composer install --no-dev --optimize-autoloader` and `npm run build` on the `production` branch, then `git add -f vendor public/build` again.

---

## 4. Push to a private GitHub repository

```bash
# Create an empty PRIVATE repo on github.com first, then:
git remote add origin git@github.com:YOUR_USER/academy-fms.git   # or https://...
git push -u origin main
git push -u origin production
```

---

## 5. Connect Hostinger to GitHub (auto-deploy)

1. hPanel → your website → **Advanced → Git**.
2. Click **Connect with GitHub** → install the **Hostinger GitHub App** on your account and grant access to the repo.
3. Select the repo, then configure the deployment:

   | Setting | Value |
   |---|---|
   | **Branch** | `production` |
   | **Deploy directory** | `public_html` |

4. **Before the first deploy, empty `public_html`** (delete the default `index.html`/placeholder) — the initial deploy requires an empty target.
5. Click **Deploy** and watch the log.

Auto-deployment is **on by default**: every future push to `production` triggers a pull automatically. Manual: **Redeploy**. History: **Deployments** tab.

> ⚠️ Hostinger replaces files present in the repo on each deploy. **Untracked** files (`.env`, `storage/app/public/*` uploads, logs) are *not* in the repo — verify once after your first real push that they survive (Section 9).

---

## 6. Root `.htaccess` (`public_html/.htaccess`)

This file is **not** in the repo (Hostinger's `public_html` root differs from the repo layout), so create it **once** in File Manager. It routes everything into `public/`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Send every request into the app's public/ directory (assets + front controller)
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>

# ---- Force HTTPS (delete this block if it causes a redirect loop) ----
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

# ---- Deny dot files (.env, .git, etc.) ----
<FilesMatch "^\.">
    Require all denied
</FilesMatch>
```

`public/.htaccess` (shipped in the repo) handles the Laravel front controller and trailing-slash redirects. Activate Hostinger's **free SSL** first (hPanel → *Security → SSL*), or the HTTPS rule can loop.

---

## 7. Server `.env` (never committed)

Create `public_html/.env` in File Manager (*Show hidden files*). The `APP_KEY` must be generated **locally** and pasted, since there is no SSH:

```bash
# Locally:
php artisan key:generate --show
```

```dotenv
APP_NAME="Academy FMS"
APP_ENV=production
APP_KEY=base64:PASTE_THE_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_TIMEZONE=Asia/Karachi

APP_LOCALE=en
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=daily
LOG_LEVEL=warning

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
QUEUE_CONNECTION=sync

# Long random token that protects /_deploy/{token} (generate locally:
# php -r "echo bin2hex(random_bytes(24));")
DEPLOY_TOKEN=paste_a_long_random_token_here

MAIL_MAILER=smtp
MAIL_HOST=smtp.yourprovider.com
MAIL_PORT=587
MAIL_USERNAME=you@yourdomain.com
MAIL_PASSWORD=your_mail_password
MAIL_FROM_ADDRESS="no-reply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**Hardening notes**
- `APP_ENV=production` **and** `APP_DEBUG=false` — must both be set, or stack traces/SQL/`.env` leak.
- `APP_TIMEZONE=Asia/Karachi` drives overdue logic, monthly reports and the nightly backup time.
- `SESSION_SECURE_COOKIE=true` keeps the session cookie off plain HTTP.
- `DEPLOY_TOKEN` empty ⇒ the `/_deploy` endpoint is disabled.

---

## 8. First run (no SSH)

After the first deploy and once `.env` exists, hit the protected webhook (Section 9) to migrate and seed:

```
https://yourdomain.com/_deploy/<DEPLOY_TOKEN>?seed=1
```

This runs `migrate --force`, seeds the two portal users, and refreshes caches. Also create the public storage link once:

- hPanel → **Advanced → Cron Jobs** (one-off) **or** File Manager: create a symlink `public_html/public/storage` → `public_html/storage/app/public`.
- On SSH-less plans the easiest reliable way is a **one-time cron**: 
  `cd /home/USER/public_html && php artisan storage:link` (then delete the cron).

> ⚠️ The seeded accounts are `admin@academy.pk` / `password` and `partner@academy.pk` / `password`. **Change both passwords immediately** at Profile → Update Password. To ship custom credentials, edit `database/seeders/UserSeeder.php` before deploying the `production` branch.

---

## 9. Post-deploy webhook — `/_deploy/{token}`

Hostinger's Git deploy never runs Artisan, so the app exposes a token-protected endpoint (`DeployController`) that does it over HTTP:

| URL | Action |
|---|---|
| `/_deploy/<token>` | `migrate --force` + `optimize` (config/route/view cache) |
| `/_deploy/<token>?seed=1` | the above **plus** `db:seed --class=UserSeeder` |
| `/_deploy/<token>?clear=1` | `optimize:clear` (clears all caches) |

Safety:
- Only active when `APP_ENV=production` **and** `DEPLOY_TOKEN` is non-empty; otherwise returns **404**.
- Token compared with `hash_equals()` (timing-safe) and the route is rate-limited (6/min).
- Returns JSON with the migrate/optimize output.

**Run it after every deployment that includes migrations** (or whenever config/routes change).

> If you ever change `.env`, run `?clear=1` first, then the plain URL, so the cached config is rebuilt.

---

## 10. Day-to-day release workflow

```bash
# 1. Develop on main
git checkout main
git merge feature-x
git push origin main

# 2. Rebuild assets if CSS/JS changed, then merge into production
npm run build
git checkout production
git merge main
git add -f public/build          # (and `git add -f vendor` after composer changes)
git commit -m "release: <summary>"
git push origin production       # → Hostinger auto-deploys

# 3. Apply migrations + refresh caches
#    open:  https://yourdomain.com/_deploy/<DEPLOY_TOKEN>
```

---

## 11. Rollback

```bash
# Option A — revert a bad commit and re-deploy
git checkout production
git revert <bad-commit>          # or: git reset --hard <good-commit>
git push origin production       # Hostinger pulls the reverted state
# then: https://yourdomain.com/_deploy/<DEPLOY_TOKEN>?clear=1
```

Or, in hPanel → **Advanced → Git → Deployments**, pick the previous successful deployment and **Redeploy**. Database changes are not rolled back automatically — restore from a backup if needed (Section 12).

---

## 12. Cron + nightly backups

The nightly dump is registered in `routes/console.php`:

```php
Schedule::command('app:backup --keep=14')->dailyAt('01:30')->withoutOverlapping()->runInBackground();
```

**hPanel → Advanced → Cron Jobs → Add New:**

| Field | Value |
|---|---|
| Frequency | *Every minute* (`* * * * *`) |
| Command | `cd /home/USER/public_html && php artisan schedule:run >> /dev/null 2>&1` |

Backups land in `storage/app/private/backups/academy-YYYY-MM-DD-HHMMSS.sql`; the newest 14 are kept. **Download them off-server weekly** via File Manager.

**Restore:** import the `.sql` through hPanel → *Databases → phpMyAdmin → Import*, or over SSH `mysql -u user -p db < backup.sql`.
`01:30` is interpreted in `APP_TIMEZONE` (Asia/Karachi).

---

## 13. Security checklist

- [ ] `APP_ENV=production` and `APP_DEBUG=false` in `public_html/.env`
- [ ] `APP_URL=https://yourdomain.com` and `APP_TIMEZONE=Asia/Karachi`
- [ ] `APP_KEY` generated locally and pasted; `.env` is **not** in Git and mode `644` or tighter
- [ ] `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`
- [ ] Free SSL active; HTTP→HTTPS redirect in the root `.htaccess`
- [ ] Root `.htaccess` routes into `public/`, so `app/`, `vendor/`, `config/`, `storage/`, `.env` are unreachable over HTTP
- [ ] `public/build/` **and** `vendor/` are committed on the `production` branch
- [ ] `DEPLOY_TOKEN` is long/random and set only on the server
- [ ] Default seeded passwords changed
- [ ] Registration disabled (`/register` → 404); Settings & Activity Log are admin-only (partner gets 403)
- [ ] `storage/` and `bootstrap/cache/` writable (775); `storage:link` created
- [ ] DB user limited to `SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX` on that database
- [ ] Scheduler cron live; a backup was downloaded off-server this week
- [ ] `php artisan about` shows `Environment: production`, `Debug: false`

**Verify after the first real push** that untracked files survive a deploy:
`.env` still present, a test-uploaded student photo still displays, `storage/logs/laravel.log` still appends. If a deploy wipes them, move uploads off the repo tree and re-create `.env` after each deploy (rare, but confirm once).

---

## 14. Troubleshooting

| Symptom | Fix |
|---|---|
| Push didn't reach the site | Pushed to `production`? Check the **Auto-deployment** chip is healthy; use **Redeploy**. |
| CSS/JS missing | `public/build` wasn't committed (`git add -f public/build`) or assets weren't rebuilt after a CSS/JS change. |
| `Class "..." not found` / 500 | `vendor/` missing → rerun `composer install --no-dev --optimize-autoloader` and `git add -f vendor`, push again. |
| Every page 404s | Root `.htaccess` missing/wrong, or first deploy wasn't into an empty `public_html`. |
| `.env`/photos gone after deploy | Deploy deleted untracked files → see Section 13 verification; re-create `.env`. |
| Route/config changes ignored | Cached — hit `/_deploy/<token>?clear=1` then `/_deploy/<token>`. |
| `/_deploy` returns 404 | `APP_ENV` isn't `production` or `DEPLOY_TOKEN` is empty/wrong. |
| 419 "Page expired" on forms | Session cookie `secure` while on HTTP, or `APP_URL`/session domain mismatch. Use HTTPS. |
| HTTPS redirect loop | Delete the Force-HTTPS block from the root `.htaccess` (Hostinger applies SSL server-side). |
| Migration errors | Check the JSON response from `/_deploy`; then `php artisan migrate:status` (local) — never run two deploys at once. |

---

## 15. Alternative: manual zip upload

If you ever need to deploy **without** Git (e.g. hotfix on a machine without the repo), follow **[DEPLOYMENT.md](DEPLOYMENT.md)** — build locally, zip without `.git`/`node_modules`/`tests`/`scripts`, upload with `vendor/` included, and edit `public_html/index.php` to point at the project folder. That guide keeps the app **outside** `public_html`, which is a different (and equally valid) layout to the one used by this Git workflow.

---

_Git workflow target: Hostinger Premium (no SSH). Companion reference: [DEPLOYMENT.md](DEPLOYMENT.md)._


