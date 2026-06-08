---
name: qarwana-gap-implementation
description: Qarwana rebuild — the 98-gap audit findings and the 10-wave plan to implement all of them
metadata: 
  node_type: memory
  type: project
  originSessionId: 37e7a12c-987a-4b8b-9dbe-81bbfec77eef
---

Qarwana (القروانة) is a Laravel 13 rebuild of a legacy PHP contractor-management system at `/Users/mohamed/Downloads/qarwana/` (legacy at `/Users/mohamed/Downloads/system (2)/`).

On 2026-06-03 a multi-agent workflow audit compared legacy vs rebuild and confirmed **98 real gaps** (31 high / 45 medium / 22 low). Full report: `/Users/mohamed/Downloads/qarwana/GAPS_REPORT.md`. Root cause of "feels empty": modules were built header-only without detail/line-items.

User decision: **implement ALL 98** ("نفّذ كل الـ98"), then a 2nd comprehensive audit ("عايزه شامل") found **44 more gaps** → implemented in waves 11-14. **STATUS (2026-06-04): ALL 14 WAVES DONE & COMMITTED** (latest `9d3d482`). Final verify on fresh setup: **59/59 pages 200, 0 fail**, 311 routes, 74 migrations, 46 models, 59 controllers; integrity passes (bank stored==derived, invoice/extract paid==Σpayments, soft-delete restore OK). Round-2 report: `GAPS_REPORT_ROUND2.md`. Waves 11-14 added: responsive nav + alerts center + dashboard due-lists + error pages + financial filters; reports suite (cash-flow, AR/AP aging, period comparison) + AnalyticsController (profitability/budget/performance/payroll/forecast) + real PDF (mpdf Arabic) & Excel (phpspreadsheet) via ExportService; accounting depth (opening balances, retention, cheque register, cost centers, WHT certificate نموذج 41, as-of balance sheet); enterprise admin (roles/permissions UI, soft-deletes+trash, field-level audit, global search, polymorphic attachments, user mgmt+last_login, 2FA TOTP, spatie backup, scheduled reminders+DB notifications, bulk actions). Packages added: barryvdh/laravel-dompdf, phpoffice/phpspreadsheet, mpdf/mpdf, spatie/laravel-backup, pragmarx/google2fa-qrcode. **Deliberately SKIPPED (audit flagged overkill for SME):** full double-entry GL/chart-of-accounts, full per-transaction multi-currency. ORIGINAL 98 status below. Built via parallel workflow agents scaffolding module files + returning route/nav snippets; orchestrator wired shared files (routes/web.php, nav, seeder) centrally; each wave numerically verified + committed. Plan = 10 dependency-ordered waves, test + commit after each:
1. Line items & detail: purchase_order_items, contractor_extract_items, supplier_transactions + missing financial columns + auto numbers
2. Payments/collections/status automation: revenue_collections, invoice payments, expense installments, extract payment→status
3. Custody & detailed deductions: expense/payment↔employee custody auto-deduct (reference_type/id), detailed deduction columns (vat/insurance/social/commercial profit/professions)
4. Partners profit system: partner_deposits, partner_profit_schedule, profit payout, settlement, bank link, balance validation, statement
5. Project costs/BOQ: project_costs, work_item, costs_by_contractor, project_employees, project_materials, cost import/export
6. Materials/inventory: transfer/adjustment types, unit_price/value, stock_before/after, material_purchases, PO receive→inventory, low-stock report
7. Financial reports: Balance Sheet, Income Statement, entity statements (running balance, printable), project costs report, tax aggregation, Excel/PDF export
8. Banking: custom_payment_methods, bank txn fields (category/beneficiary/check/value_date/attachment/links/is_reconciled), statement filters/export, reconciliation, transfer fee as separate txn
9. Auth/profile/login audit: login_attempts logging, login_logs admin page, ProfileController, avatar upload, activity log records login + filters
10. Settings/import-export/misc: Excel import/export (6 entities)+import_logs, dynamic expense_categories, expense alerts/fields, financial+currency+timezone settings, supplier balance fields/statement, contractor statement+fields+approval, invoice print

**UPDATE (2026-06-06): expansion beyond legacy parity — contracting-specific + benchmarked vs Procore/Buildertrend.** After the 14 waves, the user kept asking to "complete" the system. Added (all committed, each numerically verified, all pages 200):
- **UI redesign**: calmer professional palette, fewer colors — neutral warm base + single brown accent + 3 muted semantic colors. Done via `:root` vars + global Bootstrap overrides (`.text-bg-*`/`.text-*`) in `resources/views/layouts/app.blade.php` so 164 badges/334 icon-colors/124 inline brown buttons recolor WITHOUT per-view edits. KPI cards → white + thin semantic top-strip. Chart.js → brown-led muted ramp.
- **Contracting modules**: tenders, quotations(+items, →invoice), letters_of_guarantee, insurance_policies, project_milestones (+visual Gantt in Project 360), daily_site_reports (+site photos via existing polymorphic Attachment — whitelist DailySiteReport), labor_attendances, equipment_logs, material_requisitions(+items, issue→inventory out), change_orders (→revised contract value), snags/punch-list, rfis, submittals, inspection_requests, meetings, **payroll** (payroll_runs+items; pay posts bank withdrawals via [[BankLedgerService]] + salary/advance_return EmployeeTransactions — verified bank stored==derived, withdrawals==net).
- **WIP report** (`reports.work_in_progress`): per-project revised contract, %complete, earned value, billed/collected, cost, over/under-billing, profit + Excel.
- AlertService extended with expiry/overdue/pending alerts for all above.

**Already-existing (do NOT rebuild)**: petty cash / custody / advances live in `EmployeeTransaction` (types custody/custody_return/custody_expense/advance/advance_return) + `Employee::custodyBalance()`/`advanceBalance()`.

**CRITICAL house conventions for future modules**: (1) NEVER use `@php ... @endphp` BLOCK form in Blade — it fails to compile here; use inline `@php($x = ...)` only, avoid closures inside it. (2) Build via parallel workflow agents that ONLY create their own files + return route/nav/alert/relation/demo snippets; orchestrator wires shared files centrally (routes/web.php, layout nav, Project model, AlertService, projects/show.blade.php, DemoSeeder). (3) Reuse existing permission groups where possible. (4) IDE P1009/P1010/P1013 diagnostics are false-positives; `php -l` is authoritative. (5) Migrations dated `2026_06_0X_0000NN`; verify with fresh `migrate:fresh --seed` + `db:seed --class=DemoSeeder`. (6) Admin login: admin@alqarwana.com / <من إعداد ADMIN_PASSWORD>.

**Why:** user wants enterprise-grade parity with legacy + more. **How to apply:** money/ledger/status logic is correctness-critical (bcmath, derived balances, DB::transaction+lockForUpdate) — build carefully, verify numerically; reuse [[BankLedgerService]] pattern. Related: see existing waves committed in git history.
