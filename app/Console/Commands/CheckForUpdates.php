<?php

namespace App\Console\Commands;

use App\Models\SystemSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckForUpdates extends Command
{
    protected $signature = 'system:check-updates {--notify : Send notification if update is available}';

    protected $description = 'Check for available system updates from the update server';

    public function handle(): int
    {
        $autoUpdateEnabled = SystemSetting::get('auto_update_enabled', 'false') === 'true';

        if (!$autoUpdateEnabled && !$this->option('notify')) {
            $this->info('Auto-update checking is disabled. Use --notify to force check.');
            return Command::SUCCESS;
        }

        $this->info('Checking for updates...');

        try {
            $currentVersion = SystemSetting::get('app_version', config('installer.version', '1.0.0'));
            $licenseKey = SystemSetting::get('license_key');
            $updateServer = config('installer.update_server');

            $response = Http::timeout(30)->get("{$updateServer}/api/check-update", [
                'version' => $currentVersion,
                'license' => $licenseKey,
            ]);

            SystemSetting::set('last_update_check', now()->toIso8601String());

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['update_available']) && $data['update_available']) {
                    $this->info("Update available: Version {$data['version']}");
                    
                    if (isset($data['changelog'])) {
                        $this->line("Changelog: {$data['changelog']}");
                    }

                    Log::info('System update available', [
                        'current_version' => $currentVersion,
                        'available_version' => $data['version'],
                    ]);

                    return Command::SUCCESS;
                }

                $this->info("You are running the latest version ({$currentVersion}).");
                return Command::SUCCESS;
            }

            $this->error('Could not connect to update server.');
            return Command::FAILURE;

        } catch (\Exception $e) {
            $this->error('Update check failed: ' . $e->getMessage());
            Log::error('Update check failed', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }
    }
}
