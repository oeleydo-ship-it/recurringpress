<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Company;
use App\Models\SubscriptionPlan;
use Symfony\Component\Process\Process;

class AutoInstallerService
{
    protected array $manifest = [];
    protected string $manifestPath = '';

    /**
     * Check if there's a pending installation
     */
    public function hasPendingInstallation(): bool
    {
        if (!config('autoinstaller.enabled', true)) {
            return false;
        }

        // Check if already installed
        if (File::exists(storage_path(config('autoinstaller.installed_marker', 'installed')))) {
            return false;
        }

        // Check for manifest in base path
        $manifestFile = base_path(config('autoinstaller.manifest', 'install.json'));

        if (File::exists($manifestFile)) {
            $this->manifestPath = $manifestFile;
            return true;
        }

        return false;
    }

    /**
     * Get the manifest data
     */
    public function getManifest(): array
    {
        if (empty($this->manifest) && !empty($this->manifestPath)) {
            $this->loadManifest();
        }

        return $this->manifest;
    }

    /**
     * Load manifest from file
     */
    protected function loadManifest(): void
    {
        if (File::exists($this->manifestPath)) {
            $content = File::get($this->manifestPath);
            $this->manifest = json_decode($content, true) ?? [];
        }
    }

    /**
     * Set manifest path and load
     */
    public function setManifestPath(string $path): self
    {
        $this->manifestPath = $path;
        $this->loadManifest();
        return $this;
    }

    /**
     * Check server requirements
     */
    public function checkRequirements(): array
    {
        $requirements = [];
        $allPassed = true;

        // PHP Version
        $requiredPhp = $this->manifest['requirements']['php']
            ?? config('autoinstaller.php_version', '8.3.0');

        $phpPassed = version_compare(PHP_VERSION, $requiredPhp, '>=');
        $requirements['php'] = [
            'name' => "PHP Version >= {$requiredPhp}",
            'required' => $requiredPhp,
            'current' => PHP_VERSION,
            'passed' => $phpPassed,
        ];

        if (!$phpPassed) {
            $allPassed = false;
        }

        // Extensions
        $extensions = $this->manifest['requirements']['extensions']
            ?? config('autoinstaller.extensions', []);

        foreach ($extensions as $extension) {
            $passed = extension_loaded($extension);
            $requirements["ext_{$extension}"] = [
                'name' => "PHP Extension: {$extension}",
                'required' => 'Installed',
                'current' => $passed ? 'Installed' : 'Not Installed',
                'passed' => $passed,
            ];

            if (!$passed) {
                $allPassed = false;
            }
        }

        return [
            'requirements' => $requirements,
            'all_passed' => $allPassed,
        ];
    }

    /**
     * Check folder permissions
     */
    public function checkPermissions(): array
    {
        $permissions = [];
        $allPassed = true;

        $requiredPermissions = $this->manifest['requirements']['permissions']
            ?? array_keys(config('autoinstaller.permissions', []));

        foreach ($requiredPermissions as $folder) {
            $path = base_path($folder);
            $isWritable = is_writable($path);

            $permissions[$folder] = [
                'path' => $folder,
                'required' => 'Writable',
                'current' => $isWritable ? 'Writable' : 'Not Writable',
                'passed' => $isWritable,
            ];

            if (!$isWritable) {
                $allPassed = false;
            }
        }

        return [
            'permissions' => $permissions,
            'all_passed' => $allPassed,
        ];
    }

    /**
     * Test database connection
     */
    public function testDatabaseConnection(array $credentials): array
    {
        try {
            $connection = @mysqli_connect(
                $credentials['host'],
                $credentials['username'],
                $credentials['password'] ?? '',
                $credentials['database'],
                (int) ($credentials['port'] ?? 3306)
            );

            if (!$connection) {
                return [
                    'success' => false,
                    'message' => 'Could not connect to database. Please check your credentials.',
                ];
            }

            mysqli_close($connection);

            return [
                'success' => true,
                'message' => 'Database connection successful!',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Configure database in .env file
     */
    public function configureDatabase(array $credentials): bool
    {
        try {
            $this->updateEnvFile([
                'DB_HOST' => $credentials['host'],
                'DB_PORT' => $credentials['port'] ?? '3306',
                'DB_DATABASE' => $credentials['database'],
                'DB_USERNAME' => $credentials['username'],
                'DB_PASSWORD' => $credentials['password'] ?? '',
                // Ensure quote safety logic is handled by caller or updateEnvFile generally doesn't strip quotes by itself
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to configure database: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Configure application settings
     */
    public function configureApplication(array $settings): bool
    {
        try {
            $values = [
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
            ];

            if (!empty($settings['app_name'])) {
                $values['APP_NAME'] = '"' . $settings['app_name'] . '"';
            }

            if (!empty($settings['app_url'])) {
                $values['APP_URL'] = $settings['app_url'];
            }

            $this->updateEnvFile($values);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to configure application: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Run the installation process
     */
    public function runInstallation(array $adminData = []): array
    {
        $steps = [];

        try {
            // Clear config cache first
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            $steps[] = ['step' => 'Clear cache', 'status' => 'success'];

            // Reload database configuration by reading .env directly (bypassing cache/env() issues)
            $envPath = base_path('.env');
            if (file_exists($envPath)) {
                $envContent = file_get_contents($envPath);
                preg_match('/^DB_HOST=(.*)$/m', $envContent, $hostMatch);
                preg_match('/^DB_DATABASE=(.*)$/m', $envContent, $dbMatch);
                preg_match('/^DB_USERNAME=(.*)$/m', $envContent, $userMatch);
                preg_match('/^DB_PASSWORD=(.*)$/m', $envContent, $passMatch);
                preg_match('/^DB_PORT=(.*)$/m', $envContent, $portMatch);

                // Helper to strip quotes
                $clean = function ($val) {
                    $val = trim($val ?? '');
                    if (
                        (str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                        (str_starts_with($val, "'") && str_ends_with($val, "'"))
                    ) {
                        return substr($val, 1, -1);
                    }
                    return $val;
                };

                $host = $clean($hostMatch[1] ?? '127.0.0.1');
                $port = (int) $clean($portMatch[1] ?? '3306');
                $database = $clean($dbMatch[1] ?? '');
                $username = $clean($userMatch[1] ?? '');
                $password = $clean($passMatch[1] ?? '');

                config([
                    'database.default' => 'mysql', // Force default connection
                    'database.connections.mysql.host' => $host,
                    'database.connections.mysql.port' => $port,
                    'database.connections.mysql.database' => $database,
                    'database.connections.mysql.username' => $username,
                    'database.connections.mysql.password' => $password,
                ]);

                // "Nuclear" Option: Drop tables using raw MySQLi to guarantee we hit the right DB
                if ($database && $username) {
                    try {
                        $conn = @mysqli_connect($host, $username, $password, $database, $port);
                        if ($conn) {
                            mysqli_query($conn, 'SET FOREIGN_KEY_CHECKS=0');
                            $result = mysqli_query($conn, 'SHOW TABLES');
                            $droppedCount = 0;
                            while ($row = mysqli_fetch_array($result)) {
                                $tbl = $row[0];
                                mysqli_query($conn, "DROP TABLE IF EXISTS `{$tbl}`");
                                $droppedCount++;
                            }
                            mysqli_query($conn, 'SET FOREIGN_KEY_CHECKS=1');
                            mysqli_close($conn);

                            $steps[] = ['step' => "Clear tables (DB: $database, Dropped: $droppedCount)", 'status' => 'success'];
                        } else {
                            $steps[] = ['step' => "Clear tables (Connection Failed: $database)", 'status' => 'warning', 'message' => 'Could not connect via MySQLi'];
                        }
                    } catch (\Exception $e) {
                        $steps[] = ['step' => 'Clear existing tables', 'status' => 'warning', 'message' => $e->getMessage()];
                    }
                }
            }

            DB::purge('mysql');
            DB::reconnect('mysql');
            $steps[] = ['step' => 'Database reconnection', 'status' => 'success'];

            // Run standard migrations (now that we know tables are gone)
            $migrationsPath = $this->manifest['database']['migrations_path'] ?? 'database/migrations';
            Artisan::call('migrate', [
                '--force' => true,
                '--path' => $migrationsPath
            ]);
            $steps[] = ['step' => 'Run migrations', 'status' => 'success'];

            // Run seeders
            $seeders = $this->manifest['database']['seeders'] ?? ['DatabaseSeeder'];
            foreach ($seeders as $seeder) {
                try {
                    Artisan::call('db:seed', [
                        '--class' => $seeder,
                        '--force' => true,
                    ]);
                    $steps[] = ['step' => "Run seeder: {$seeder}", 'status' => 'success'];
                } catch (\Exception $e) {
                    $steps[] = ['step' => "Run seeder: {$seeder}", 'status' => 'warning', 'message' => $e->getMessage()];
                }
            }

            // Create admin user if data provided
            if (!empty($adminData)) {
                $this->createAdminUser($adminData);
                $steps[] = ['step' => 'Create admin user', 'status' => 'success'];
            }

            // Generate app key if not set
            if (empty(env('APP_KEY'))) {
                Artisan::call('key:generate', ['--force' => true]);
                $steps[] = ['step' => 'Generate app key', 'status' => 'success'];
            }

            // Create storage link
            if (function_exists('symlink')) {
                try {
                    Artisan::call('storage:link', ['--force' => true]);
                    $steps[] = ['step' => 'Create storage link', 'status' => 'success'];
                } catch (\Exception $e) {
                    $steps[] = ['step' => 'Create storage link', 'status' => 'warning', 'message' => 'Symlink not available'];
                }
            }

            // Run post-install commands
            $commands = $this->manifest['post_install']['commands']
                ?? config('autoinstaller.post_install_commands', []);

            foreach ($commands as $command) {
                try {
                    Artisan::call($command);
                    $steps[] = ['step' => "Run command: {$command}", 'status' => 'success'];
                } catch (\Exception $e) {
                    $steps[] = ['step' => "Run command: {$command}", 'status' => 'warning', 'message' => $e->getMessage()];
                }
            }

            // Mark as installed
            $this->markAsInstalled();
            $steps[] = ['step' => 'Mark as installed', 'status' => 'success'];

            return [
                'success' => true,
                'steps' => $steps,
                'redirect' => $this->manifest['post_install']['redirect'] ?? '/login',
            ];

        } catch (\Exception $e) {
            Log::error('Installation failed: ' . $e->getMessage());
            $steps[] = ['step' => 'Installation', 'status' => 'error', 'message' => $e->getMessage()];

            return [
                'success' => false,
                'steps' => $steps,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create admin user
     */
    protected function createAdminUser(array $data): User
    {
        $plan = SubscriptionPlan::where('slug', 'enterprise')->first()
            ?? SubscriptionPlan::first();

        $company = Company::create([
            'name' => $data['company_name'] ?? $data['name'] . "'s Company",
            'email' => $data['email'],
            'subscription_plan_id' => $plan?->id,
            'is_active' => true,
            'currency' => 'USD',
            'currency_symbol' => '$',
            'invoice_prefix' => 'INV-',
            'invoice_start_number' => 1,
        ]);

        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'company_id' => $company->id,
            'role' => $this->manifest['admin']['default_role'] ?? 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Mark installation as complete
     */
    public function markAsInstalled(): void
    {
        $installedFile = storage_path(config('autoinstaller.installed_marker', 'installed'));

        File::put($installedFile, json_encode([
            'version' => $this->manifest['version'] ?? config('installer.version', '1.0.0'),
            'installed_at' => now()->toIso8601String(),
            'manifest' => $this->manifest['name'] ?? 'Unknown',
        ]));

        // Optionally rename the manifest file to prevent re-detection
        if (File::exists($this->manifestPath)) {
            $backupPath = $this->manifestPath . '.installed';
            File::move($this->manifestPath, $backupPath);
        }
    }

    /**
     * Update .env file values
     */
    protected function updateEnvFile(array $values): void
    {
        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            File::copy(base_path('.env.example'), $envPath);
        }

        $envContent = File::get($envPath);

        foreach ($values as $key => $value) {
            $pattern = "/^{$key}=.*/m";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, "{$key}={$value}", $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        File::put($envPath, $envContent);
    }

    /**
     * Get application name from manifest
     */
    public function getAppName(): string
    {
        return $this->manifest['name'] ?? 'Application';
    }

    /**
     * Get application version from manifest
     */
    public function getAppVersion(): string
    {
        return $this->manifest['version'] ?? '1.0.0';
    }

    /**
     * Check if database is required
     */
    public function isDatabaseRequired(): bool
    {
        return $this->manifest['database']['required'] ?? true;
    }

    /**
     * Check if admin setup is required
     */
    public function isAdminRequired(): bool
    {
        return $this->manifest['admin']['required'] ?? true;
    }
}
