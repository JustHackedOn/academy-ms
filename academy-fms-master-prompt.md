# MASTER PROMPT: Academy Fee Management System (Laravel + Tailwind, Bento UI)

Copy everything below into your AI coding tool (Claude Code / Cursor / etc.). Work **phase by phase**; after each phase, stop, summarize what was built, and wait for my "continue".

---

## 1. ROLE
You are a senior Laravel engineer and product designer. Build a production-quality, private web app to manage students, batches, fees, and monthly collections for an online academy in Pakistan. Write clean, commented, maintainable code. Do not skip validation, authorization, or edge cases.

## 2. PROJECT SUMMARY
A private admin dashboard used by **only 2 people (owner + partner)** from anywhere. Features: batch management, student management, fee plans (full payment or installments), payment recording, monthly ledger, pending/defaulter tracking, click-to-WhatsApp, notes, and a beautiful bento-style statistics dashboard.

Currency: **PKR**. Format numbers like `Rs 12,500`. Timezone: `Asia/Karachi`. UI language: English with simple labels (Roman Urdu friendly). Phone format: Pakistan.

## 3. TECH STACK (fixed)
- Laravel 11/12 (PHP 8.2+), **Blade + Alpine.js** (no SPA framework)
- Laravel Breeze (Blade) for auth, **registration disabled**
- Tailwind CSS (via Vite), `@tailwindcss/forms`
- Chart.js (charts), Lucide icons (inline SVG)
- MySQL
- `barryvdh/laravel-dompdf` for receipts (works on shared hosting)
- `maatwebsite/excel` or plain CSV streaming for exports
- No Node.js runtime on the server. Node is for build only (`npm run build`), assets are committed/uploaded.
- **Target host: Hostinger shared hosting.** So: no queue workers (use `sync`), no Redis, no Supervisor, scheduler via cron, file/database cache and session.

## 4. ACCESS & SECURITY
- Breeze login only. Remove register routes. Seeder creates 2 users (owner, partner) with roles `admin` and `partner`.
- Throttle login, strong password rules, "remember me" ok.
- All routes behind `auth`. Policies/middleware for roles: `admin` = everything incl. delete + settings; `partner` = configurable (default: everything except hard delete and user settings).
- CSRF everywhere, validate every request via FormRequest classes, escape output, mass-assignment protection.
- Force HTTPS in production, `APP_DEBUG=false`, secure cookies.
- Soft delete students/batches/payments; never hard delete financial data.
- **Activity log** table: who did what (created/updated/deleted payment, student, batch), old/new values (JSON), IP, timestamp. Viewable page.
- Optional later: 2FA (TOTP).

## 5. DATABASE DESIGN
Use migrations, foreign keys, indexes, `decimal(12,2)` for money, soft deletes.

**users**: id, name, email, password, role (admin|partner), timestamps
**batches**: id, name, code, default_fee, duration_months (default 4), start_date, end_date (computed), status (upcoming|running|completed|archived), notes, timestamps, softDeletes
**students**: id, batch_id, name, phone (stored normalized 92XXXXXXXXXX), alt_phone nullable, email nullable, fee_type (full|installments), total_fee, discount, net_fee (total_fee - discount), join_date, status (active|dropped|completed|paused), notes text nullable, timestamps, softDeletes
**installments**: id, student_id, number, due_date, amount, status (pending|partial|paid|overdue, derived/cached), timestamps
**payments**: id, student_id, installment_id nullable, amount, paid_on, for_month (YYYY-MM), method (cash|jazzcash|easypaisa|bank|other), reference nullable, note nullable, received_by (user_id), receipt_no unique, timestamps, softDeletes
**student_notes**: id, student_id, user_id, body, promise_date nullable, type (note|call|follow_up|promise), timestamps
**expenses** (phase 8): id, title, category, amount, spent_on, note, user_id
**activity_logs**: id, user_id, action, subject_type, subject_id, old_values json, new_values json, ip, created_at
**settings**: key/value (academy name, logo, whatsapp templates, currency, due-day-of-month)

## 6. BUSINESS RULES (important, implement exactly)
1. **Fee plan on student create:**
   - `net_fee = total_fee - discount` (total_fee defaults from batch default_fee, editable per student).
   - **full**: create 1 installment = net_fee, due on join_date (editable).
   - **installments**: default count = batch.duration_months (4). Split equally; put the rounding remainder on the last installment. Due dates monthly from join_date (editable). UI lets user edit count, each amount, and each due date before saving; validate that the sum equals net_fee.
2. **Recording a payment:** user picks student, amount, date, method. System **auto-allocates** to the oldest unpaid/partial installment first, and can split one payment across multiple installments. Allow manual choose-installment override. Block overpayment beyond remaining balance (with clear error). Generate `receipt_no` (e.g. `RCPT-2026-0001`).
3. **Installment status** (computed from paid vs amount and today):
   - `paid` = fully covered; `partial` = some paid; `overdue` = due_date < today and not fully paid; `pending` = otherwise.
4. **Student balance** = net_fee − sum(payments). Show paid, remaining, % complete, next due date, months left.
5. If all installments paid, student fee status = "Cleared". Show badge.
6. Editing a student's fee after payments exist: recalculate unpaid installments only, keep paid ones; warn the user.
7. Deleting/editing a payment re-calculates installment statuses and is logged.
8. **Monthly numbers (for any selected month):**
   - **Expected** = sum of installment amounts due in that month
   - **Received** = sum of payments with `paid_on` in that month
   - **Pending** = expected − (paid against those installments), plus carried-over overdue shown separately
   - **Defaulters** = students with any overdue installment (with overdue days and amount)
   - **Collection rate** = received / expected
9. **WhatsApp:** every student phone shows a WhatsApp button → `https://wa.me/92XXXXXXXXXX?text=<urlencoded template>`. Normalize inputs: `0300-1234567`, `+92 300 1234567`, `03001234567` → `923001234567`. Reminder button on pending/overdue rows uses a template with placeholders `{name} {batch} {amount_due} {due_date} {academy}`. Templates editable in settings, with an English and a Roman Urdu default.

## 7. PAGES & FEATURES
1. **Login** (clean, branded, no register link)
2. **Dashboard** (see design section): month selector, KPIs, bento cards, charts, lists
3. **Batches**: list (cards + table toggle), create/edit, detail page (students, collected vs expected, completion, batch chart), archive
4. **Students**: table with search, filters (batch, status, fee status, fee type, overdue), sort, pagination; create/edit with fee-plan builder; **student profile** with: header (avatar initials, batch, status, WhatsApp + call buttons), fee summary bento cards, installment schedule with status, payment history, notes timeline (add note, call log, promise date), "Record payment" modal
5. **Payments**: global list with filters (date range, method, batch, student), quick add, receipt PDF download + share on WhatsApp, edit/delete (logged)
6. **Monthly Ledger**: matrix view, rows = students, columns = months of the batch (M1–M4 or calendar months), cell colored/labeled Paid / Partial / Due / Overdue with amounts; click a cell to record payment
7. **Defaulters / Dues**: list sorted by overdue days, total overdue, one-click WhatsApp reminder, "promise date" column, bulk "open reminders" helper
8. **Reports & Export**: monthly report, batch report, CSV/Excel export, printable PDF
9. **Expenses** + net profit card (phase 8)
10. **Activity log**, **Settings** (academy info, logo, WhatsApp templates, users)
11. **Global search** (Ctrl/Cmd+K): students by name/phone

## 8. DESIGN SYSTEM: PROFESSIONAL BENTO UI
**Overall look:** modern SaaS dashboard (think Linear / Vercel / Stripe), clean, airy, high contrast, calm colors.

**Bento layout rules**
- Dashboard is a CSS Grid bento: `grid-cols-12`, cards of mixed sizes (e.g. 4x2, 8x2, 3x1, 6x2), consistent `gap-4/gap-5`.
- Cards: `rounded-2xl` to `rounded-3xl`, 1px subtle border, very soft shadow, generous padding (`p-5/p-6`), subtle hover lift (`hover:-translate-y-0.5`, transition).
- Mix card types: big hero KPI card, small stat tiles, chart cards, list cards, progress/ring cards. Some cards use a soft gradient background or tinted accent for emphasis.
- Responsive: 12-col on desktop, 6-col tablet, single column on mobile; no horizontal page scroll; tables become cards on mobile.

**Dashboard bento content**
- Hero card: **This Month: Expected vs Received** with large number, progress bar and % collected
- Tiles: Pending amount, Overdue amount, Defaulters count, Active students, Running batches, New joins this month
- **12-month revenue trend** (area/line chart, expected vs received)
- **Collection rate** donut/radial
- **Batch-wise collection** (horizontal stacked bars: paid vs remaining)
- **Payment methods** donut (Cash/JazzCash/Easypaisa/Bank)
- **Upcoming dues (next 7 days)** list with WhatsApp buttons
- **Top overdue students** list with days overdue
- **Recent payments** feed
- Sparklines in KPI tiles, animated count-up numbers, skeleton loaders

**Visual tokens**
- Font: Inter or Plus Jakarta Sans (self-hosted/Google Fonts), tabular numbers for money
- Primary: indigo/violet; success: emerald; warning: amber; danger: rose; neutral: slate. Light and **dark mode** (class-based, toggle, persisted)
- Status badges (pill): Paid (emerald), Partial (amber), Pending (slate/blue), Overdue (rose), Cleared (emerald solid)
- Collapsible sidebar with icons (Dashboard, Batches, Students, Payments, Ledger, Dues, Reports, Expenses, Activity, Settings); mobile = bottom nav or drawer
- Top bar: global search, month picker, dark toggle, user menu
- Components as reusable Blade components: `<x-card>`, `<x-stat-tile>`, `<x-badge>`, `<x-modal>`, `<x-empty-state>`, `<x-table>`, `<x-progress>`, `<x-avatar>`, toast notifications
- Nice empty states, loading states, confirm dialogs (Alpine), form validation messages inline
- Accessibility: focus rings, contrast, labels, keyboard navigation

## 9. CODE STRUCTURE & QUALITY
- Controllers thin; business logic in **Service classes** (`FeePlanService`, `PaymentAllocationService`, `DashboardStatsService`, `PhoneFormatter`, `WhatsAppLinkBuilder`).
- Eloquent relationships, query scopes (`overdue()`, `dueInMonth()`), accessors for balances
- Eager loading to avoid N+1; cache dashboard stats briefly (file cache)
- FormRequests for validation; Policies for authorization
- Seeders with realistic demo data (3 batches, ~30 students, mixed full/installment, some overdue)
- Feature tests for: installment generation, payment allocation, overpayment block, monthly stats, phone normalization
- README with setup, env vars, and Hostinger deployment steps

## 10. BUILD PHASES (stop after each)
1. Laravel + Breeze + Tailwind setup, registration disabled, user seeder, base layout (sidebar, topbar, dark mode), design components
2. Migrations, models, relationships, factories/seeders
3. Batches CRUD + batch detail
4. Students CRUD + fee-plan builder + installments generation + WhatsApp button + notes timeline + student profile
5. Payments (allocation, receipts PDF), payment history
6. Monthly Ledger matrix + Dues/Defaulters + reminder templates
7. Bento Dashboard with all stats and charts
8. Reports/exports, expenses, activity log, settings, global search
9. Tests, security hardening, Hostinger deployment guide

## 11. HOSTINGER DEPLOYMENT REQUIREMENTS
- Provide steps for **shared hosting**: upload project outside `public_html`, point domain/subdomain document root to Laravel's `public` (or provide `.htaccess` + `index.php` path fix if root can't be changed)
- PHP 8.2+, enable required extensions (`mbstring, pdo_mysql, gd, zip, fileinfo`)
- `.env` production values, `php artisan key:generate`, `migrate --force`, `db:seed --class=UserSeeder`, `storage:link`, `config:cache`, `route:cache`, `view:cache`
- Cron for scheduler: `* * * * * php /home/USER/path/artisan schedule:run`
- Scheduled daily DB backup command (mysqldump to storage, keep last 14)
- SSL + force HTTPS, `APP_DEBUG=false`, `APP_ENV=production`
- Build assets locally with `npm run build`, upload `public/build`

## 12. ACCEPTANCE CHECKLIST
- [ ] Only the 2 seeded users can log in; no public registration
- [ ] Add batch (4 months) and student with full OR installment plan; installments editable and sum validated
- [ ] Recording a payment updates balance, installment status, ledger, and dashboard instantly
- [ ] Clicking a student's phone/WhatsApp opens the correct `wa.me` chat with prefilled text
- [ ] Dashboard shows, for any month: expected, received, pending, defaulters, charts
- [ ] Ledger shows month-by-month status per student
- [ ] Receipt PDF downloads correctly
- [ ] Works on mobile; dark mode works
- [ ] Every payment/student change appears in the activity log
- [ ] Deploys and runs on Hostinger shared hosting

## 13. START NOW
Begin with **Phase 1 only**. Output exact commands, then the files you created/changed. Do not start Phase 2 until I say "continue".
