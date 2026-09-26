# Invoice Manager Installation Guide

Follow these steps to install the Invoice Manager application on your server.

## Server Requirements

Before you begin, ensure your server meets the following requirements:

*   **PHP**: Version 8.1 or higher
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

#### Queue Worker (Optional but Recommended)
For better performance with email sending, you can set up a queue worker.
If you are using Supervisor:

```ini
[program:invoice-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path-to-your-project/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=your_username
numprocs=1
redirect_stderr=true
stdout_logfile=/path-to-your-project/worker.log
```

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
* **Windows / AMPPS (development):** Horizon cannot run natively on Windows. Use `start-dev.ps1`, which starts
  `artisan serve` (port 8002), a `queue:work redis` worker and `schedule:work`.

### 6. Production security checklist

* `APP_ENV=production`, `APP_DEBUG=false`, and `APP_URL` set to the exact public URL (email verification links are signed against it).
* Serve over HTTPS and set `SESSION_SECURE_COOKIE=true`.
* Extra disposable-email domains can be blocked with `DISPOSABLE_EMAIL_DOMAINS=foo.com,bar.net`.

***

**Need Help?**
If you encounter any issues during installation, please check the `storage/logs/laravel.log` file for error details.
