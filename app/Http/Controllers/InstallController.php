<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class InstallController extends Controller
{
    public function index()
    {
        return view('install.index');
    }

    public function requirements()
    {
        $requirements = $this->checkRequirements();
        $permissions = $this->checkPermissions();

        return view('install.requirements', compact('requirements', 'permissions'));
    }

    public function database()
    {
        return view('install.database');
    }

    public function setupDatabase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'db_host' => 'required|string',
            'db_port' => 'required|numeric',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $connection = @mysqli_connect(
                $request->db_host,
                $request->db_username,
                $request->db_password ?? '',
                $request->db_database,
                $request->db_port
            );

            if (!$connection) {
                return back()->with('error', 'Could not connect to database. Please check your credentials.')->withInput();
            }

            mysqli_close($connection);

            $this->updateEnvFile([
                'DB_HOST' => $request->db_host,
                'DB_PORT' => $request->db_port,
                'DB_DATABASE' => $request->db_database,
                'DB_USERNAME' => $request->db_username,
                'DB_PASSWORD' => $request->db_password ?? '',
            ]);

            return redirect()->route('install.application');

        } catch (\Exception $e) {
            return back()->with('error', 'Database connection failed: ' . $e->getMessage())->withInput();
        }
    }

    public function application()
    {
        return view('install.application');
    }

    public function setupApplication(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $this->updateEnvFile([
            'APP_NAME' => '"' . $request->app_name . '"',
            'APP_URL' => $request->app_url,
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
        ]);

        return redirect()->route('install.admin');
    }

    public function admin()
    {
        return view('install.admin');
    }

    public function setupAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'company_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            Artisan::call('config:clear');
            Artisan::call('cache:clear');

            // Read fresh .env values for table dropping
            $envPath = base_path('.env');
            if (File::exists($envPath)) {
                $envContent = File::get($envPath);
                preg_match('/^DB_HOST=(.*)$/m', $envContent, $hostMatch);
                preg_match('/^DB_DATABASE=(.*)$/m', $envContent, $dbMatch);
                preg_match('/^DB_USERNAME=(.*)$/m', $envContent, $userMatch);
                preg_match('/^DB_PASSWORD=(.*)$/m', $envContent, $passMatch);
                preg_match('/^DB_PORT=(.*)$/m', $envContent, $portMatch);

                $host = trim($hostMatch[1] ?? 'localhost');
                $database = trim($dbMatch[1] ?? '');
                $username = trim($userMatch[1] ?? '');
                $password = trim($passMatch[1] ?? '');
                $port = (int) trim($portMatch[1] ?? '3306');

                // Drop all existing tables before migration
                if ($database && $username) {
                    $conn = @mysqli_connect($host, $username, $password, $database, $port);
                    if ($conn) {
                        mysqli_query($conn, 'SET FOREIGN_KEY_CHECKS=0');
                        $result = mysqli_query($conn, 'SHOW TABLES');
                        while ($row = mysqli_fetch_array($result)) {
                            mysqli_query($conn, "DROP TABLE IF EXISTS `{$row[0]}`");
                        }
                        mysqli_query($conn, 'SET FOREIGN_KEY_CHECKS=1');
                        mysqli_close($conn);
                    }
                }
            }

            config([
                'database.connections.mysql.host' => env('DB_HOST'),
                'database.connections.mysql.port' => env('DB_PORT'),
                'database.connections.mysql.database' => env('DB_DATABASE'),
                'database.connections.mysql.username' => env('DB_USERNAME'),
                'database.connections.mysql.password' => env('DB_PASSWORD'),
            ]);

            DB::purge('mysql');
            DB::reconnect('mysql');

            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);

            $plan = SubscriptionPlan::where('slug', 'enterprise')->first()
                ?? SubscriptionPlan::first();

            $company = Company::create([
                'name' => $request->company_name,
                'email' => $request->email,
                'subscription_plan_id' => $plan?->id,
                'is_active' => true,
                'currency' => 'USD',
                'currency_symbol' => '$',
                'invoice_prefix' => 'INV-',
                'invoice_start_number' => 1,
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'company_id' => $company->id,
                'role' => 'admin',
                'is_active' => true,
            ]);

            if (!File::exists(base_path('.env'))) {
                File::copy(base_path('.env.example'), base_path('.env'));
            }

            if (empty(env('APP_KEY'))) {
                Artisan::call('key:generate', ['--force' => true]);
            }

            if (function_exists('symlink')) {
                Artisan::call('storage:link', ['--force' => true]);
            }

            File::put(storage_path('installed'), json_encode([
                'version' => config('installer.version', '1.0.0'),
                'installed_at' => now()->toIso8601String(),
            ]));

            return redirect()->route('install.complete');

        } catch (\Exception $e) {
            return back()->with('error', 'Installation failed: ' . $e->getMessage())->withInput();
        }
    }

    public function complete()
    {
        return view('install.complete');
    }

    protected function checkRequirements(): array
    {
        $requirements = [];
        $phpVersion = config('installer.php_version', '8.1.0');

        $requirements['php'] = [
            'name' => 'PHP Version >= ' . $phpVersion,
            'passed' => version_compare(PHP_VERSION, $phpVersion, '>='),
            'current' => PHP_VERSION,
        ];

        foreach (config('installer.extensions', []) as $extension) {
            $requirements[$extension] = [
                'name' => 'PHP Extension: ' . $extension,
                'passed' => extension_loaded($extension),
                'current' => extension_loaded($extension) ? 'Installed' : 'Not Installed',
            ];
        }

        return $requirements;
    }

    protected function checkPermissions(): array
    {
        $permissions = [];

        foreach (config('installer.permissions', []) as $folder => $permission) {
            $path = base_path($folder);
            $isWritable = is_writable($path);

            $permissions[$folder] = [
                'path' => $folder,
                'required' => $permission,
                'passed' => $isWritable,
                'current' => $isWritable ? 'Writable' : 'Not Writable',
            ];
        }

        return $permissions;
    }

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
}
