# Invoice Manager Installation Guide

Follow these steps to install the Invoice Manager application on your server.

> Full guide (cPanel, Plesk, DirectAdmin, other panels, VPS, and how to use the platform): open `docs/index.html` in a browser.

## Server Requirements

Before you begin, ensure your server meets the following requirements:

*   **PHP**: Version 8.3 or higher (8.4 recommended) — required by Laravel 13
*   **Node.js**: 20.19+ or 22.12+ (only on the machine that builds the front-end assets)
*   **Redis**: 6 or higher
*   **Database**: MySQL 5.7+ or MariaDB 10.3+
*   **Web Server**: Apache or Nginx
*   **PHP Extensions**:
    *   BCMath
    *   Ctype
    *   Fileinfo
    *   JSON
    *   Mbstring
    *   OpenSSL
    *   PDO
    *   PDO_MySQL
    *   Tokenizer
    *   XML
    *   Zip

## Installation Steps

### 1. Upload Files
Upload the application files to your web server.
*   **Important**: Configure your web server's (Apache/Nginx) document root to point to the `public` folder inside the application directory.
*   Example: `/var/www/html/your-app/public`

### 1b. Build the front-end (React + Inertia)
The UI is a React/Inertia app compiled with Vite. If your upload does not already contain `public/build` and `bootstrap/ssr`, build them:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build   # builds public/build (browser) and bootstrap/ssr (server-side rendering)
```

Re-run `npm run build` after every update that changes files in `resources/js` or `resources/css`.

### 2. Set Permissions
Ensure the following directories are writable (775 permission):
*   `storage/` (and all subdirectories)
*   `bootstrap/cache/`

### 3. Create Database
*   Log in to your hosting control panel (cPanel, Plesk, etc.).
*   Create a new MySQL Database.
*   Create a new Database User and assign it to the database with full privileges.
*   **Note down the Database Name, Username, and Password.**

### 4. Run the Installer
*   Open your website URL in a web browser (e.g., `https://yourdomain.com/`).
*   You will be redirected to the installation wizard.
*   **Requirements Check**: The system will check if your server meets the requirements. Click "Next" if everything passes.
*   **Database Setup**: Enter the database credentials you created in Step 3.
*   **Application Setup**: Enter your App Name and URL.
*   **Admin Setup**: Create your Super Administrator account and initial Company details.
*   Click **Install**.

The system will now migrate the database, seed default data, and create your admin account.

### 5. Post-Installation Setup

#### Cron Job (Task Scheduler)
To ensure recurring invoices and system maintenance tasks run correctly, add the following Cron Job entry to your server:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

*Replace `/path-to-your-project` with the actual path to your application's root directory which you can find in the application settings or by asking your hosting provider.*

#### Queue Worker
Emails, recurring invoices and other background jobs run on Redis queues. Use **Horizon** (see section 5 below).
On hosting without Supervisor/pcntl, run a plain worker instead:
`php artisan queue:work redis --queue=high,default,mail --tries=3 --max-time=3600`.

## System Updates
The application includes an auto-update feature.
1.  Log in as Super Admin.
2.  Go to **Superadmin > System Updates**.
3.  Click "Check for Updates".
4.  If an update is available, click "Download & Install".
    *   The system will automatically create a backup of your files and database before updating.

### 5. Redis, Queues & Horizon

The app uses **Redis** for cache, sessions, rate limiting and queues, and **Laravel Horizon** to run and monitor queue workers.

* Install Redis 6+ and set `REDIS_*` in `.env` (`REDIS_CLIENT=predis` works without a PHP extension).
  If other apps share the Redis server, give this one its own `REDIS_DB` / `REDIS_CACHE_DB` / `REDIS_PREFIX` / `HORIZON_PREFIX`.
* Run `php artisan migrate` (creates `failed_jobs` and `notifications`).
* Keep Horizon running with Supervisor (Linux; Horizon requires the `pcntl` and `posix` PHP extensions):

```ini
[program:invoice-horizon]
command=php /var/www/your-app/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/your-app/storage/logs/horizon.log
stopwaitsecs=3600
```

* After every deploy run `php artisan horizon:terminate` (Supervisor restarts it with the new code).
* Add the scheduler cron: `* * * * * php /var/www/your-app/artisan schedule:run >> /dev/null 2>&1`
* The dashboard is at `/horizon` and is restricted to logged-in **superadmins**.
* **Windows / AMPPS (development):** Horizon cannot run natively on Windows, and AMPPS ships PHP 8.2 (too old for
  Laravel 13). Install PHP 8.4 at `C:\php84` (with the extensions listed above) and use `start-dev.ps1`, which starts
  `artisan serve` (port 8002), a `queue:work redis` worker, `schedule:work` and the Vite dev server (`-Build` builds
  the assets and starts the SSR server instead). To serve through AMPPS Apache, point it at PHP 8.4 as well.

### 6. Server-side rendering (SSR)

Pages are rendered on the server first (faster first paint, better SEO for the landing/blog pages).
SSR is optional — if the SSR process is not running, pages simply render in the browser.

```ini
[program:invoice-ssr]
command=php /var/www/your-app/artisan inertia:start-ssr
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/your-app/storage/logs/ssr.log
```

* Requires Node.js on the server. It listens on `127.0.0.1:13714` (change with `INERTIA_SSR_URL` if needed).
* After each deploy/rebuild run `php artisan inertia:stop-ssr` (Supervisor restarts it with the new bundle).

### Deploy checklist

```bash
php artisan down
git pull   # or upload the new files
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
php artisan horizon:terminate
php artisan inertia:stop-ssr
php artisan up
```

### 7. Production security checklist

* `APP_ENV=production`, `APP_DEBUG=false`, and `APP_URL` set to the exact public URL (email verification links are signed against it).
* Serve over HTTPS and set `SESSION_SECURE_COOKIE=true`.
* Extra disposable-email domains can be blocked with `DISPOSABLE_EMAIL_DOMAINS=foo.com,bar.net`.

### 8. Tenant custom domains

Tenants can connect their own domain (Settings → Custom domain), e.g. `billing.theirdomain.com`. Once its DNS
is verified, their public invoice/quotation pages work on that domain and new emails link to it. Old links on
the platform address keep working.

**How it works**

* The tenant adds two DNS records: a TXT record `_recurringpress.<domain>` = `recurringpress-verify=<token>`
  (proves ownership) and a CNAME `<domain>` → the `APP_URL` host (or A records to the server IP for a root domain).
* "Verify DNS" checks both. `domains:recheck` runs hourly: it verifies pending domains, and un-verifies a domain
  after two failed checks in a row.
* On a custom domain, only `/invoice/*` and `/quotation/*` are served, and only for that tenant's documents
  (another tenant's token returns 404). Other GET paths redirect to `APP_URL`. When the feature is on, any host
  that is neither a platform host nor a verified tenant domain gets a bare `404 Not Found`. Platform hosts are
  the `APP_URL` host and its `www.` variant, `localhost`, and IP addresses; add any others with
  `PLATFORM_HOSTS=a.com,b.com`.

**The feature is off by default** (`CUSTOM_DOMAINS_ENABLED=false`) because it needs server work first. Links are
built as `https://<tenant-domain>/…`, so the server must be able to present a TLS certificate for every tenant
domain. A single certificate for recurringpress.com won't cover them, and plain nginx can't issue certificates
on demand. The production server currently answers as `nginx/1.24.0 (Ubuntu)` with no CDN in front, so pick one of:

1. **Caddy in front, on-demand TLS (recommended for a single VPS).** Caddy listens on 80/443 and gets a
   Let's Encrypt certificate the first time a verified domain is visited. nginx moves to `127.0.0.1:8080`.
   The app exposes the "ask" endpoint `GET /tls/allowed?domain=…`. It returns 200 only for platform hosts and
   verified tenant domains, so nobody can make the server request certificates for arbitrary domains.

   ```caddy
   {
       on_demand_tls {
           ask http://127.0.0.1:8080/tls/allowed
       }
   }

   recurringpress.com, www.recurringpress.com {
       reverse_proxy 127.0.0.1:8080
   }

   https:// {
       tls {
           on_demand
       }
       reverse_proxy 127.0.0.1:8080
   }
   ```

   Then make the nginx vhost the default server on `127.0.0.1:8080` (`listen 127.0.0.1:8080 default_server;`
   `server_name _;`) so it accepts any Host. Laravel rejects unknown hosts itself. Trust the proxy so the app
   sees `https` and the client IP (`TrustProxies` for `127.0.0.1`).
2. **Cloudflare for SaaS (Custom Hostnames).** Cloudflare issues the certificates. Tenants CNAME to your
   fallback origin hostname, so set `CUSTOM_DOMAIN_CNAME_TARGET` to it. nginx must still accept any Host.
3. **certbot per domain.** Run `certbot --nginx -d <domain>` for each verified domain and add a `server_name`.
   This works but is manual, so only use it for a handful of tenants.

When the chosen setup serves HTTPS for a test domain, set `CUSTOM_DOMAINS_ENABLED=true` and run
`php artisan optimize`. Also check that `SESSION_DOMAIN` is empty or the platform domain: on custom domains the app
switches to host-only session cookies automatically.

Related env vars: `CUSTOM_DOMAIN_CNAME_TARGET` (default: `APP_URL` host), `CUSTOM_DOMAIN_IPS` (A-record IPs
shown for root domains; default: whatever the CNAME target resolves to), `CUSTOM_DOMAIN_SCHEME` (default `https`).

### 9. Tenant SMTP

Tenants can send client emails (invoices and quotations, including recurring ones) through their own SMTP server
(Settings → Email (SMTP)). The password is stored with Laravel's `encrypted` cast, so it depends on `APP_KEY`:
rotating the key makes saved passwords unreadable, and tenants must re-enter them.

* A server is only used after "Send test email" succeeds. Changing host, port, encryption, username, password or
  From address requires a new test.
* If the tenant's server fails at send time, the error is logged and shown in their settings. The email is sent
  through the platform mailer instead, with Reply-To set to the company email.
* In production, SMTP hosts on private, loopback or reserved addresses are refused, and only ports
  25/465/587/2525 are allowed (SSRF protection; `TENANT_SMTP_BLOCK_PRIVATE`). Outbound connections to those
  ports must be allowed by the server's firewall and hosting provider.

***

**Need Help?**
If you encounter any issues during installation, please check the `storage/logs/laravel.log` file for error details.
