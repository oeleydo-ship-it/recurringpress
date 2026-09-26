# Bug Analysis & Fixes

Analysis of the Laravel invoice/quotation SaaS app in this repo, performed 2026-07-24. This
document lists every issue found, which ones were fixed in this pass, and what's left for
follow-up.

## Fixed

### 1. Critical — Unauthenticated destructive scripts in `public/` (deleted)
- `public/clear-all.php` and `public/clear-db.php`: read DB credentials from `.env` and
  **drop every table in the production database** on a plain unauthenticated GET request.
- `public/verify-users.php`: unauthenticated endpoint that force-verifies the email of
  **every** `User` and `Superadmin` row — a trivial account-verification bypass.
- **Fix:** all three files deleted. They were leftover install/emergency scripts that should
  never have shipped to a public web root. If you need this functionality again, run it via
  `php artisan tinker` or a one-off Artisan command, not a public URL.

### 2. Critical — Stripe public payment success didn't verify the PaymentIntent belonged to the invoice
- `app/Http/Controllers/PublicInvoiceController.php` (`stripeSuccess`): only checked
  `paymentIntent->status === 'succeeded'`. It never confirmed the PaymentIntent's
  `metadata.invoice_id` / `metadata.company_id` matched the invoice referenced by the
  share-token in the URL. Anyone with a valid share link could submit **any** successful
  PaymentIntent ID (e.g. from paying a different, cheaper invoice) and have this invoice
  marked paid.
- **Fix:** added a check that rejects the request unless the retrieved PaymentIntent's
  metadata `invoice_id`/`company_id` match the invoice being viewed.

### 3. High — `paid_amount` never actually updated for any payment path
- The `Invoice` model's real, fillable, migrated column is **`paid_amount`**
  (`database/migrations/2026_01_17_184936_add_paid_amount_to_invoices_table.php`).
- `PublicInvoiceController` read/wrote a non-existent `amount_paid` attribute in three
  places (`show`, `selectPayment`, `createStripeIntent`) and in the invoice-update call in
  `stripeSuccess`. Since `amount_paid` isn't a real or fillable column, all of this silently
  did nothing — `paid_amount` was never incremented for invoices paid via the public
  share-link/Stripe flow, and "amount due" shown to payers was always the full invoice total
  even after a partial payment.
- `PaymentController::handleWebhook()` and `recordManualPayment()` called
  `$invoice->markAsPaid()`, which only sets `status`/`paid_at` and also never touches
  `paid_amount`.
- **Fix:** all payment-recording paths (public Stripe checkout, Stripe webhook, manual
  payment entry) now call `Invoice::addPayment($amount)`, the one method that correctly
  increments `paid_amount` and derives `status` (`paid`/`partial`). Renamed the stray
  `amount_paid` reads to `paid_amount` in `PublicInvoiceController`.

### 4. High — Duplicate payment numbers under concurrent requests
- `Company::getNextInvoiceNumber()` and `Company::getNextQuotationNumber()` computed the
  next sequence number by reading the last row and incrementing it, with no locking —
  unlike `getNextPaymentNumber()`, which already used `DB::transaction()` +
  `lockForUpdate()`. Two concurrent requests could read the same "last number" and generate
  colliding invoice/quotation numbers. (`fix_duplicate_payments.sql` in the repo root is
  evidence this exact race already caused a production duplicate-`PAY-000001` incident for
  payments before that method was hardened — invoices/quotations had the same unfixed bug.)
- **Fix:** wrapped both methods in `DB::transaction()` with `lockForUpdate()` on the company
  row and the candidate-number existence check, mirroring the already-correct
  `getNextPaymentNumber()` implementation.

### 5. Medium — No idempotency guard on manual payment entry
- `PaymentController::recordManualPayment()` had no duplicate-submission guard, unlike the
  Stripe paths (which check `transaction_id` / existing pending payment). A double form
  submit created two `Payment` + `Receipt` rows for the same invoice.
- **Fix:** added a check that rejects a manual payment if a payment with the same
  `transaction_id` already exists for that invoice.
- Also fixed: the Stripe webhook handler (`handleWebhook`) now skips re-processing a
  payment whose status is already `completed`, so a redelivered webhook event can't double-
  apply `addPayment()`.

### 6. Medium — Zip-slip in update/backup-restore extraction (RCE-adjacent)
- `UpdateController::installUpdate()` (`$zip->extractTo($extractPath)`) and
  `restoreBackup()` (`$zip->extractTo(base_path())`) extracted uploaded/downloaded zip
  archives with no path-traversal protection. A malicious archive with `../../` entries (or
  absolute/Windows-drive paths) could write files outside the intended directory.
  `restoreBackup()` also runs `DB::unprepared()` on a bundled `.sql` file — arbitrary SQL
  execution from the archive contents.
- **Fix:** added `assertZipIsSafe()`, called before both `extractTo()` calls, which rejects
  any archive containing an entry with `../`, a leading `/`, a Windows drive prefix, or that
  otherwise resolves outside the destination directory.
- Note: this endpoint is already gated behind superadmin authentication
  (`SuperadminAuthenticate` middleware), so exploitation requires an already-compromised
  superadmin account or a compromised/spoofed update-download source — this fix is
  defense-in-depth, not a fix for a publicly reachable hole.

## Not fixed in this pass (recommended follow-ups)

- **CORS (`config/cors.php`)**: `allowed_origins => ['*']` with `allowed_headers => ['*']`.
  Currently mitigated by `supports_credentials => false`, but should be tightened to an
  explicit allow-list before any cookie-based API auth is added.
- **SSRF surface in `UpdateController`**: `checkForUpdates()` / `downloadAndInstall()` fetch
  an admin-configured URL with no allow-list and no signature/checksum verification of the
  downloaded package. Worth adding package signing before trusting arbitrary update sources.
- **Tenant scoping fragility**: `Payment`/`Invoice` rely entirely on model global scopes
  (`company_id = auth()->user()->company_id`) rather than explicit `where()` clauses in
  controllers. This is correct today but silently breaks tenant isolation if any code path
  runs `withoutGlobalScopes()` or executes outside an authenticated HTTP context (e.g. a
  queued job). Consider adding explicit scoping in those cases as a defense-in-depth measure.
- **Session cookie security**: `SESSION_SECURE_COOKIE` has no enforced default; document
  setting it to `true` for production in `INSTALL.md`.
- **Repo hygiene**: delete `fix_duplicate_payments.sql` and `install.json.installed` from
  the repository once the underlying issues are confirmed fixed in your live database (kept
  in place for now since they may still be needed to clean up historical bad data — verify
  before removing).

---

# Pass 2 — 2026-09-26: auth hardening, thumbnails, `artisan serve`, Redis + Horizon

## Fixed

### 7. Critical — Auto-installer reachable on a live site (DB wipe / credential hijack)
- `/auto-install/*` had no `not.installed` middleware (only `/install/*` did). Any anonymous
  visitor could fetch a CSRF token from any page and `POST /auto-install/install`, whose
  `runInstall()` **drops every table**, or `POST /auto-install/database` to rewrite the `.env`
  DB credentials to a server they control. `/auto-install/test-connection` was also an open
  port scanner.
- **Fix:** both installer groups now use `not.installed` + `throttle:installer`. Verified: the
  attack POSTs now redirect to `/login` and all tables survive.

### 8. High — Email verification signature check bypassed
- `VerificationController::verify()` logged an invalid signature and then called
  `fulfill()` anyway, and the route had no `signed` middleware.
- **Fix:** route uses `signed`; controller aborts 403 on an invalid signature.

### 9. High — No brute-force / spam protection on auth
- Login, superadmin login, register, forgot/reset password and the contact form had no rate
  limiting; deactivated users (`is_active = 0`) could still log in.
- **Fix:**
  - Named rate limiters (`AppServiceProvider::configureRateLimiting`): login 5/min per
    email+IP and 20/min per IP; register 3/min and 10/hour per IP; password reset 5/min per
    IP and 5/hour per email; contact 2/min and 20/day; public invoice/quotation actions 20/min.
  - Per-account lockout in `LoginController` (5 failures → 5 min) and
    `Superadmin\AuthController` (5 failures → 15 min), with `Lockout` events and logging.
  - Inactive users/superadmins are rejected at `Auth::attempt()`.
  - `ProtectAgainstSpam` middleware + `<x-honeypot />` component on register, forgot-password
    and contact: hidden honeypot field + encrypted render timestamp (rejects < 3 s or > 12 h).
  - Register: emails lower-cased, `email:rfc,filter` (mitigates the Laravel 11 CRLF email-rule
    advisory), disposable domains blocked (`config/security.php`), links/HTML rejected in
    name and company name, stronger `Password::defaults()` (8+ chars, mixed case, number;
    plus HaveIBeenPwned check in production).
  - Password reset: one generic error for every failure, rotates `remember_token`, applies the
    password policy, and **queues** the email so response time no longer reveals whether an
    account exists.
  - `SecurityHeaders` middleware (X-Frame-Options SAMEORIGIN, nosniff, Referrer-Policy,
    Permissions-Policy, HSTS on HTTPS, `no-store` on auth pages).

### 10. High — Vulnerable dependencies
- 48 Composer advisories. Upgraded `barryvdh/laravel-dompdf` 2 → 3.1 (dompdf 3.1.6) and
  patched guzzle, league/commonmark, symfony/*, laravel/framework 11.56.1, etc. within their
  constraints.
- **Remaining:** 3 advisories in Laravel 11 itself, only fixed in Laravel 12.60+. They are listed
  in `composer.json` → `config.audit.ignore` so Composer can still resolve; remove them after
  upgrading to Laravel 12.

### 11. Medium — Recurring invoices never generated
- `app/Console/Kernel.php` is ignored by Laravel 11, so `invoices:generate-recurring` and
  `system:check-updates` were never scheduled. Moved to `routes/console.php` (old file kept at
  `storage/app/backup-2026-09-26/Console-Kernel.php.unused`).

### 12. Medium — Missing `notifications` / `failed_jobs` tables
- `QuotationResponseNotification` (database channel) was failing, and failed queue jobs had
  nowhere to go. Migrations added and run.

### 13. Invoice template thumbnails cropped/reflowed on narrow screens
- Thumbnails sized the iframe at 400% of the *card* width, so on phones, tablets and the
  onboarding grid the invoice was laid out in a ~240 px viewport and cropped.
- **Fix:** `<x-template-thumbnail>` renders the page at a fixed 800 px A4 width and scales it
  to the card with a `ResizeObserver`; iframes lazy-load. The `stdClass::$bill_to_name` preview
  errors (fixed 2026-07-31 by using real unsaved models) were re-verified: all 12 templates
  render to HTML and PDF.

### 14. `php artisan serve` hung / did not start
- Port 8000 is used by another local project, and with AMPPS MySQL stopped every request
  blocked on repeated DB connection attempts. `.env` also began with a UTF-8 BOM that hid the
  first `APP_NAME` line.
- **Fix:** app runs on `SERVER_PORT=8002` (`APP_URL` updated), BOM and dead duplicate removed,
  launch configs updated, `start-dev.ps1` added.

## Added — Redis + Horizon
- `predis/predis` and `laravel/horizon` installed. Cache, sessions and queues use Redis
  (dedicated DB 7/8 and `invapp_` prefix, since the Redis server is shared with other apps).
- Horizon supervisor handles queues `high`, `default`, `mail` (3 tries, backoff 10/60/300 s).
  The dashboard is superadmin-only. Contact-form, password-reset and quotation-response
  emails are queued on `mail`.
- On Windows, Horizon cannot run (no `pcntl`), so use `start-dev.ps1`. See INSTALL.md §5 for the
  production Supervisor setup.
