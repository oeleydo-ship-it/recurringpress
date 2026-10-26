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

### 8. Tenant domains: subdomains and custom domains (Cloudflare)

Tenants can give their client-facing invoice and quotation pages their own address (Settings → Domains):

* **Subdomain:** `acme.recurringpress.com`. It works as soon as it is claimed. Names are 3–32 characters,
  unique, and checked against a reserved list (`www`, `app`, `admin`, `api`, `mail`, `superadmin`, … in `config/tenancy.php`).
* **Custom domain:** `billing.theirdomain.com`, registered as a **Cloudflare for SaaS custom hostname**.
  Cloudflare verifies ownership, issues the certificate and proxies the traffic to this server.

**Which link emails use:** a verified custom domain first, then the subdomain, then `APP_URL`. Old links keep
working on the platform address.

**What is served on a tenant host:** only `/invoice/*` and `/quotation/*`, and only that tenant's documents (another
tenant's token returns 404). Other GET paths redirect to `APP_URL`. While either feature is on, any host that is
neither a platform host nor a tenant host gets a bare `404 Not Found`. Platform hosts are the `APP_URL` host and its
`www.` variant, `localhost` and IP addresses; add others with `PLATFORM_HOSTS=a.com,b.com`.

Both features are **off by default** and are switched on in **Superadmin → Custom domains**, after the
Cloudflare setup below.

#### Cloudflare setup (one-time)

`recurringpress.com` currently uses GoDaddy nameservers (`ns07/ns08.domaincontrol.com`) and the server answers
directly as `nginx/1.24.0 (Ubuntu)`, so none of the following exists yet:

1. **Move the zone to Cloudflare.** Add `recurringpress.com` to Cloudflare and switch the nameservers at GoDaddy.
   Cloudflare for SaaS needs the platform's own zone on Cloudflare. Re-create the existing DNS records (A record to
   the server, MX, etc.) and set them to **Proxied**.
2. **Fallback origin.** Add a proxied record such as `customers.recurringpress.com` → A `<server IP>`. Under
   **SSL/TLS → Custom Hostnames**, enable Cloudflare for SaaS (the plan must include it) and set that record as the
   fallback origin.
3. **Wildcard for subdomains.** Add a proxied `*.recurringpress.com` → A `<server IP>`. Cloudflare's Universal SSL
   covers one level of subdomains, so `acme.recurringpress.com` gets HTTPS with no work on the server.
4. **API token.** Under My Profile → API Tokens, create a token with **Zone → SSL and Certificates → Edit** and
   **Zone → Zone → Read**, limited to this zone.
5. **Superadmin → Custom domains.** Enter the zone ID, the token (stored encrypted with `APP_KEY`) and the CNAME target
   (`customers.recurringpress.com`), then click **Test connection**. It checks the token and that the fallback origin
   matches. Then switch on tenant custom domains and/or subdomains.

#### Origin server (nginx) changes

* **Accept any Host.** Traffic for every tenant hostname arrives with its own Host header. Make the app's vhost the
  default server (`listen 443 ssl default_server; server_name _;`, and the same for port 80). Laravel rejects
  unknown hosts itself.
* **TLS between Cloudflare and the server.** Use SSL mode **Full**. Install a Cloudflare Origin CA certificate (or
  keep the current one) on nginx. **Full (strict)** validates the origin certificate against each tenant hostname
  and will fail for custom domains unless the origin certificate covers them.
* **Real client IPs.** Behind Cloudflare every request comes from Cloudflare's IPs, and login and rate limits are
  per IP. Restore the visitor IP in nginx with `set_real_ip_from <each Cloudflare range>;` and
  `real_ip_header CF-Connecting-IP;` (ranges: https://www.cloudflare.com/ips/). Or trust Cloudflare's ranges in
  Laravel's `trustProxies`.
* Leave `SESSION_DOMAIN` empty (host-only cookies). The app forces host-only cookies on tenant hosts anyway.

#### How a tenant connects a custom domain

Saving the domain creates the custom hostname on Cloudflare (TXT validation). The tenant then sees three records:
a CNAME to the fallback origin, an ownership TXT (`_cf-custom-hostname.…`) and a certificate TXT
(`_acme-challenge.…`). **Check status** asks Cloudflare. The domain counts as verified once both the hostname and its
certificate are `active`. `domains:recheck` runs hourly: it verifies pending domains and un-verifies a domain after
two failed checks in a row. A Cloudflare API outage is recorded but never un-verifies a domain. Disconnecting deletes
the hostname from Cloudflare. A root domain (`theirdomain.com`) needs a DNS provider that supports CNAME
flattening or ALIAS records.

Related env vars: `TENANT_SUBDOMAIN_BASE` (default: `APP_URL` host without `www.`),
`CUSTOM_DOMAIN_SCHEME` / `TENANT_SUBDOMAIN_SCHEME` (default `https`), `CLOUDFLARE_API_BASE` (only for testing
against a fake API).

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
