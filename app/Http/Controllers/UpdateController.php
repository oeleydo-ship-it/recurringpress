<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class UpdateController extends Controller
{
    /**
     * Guard against zip-slip: reject archives containing entries that would
     * escape the intended extraction directory via ../ traversal or absolute paths.
     */
    protected function assertZipIsSafe(ZipArchive $zip, string $destination): void
    {
        $destination = rtrim(str_replace('\\', '/', realpath($destination) ?: $destination), '/');

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = str_replace('\\', '/', $zip->getNameIndex($i));

            if ($name === '' || str_contains($name, '../') || str_starts_with($name, '/') || preg_match('#^[A-Za-z]:#', $name)) {
                throw new \RuntimeException('Unsafe archive entry detected: ' . $name);
            }

            $resolved = $destination . '/' . $name;
            if (!str_starts_with(str_replace('\\', '/', $resolved), $destination . '/')) {
                throw new \RuntimeException('Unsafe archive entry detected: ' . $name);
            }
        }
    }

    public function index()
    {
        $currentVersion = SystemSetting::get('app_version', config('installer.version', '1.0.0'));
        $autoUpdateEnabled = SystemSetting::get('auto_update_enabled', 'false') === 'true';
        $updateMethod = SystemSetting::get('update_method', 'manual');
        $lastUpdateCheck = SystemSetting::get('last_update_check');
        $licenseKey = SystemSetting::get('license_key');
        $updateServerUrl = SystemSetting::get('update_server_url', config('installer.update_server'));

        return \Inertia\Inertia::render('Superadmin/Updates/Index', [
            'currentVersion' => $currentVersion,
            'autoUpdateEnabled' => $autoUpdateEnabled,
            'updateMethod' => $updateMethod,
            'lastUpdateCheck' => $lastUpdateCheck,
            // Only a masked hint of the licence key is sent to the browser.
            'licenseHint' => $licenseKey ? '••••' . substr($licenseKey, -4) : null,
            'updateServerUrl' => $updateServerUrl,
            'urls' => [
                'check' => route('superadmin.updates.check'),
                'download' => route('superadmin.updates.download'),
                'backups' => route('superadmin.updates.backups'),
            ],
        ]);
    }

    public function settings(Request $request)
    {
        $request->validate([
            'auto_update_enabled' => 'boolean',
            'update_method' => 'in:manual,server,upload',
            'license_key' => 'nullable|string|max:255',
            'update_server_url' => 'nullable|url:https,http|max:255',
        ]);

        SystemSetting::set('auto_update_enabled', $request->boolean('auto_update_enabled') ? 'true' : 'false');
        SystemSetting::set('update_method', $request->input('update_method', 'manual'));

        if ($request->filled('license_key')) {
            SystemSetting::set('license_key', $request->license_key);
        }

        if ($request->filled('update_server_url')) {
            SystemSetting::set('update_server_url', rtrim($request->update_server_url, '/'));
        }

        return back()->with('success', 'Update settings saved successfully.');
    }

    public function checkForUpdates()
    {
        try {
            $currentVersion = SystemSetting::get('app_version', config('installer.version', '1.0.0'));
            $licenseKey = SystemSetting::get('license_key');
            $updateServer = SystemSetting::get('update_server_url') ?: config('installer.update_server');

            // Check if update server is configured
            if (empty($updateServer) || str_contains($updateServer, 'example.com')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Update server URL is not configured. Please configure it in the settings or .env file (UPDATE_SERVER_URL).',
                ], 400);
            }

            $response = Http::timeout(30)->get("{$updateServer}/api/check-update", [
                'version' => $currentVersion,
                'license' => $licenseKey,
                'domain' => request()->getHost(),
            ]);

            SystemSetting::set('last_update_check', now()->toIso8601String());

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['update_available']) && $data['update_available']) {
                    return response()->json([
                        'success' => true,
                        'update_available' => true,
                        'latest_version' => $data['version'] ?? null,
                        'changelog' => $data['changelog'] ?? null,
                        'download_url' => $data['download_url'] ?? null,
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'update_available' => false,
                    'message' => 'You are running the latest version.',
                ]);
            }

            Log::warning('Update check failed: HTTP ' . $response->status(), [
                'response' => $response->body(),
                'server' => $updateServer,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not connect to update server. Status: ' . $response->status(),
            ], 500);

        } catch (\Exception $e) {
            Log::error('Update check failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Update check failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function downloadAndInstall(Request $request)
    {
        try {
            $request->validate([
                'download_url' => 'required|url:https,http',
                'version' => 'required|string|max:50',
            ]);

            // Only install packages served by the configured update server (the URL comes
            // from the browser; previously any URL was fetched and installed).
            $serverHost = parse_url((string) (SystemSetting::get('update_server_url') ?: config('installer.update_server')), PHP_URL_HOST);
            if (! $serverHost || strcasecmp((string) parse_url($request->download_url, PHP_URL_HOST), $serverHost) !== 0) {
                return response()->json(['success' => false, 'message' => 'The download URL must be on the configured update server.'], 422);
            }

            $this->createBackup();

            $tempFile = storage_path('app/updates/update_' . time() . '.zip');

            if (!File::isDirectory(storage_path('app/updates'))) {
                File::makeDirectory(storage_path('app/updates'), 0755, true);
            }

            $response = Http::timeout(300)->get($request->download_url);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to download update package.',
                ], 500);
            }

            File::put($tempFile, $response->body());

            $result = $this->installUpdate($tempFile, $request->version);

            File::delete($tempFile);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Update installed successfully.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 500);

        } catch (\Exception $e) {
            Log::error('Update installation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function uploadUpdate(Request $request)
    {
        try {
            $request->validate([
                'update_file' => 'required|file|mimes:zip|max:102400',
                'version' => 'required|string',
            ]);

            Log::info('Starting update upload', ['version' => $request->version]);

            // Ensure updates directory exists
            $updatesDir = storage_path('app/updates');
            if (!File::isDirectory($updatesDir)) {
                File::makeDirectory($updatesDir, 0755, true);
            }

            $this->createBackup();

            $file = $request->file('update_file');
            $tempFile = $file->storeAs('updates', 'update_' . time() . '.zip');
            $fullPath = storage_path('app/' . $tempFile);

            Log::info('Update file saved', ['path' => $fullPath]);

            $result = $this->installUpdate($fullPath, $request->version);

            // Clean up temp file
            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }

            if ($result['success']) {
                Log::info('Update installed successfully', ['version' => $request->version]);
                return back()->with('success', 'Update installed successfully. Version: ' . $request->version);
            }

            Log::error('Update installation failed', ['message' => $result['message']]);
            return back()->with('error', 'Update failed: ' . $result['message']);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Update upload failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    protected function createBackup(): void
    {
        try {
            $backupDir = storage_path('app/backups');

            if (!File::isDirectory($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }

            $backupFile = $backupDir . '/backup_' . date('Y-m-d_H-i-s') . '.zip';

            $zip = new ZipArchive();

            if ($zip->open($backupFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                $filesToBackup = [
                    'app',
                    'config',
                    'database/migrations',
                    'resources/views',
                    'routes',
                ];

                foreach ($filesToBackup as $path) {
                    $fullPath = base_path($path);

                    if (File::isDirectory($fullPath)) {
                        $this->addDirectoryToZip($zip, $fullPath, $path);
                    } elseif (File::exists($fullPath)) {
                        $zip->addFile($fullPath, $path);
                    }
                }

                // Database Backup
                $sqlFile = storage_path('app/backups/database_' . time() . '.sql');
                $this->dumpDatabase($sqlFile);

                if (File::exists($sqlFile)) {
                    $zip->addFile($sqlFile, 'database.sql');
                }

                $zip->close();

                // Clean up SQL file after zipping
                if (File::exists($sqlFile)) {
                    File::delete($sqlFile);
                }

                if (File::exists($backupFile)) {
                    Log::info('Backup created: ' . $backupFile);
                }
            } else {
                Log::error('Failed to create backup zip file: ' . $backupFile);
            }
        } catch (\Exception $e) {
            Log::error('Backup creation failed: ' . $e->getMessage());
            // Don't throw - continue with update even if backup fails
        }
    }

    protected function dumpDatabase(string $path): void
    {
        $host = config('database.connections.mysql.host');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $database = config('database.connections.mysql.database');

        $tables = DB::select('SHOW TABLES');
        $tableProperty = "Tables_in_" . $database;

        $content = "-- Database Dump\n";
        $content .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
        $content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $tableName = $table->$tableProperty;

            // Structure
            $createTable = DB::select("SHOW CREATE TABLE `$tableName`");
            $content .= "DROP TABLE IF EXISTS `$tableName`;\n";
            $content .= $createTable[0]->{'Create Table'} . ";\n\n";

            // Data
            $rows = DB::table($tableName)->get();

            if ($rows->count() > 0) {
                // Determine column types to handle quoting correctly if needed, 
                // though usually strict quoting is safer.
                // For simplicity and to avoid memory issues with large tables, 
                // we should chunk, but for this implementation we'll do simple inserts.
                // A better approach for data is to generate INSERT statements.

                foreach ($rows->chunk(100) as $chunk) {
                    $content .= "INSERT INTO `$tableName` VALUES ";
                    $values = [];

                    foreach ($chunk as $row) {
                        $rowValues = [];
                        foreach ((array) $row as $value) {
                            if (is_null($value)) {
                                $rowValues[] = "NULL";
                            } elseif (is_numeric($value)) {
                                $rowValues[] = $value;
                            } else {
                                $rowValues[] = "'" . addslashes($value) . "'";
                            }
                        }
                        $values[] = "(" . implode(', ', $rowValues) . ")";
                    }

                    $content .= implode(", ", $values) . ";\n";
                }
                $content .= "\n";
            }
        }

        $content .= "SET FOREIGN_KEY_CHECKS=1;\n";

        File::put($path, $content);
    }

    protected function addDirectoryToZip(ZipArchive $zip, string $path, string $relativePath): void
    {
        $files = File::allFiles($path);

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relPath = $relativePath . '/' . $file->getRelativePathname();
            $zip->addFile($filePath, $relPath);
        }
    }

    protected function installUpdate(string $zipPath, string $version): array
    {
        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            return ['success' => false, 'message' => 'Could not open update package.'];
        }

        $extractPath = storage_path('app/updates/extracted_' . time());

        if (!File::isDirectory($extractPath)) {
            File::makeDirectory($extractPath, 0755, true);
        }

        try {
            $this->assertZipIsSafe($zip, $extractPath);
        } catch (\RuntimeException $e) {
            $zip->close();
            return ['success' => false, 'message' => 'Update package failed safety check: ' . $e->getMessage()];
        }

        $zip->extractTo($extractPath);
        $zip->close();

        // Check if extracted content is inside a single root directory
        $files = File::directories($extractPath);
        $rootPath = $extractPath;

        if (count($files) === 1 && count(File::files($extractPath)) === 0) {
            $rootPath = $files[0];
        }

        $updateDirs = ['app', 'config', 'database', 'resources', 'routes', 'public'];
        $itemsCopied = 0;

        foreach ($updateDirs as $dir) {
            $sourcePath = $rootPath . '/' . $dir;
            $destPath = base_path($dir);

            if (File::isDirectory($sourcePath)) {
                $this->copyDirectory($sourcePath, $destPath);
                $itemsCopied++;
            }
        }

        // Also check for composer.json/lock and other root files
        $rootFiles = ['composer.json', 'composer.lock', 'package.json', 'vite.config.js'];
        foreach ($rootFiles as $file) {
            $sourceFile = $rootPath . '/' . $file;
            if (File::exists($sourceFile)) {
                File::copy($sourceFile, base_path($file));
                $itemsCopied++;
            }
        }

        File::deleteDirectory($extractPath);

        if ($itemsCopied === 0) {
            return ['success' => false, 'message' => 'No update files were found in the package. Check zip structure.'];
        }

        // Run migrations and clear caches
        try {
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('optimize:clear'); // Clears config, cache, route, view in one go
        } catch (\Exception $e) {
            Log::error('Update migration/cache clear failed: ' . $e->getMessage());
        }

        // Reset Opcache if available (Critical for production updates)
        if (function_exists('opcache_reset')) {
            try {
                opcache_reset();
            } catch (\Exception $e) {
                Log::warning('Could not reset Opcache: ' . $e->getMessage());
            }
        }

        SystemSetting::set('app_version', $version);

        $installedFile = storage_path('installed');
        if (File::exists($installedFile)) {
            $data = json_decode(File::get($installedFile), true) ?? [];
            $data['version'] = $version;
            $data['updated_at'] = now()->toIso8601String();
            File::put($installedFile, json_encode($data));
        }

        Log::info('Update installed successfully. Version: ' . $version);

        return ['success' => true, 'message' => 'Update installed successfully.'];
    }

    protected function copyDirectory(string $source, string $destination): void
    {
        if (!File::isDirectory($destination)) {
            File::makeDirectory($destination, 0755, true);
        }

        $files = File::allFiles($source);

        foreach ($files as $file) {
            $destFile = $destination . '/' . $file->getRelativePathname();
            $destDir = dirname($destFile);

            if (!File::isDirectory($destDir)) {
                File::makeDirectory($destDir, 0755, true);
            }

            File::copy($file->getRealPath(), $destFile);
        }

        $directories = File::directories($source);

        foreach ($directories as $dir) {
            $dirName = basename($dir);
            $this->copyDirectory($dir, $destination . '/' . $dirName);
        }
    }

    public function restoreBackup(Request $request)
    {
        try {
            $request->validate([
                'backup_file' => 'required|string',
            ]);

            $backupFile = storage_path('app/backups/' . basename($request->backup_file));

            if (!File::exists($backupFile)) {
                return back()->with('error', 'Backup file not found.');
            }

            $zip = new ZipArchive();

            if ($zip->open($backupFile) !== true) {
                return back()->with('error', 'Could not open backup file.');
            }

            try {
                $this->assertZipIsSafe($zip, base_path());
            } catch (\RuntimeException $e) {
                $zip->close();
                return back()->with('error', 'Backup archive failed safety check: ' . $e->getMessage());
            }

            $zip->extractTo(base_path());
            $zip->close();

            // Restore Database if exists
            $sqlFile = base_path('database.sql');
            if (File::exists($sqlFile)) {
                DB::unprepared(File::get($sqlFile));
                File::delete($sqlFile); // Clean up extracted SQL file
            }

            Artisan::call('config:clear');
            Artisan::call('cache:clear');

            return back()->with('success', 'Backup restored successfully.');

        } catch (\Exception $e) {
            Log::error('Backup restore failed: ' . $e->getMessage());
            return back()->with('error', 'Restore failed: ' . $e->getMessage());
        }
    }

    public function listBackups()
    {
        $backupDir = storage_path('app/backups');
        $backups = [];

        if (File::isDirectory($backupDir)) {
            $files = File::files($backupDir);

            foreach ($files as $file) {
                if ($file->getExtension() === 'zip') {
                    $backups[] = [
                        'name' => $file->getFilename(),
                        'size' => $this->formatBytes($file->getSize()),
                        'date' => date('Y-m-d H:i:s', $file->getMTime()),
                    ];
                }
            }
        }

        return response()->json(['backups' => array_reverse($backups)]);
    }

    public function deleteBackup(Request $request)
    {
        $request->validate(['backup_file' => 'required|string']);

        $backupFile = storage_path('app/backups/' . basename($request->backup_file));

        if (File::exists($backupFile)) {
            File::delete($backupFile);
            return back()->with('success', 'Backup deleted.');
        }

        return back()->with('error', 'Backup file not found.');
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
