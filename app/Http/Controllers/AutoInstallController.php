<?php

namespace App\Http\Controllers;

use App\Services\AutoInstallerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AutoInstallController extends Controller
{
    protected AutoInstallerService $installer;

    public function __construct(AutoInstallerService $installer)
    {
        $this->installer = $installer;

        // Load manifest if exists
        $manifestFile = base_path(config('autoinstaller.manifest', 'install.json'));
        if (file_exists($manifestFile)) {
            $this->installer->setManifestPath($manifestFile);
        }
    }

    /**
     * Show detected pending installations
     */
    public function detect()
    {
        if (!$this->installer->hasPendingInstallation()) {
            // Check if it's because already installed
            if (file_exists(storage_path(config('autoinstaller.installed_marker', 'installed')))) {
                return redirect()->route('landing');
            }

            // Otherwise it's missing configuration/manifest - show error instead of redirecting (avoids loop)
            $manifestPath = config('autoinstaller.manifest', 'install.json');
            return response()->view('autoinstall.error', [
                'title' => 'Installation Not Available',
                'message' => "The installer cannot run because the manifest file ({$manifestPath}) is missing or the installer is disabled configuration."
            ], 500);
        }

        $manifest = $this->installer->getManifest();

        return view('autoinstall.detect', [
            'appName' => $this->installer->getAppName(),
            'appVersion' => $this->installer->getAppVersion(),
            'manifest' => $manifest,
        ]);
    }

    /**
     * Show requirements check page
     */
    public function requirements()
    {
        $requirements = $this->installer->checkRequirements();
        $permissions = $this->installer->checkPermissions();

        $allPassed = $requirements['all_passed'] && $permissions['all_passed'];

        return view('autoinstall.requirements', [
            'appName' => $this->installer->getAppName(),
            'requirements' => $requirements['requirements'],
            'permissions' => $permissions['permissions'],
            'allPassed' => $allPassed,
        ]);
    }

    /**
     * Show database configuration form
     */
    public function database()
    {
        return view('autoinstall.database', [
            'appName' => $this->installer->getAppName(),
        ]);
    }

    /**
     * Process database configuration
     */
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

        $credentials = [
            'host' => $request->db_host,
            'port' => $request->db_port,
            'database' => $request->db_database,
            'username' => $request->db_username,
            'password' => $request->db_password ?? '',
        ];

        // Test connection
        $result = $this->installer->testDatabaseConnection($credentials);

        if (!$result['success']) {
            return back()->with('error', $result['message'])->withInput();
        }

        // Configure database
        $this->installer->configureDatabase($credentials);

        // Store in session for admin step
        session(['autoinstall_db' => $credentials]);

        return redirect()->route('autoinstall.application');
    }

    /**
     * Show application settings form
     */
    public function application()
    {
        return view('autoinstall.application', [
            'appName' => $this->installer->getAppName(),
        ]);
    }

    /**
     * Process application settings
     */
    public function setupApplication(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $this->installer->configureApplication([
            'app_name' => $request->app_name,
            'app_url' => $request->app_url,
        ]);

        session([
            'autoinstall_app' => [
                'app_name' => $request->app_name,
                'app_url' => $request->app_url,
            ]
        ]);

        if ($this->installer->isAdminRequired()) {
            return redirect()->route('autoinstall.admin');
        }

        return redirect()->route('autoinstall.install');
    }

    /**
     * Show admin setup form
     */
    public function admin()
    {
        return view('autoinstall.admin', [
            'appName' => $this->installer->getAppName(),
        ]);
    }

    /**
     * Process admin setup and show install page
     */
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

        session([
            'autoinstall_admin' => [
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'company_name' => $request->company_name,
            ]
        ]);

        return redirect()->route('autoinstall.install');
    }

    /**
     * Show installation page
     */
    public function showInstall()
    {
        return view('autoinstall.install', [
            'appName' => $this->installer->getAppName(),
            'appVersion' => $this->installer->getAppVersion(),
        ]);
    }

    /**
     * Run the installation
     */
    public function runInstall()
    {
        // Clear OPcache to ensure fresh code runs
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        // Manually drop all tables BEFORE calling the service (bypass cache)
        try {
            // Read fresh .env values
            $envPath = base_path('.env');
            if (file_exists($envPath)) {
                $envContent = file_get_contents($envPath);
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
        } catch (\Exception $e) {
            // Continue anyway - the service will handle it
        }

        $adminData = session('autoinstall_admin', []);

        $result = $this->installer->runInstallation($adminData);

        // Clear session data
        session()->forget(['autoinstall_db', 'autoinstall_app', 'autoinstall_admin']);

        if ($result['success']) {
            session(['autoinstall_result' => $result]);
            return response()->json([
                'success' => true,
                'steps' => $result['steps'],
                'redirect' => route('autoinstall.complete'),
            ]);
        }

        return response()->json([
            'success' => false,
            'steps' => $result['steps'],
            'message' => $result['message'] ?? 'Installation failed',
        ], 500);
    }

    /**
     * Show completion page
     */
    public function complete()
    {
        $manifest = $this->installer->getManifest();
        $redirect = $manifest['post_install']['redirect'] ?? '/login';

        return view('autoinstall.complete', [
            'appName' => $this->installer->getAppName(),
            'redirect' => $redirect,
        ]);
    }

    /**
     * Test database connection via AJAX
     */
    public function testConnection(Request $request)
    {
        $credentials = [
            'host' => $request->db_host,
            'port' => $request->db_port ?? 3306,
            'database' => $request->db_database,
            'username' => $request->db_username,
            'password' => $request->db_password ?? '',
        ];

        $result = $this->installer->testDatabaseConnection($credentials);

        return response()->json($result);
    }
}
