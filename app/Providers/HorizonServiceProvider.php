<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeMailNotificationsTo('ops@example.com');
    }

    /**
     * Only logged-in superadmins may open the Horizon dashboard (/horizon), in every
     * environment. Tenant users (the "web" guard) are never allowed — the dashboard
     * exposes job payloads from all companies.
     */
    protected function authorization(): void
    {
        Horizon::auth(function ($request) {
            return Auth::guard('superadmin')->check();
        });
    }
}
