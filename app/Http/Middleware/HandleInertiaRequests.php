<?php

namespace App\Http\Middleware;

use App\Models\Menu;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Props shared with every page.
     *
     * Everything returned here is serialized into the page as JSON, so only expose
     * explicitly chosen fields — never whole models (see CompanySetting::$hidden).
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            'site' => fn () => [
                'name' => SystemSetting::get('site_name', 'RecurringPress'),
                'logo' => ($logo = SystemSetting::get('site_logo')) ? asset('storage/' . $logo) : null,
                'favicon' => ($icon = SystemSetting::get('site_favicon')) ? asset('storage/' . $icon) : null,
            ],

            'auth' => fn () => $this->authPayload(),

            // Header/footer menus + SEO defaults, only computed on public marketing pages.
            'marketing' => fn () => $request->routeIs('landing', 'landing.*', 'blog.*', 'page.show', 'cms.*')
                ? [
                    'hide_site_name' => (bool) SystemSetting::get('hide_site_name'),
                    'footer_text' => SystemSetting::get('landing_footer_text', 'Professional invoicing software for freelancers and small businesses.'),
                    'seo' => [
                        'description' => SystemSetting::get('seo_description', 'Professional invoicing software for freelancers and small businesses.'),
                        'keywords' => SystemSetting::get('seo_keywords', 'invoicing, billing, invoice software, freelancer invoicing'),
                        'og_image' => ($og = SystemSetting::get('seo_og_image')) ? asset('storage/' . $og) : null,
                        'url' => $request->url(),
                    ],
                    'menus' => [
                        'header' => Menu::getItems('header'),
                        'footer_product' => Menu::getItems('footer_product'),
                        'footer_company' => Menu::getItems('footer_company'),
                        'footer_legal' => Menu::getItems('footer_legal'),
                    ],
                ]
                : null,

            'superadmin' => fn () => ($admin = Auth::guard('superadmin')->user()) ? [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'avatar_url' => $admin->avatar_url,
            ] : null,

            'flash' => fn () => array_filter([
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'info' => $request->session()->get('info'),
                'status' => $request->session()->get('status'),
            ]),

            // Bot-trap fields for public forms (see ProtectAgainstSpam / <Honeypot />).
            'honeypot' => fn () => Auth::check() ? null : [
                'field' => ProtectAgainstSpam::HONEYPOT_FIELD,
                'timestampField' => ProtectAgainstSpam::TIMESTAMP_FIELD,
                'timestamp' => Crypt::encryptString((string) time()),
            ],

            // Route list for the JS route() helper. Sent once per page load and cached by
            // the client. Superadmin routes are only sent inside the superadmin area.
            'ziggy' => Inertia::once(fn () => [
                ...(new Ziggy($request->is('superadmin*') || $request->is('horizon*') ? 'superadmin' : 'app'))->toArray(),
                'location' => $request->url(),
            ]),
        ];
    }

    protected function authPayload(): ?array
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        $company = $user->company;
        $settings = $company?->settings;

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'avatar_url' => $user->avatar_url,
                'is_admin' => $user->isAdmin(),
                'is_owner' => $user->isOwner(),
                'email_verified' => $user->hasVerifiedEmail(),
            ],
            'company' => $company ? [
                'id' => $company->id,
                'name' => $company->name,
                'logo_url' => $company->logo_url,
                'currency' => $company->currency,
                'currency_symbol' => $company->currency_symbol,
                'on_trial' => $company->isOnTrial(),
                'subscription_status' => $company->subscription_status,
                'subscription_ends_at' => $company->subscription_ends_at?->toDateString(),
                'plan' => $company->subscriptionPlan?->name,
                'show_payouts' => $settings && $settings->payment_gateway_mode !== 'own',
                'date_format' => $settings->date_format ?? 'M d, Y',
            ] : null,
            'theme' => [
                'primary' => $settings->primary_color ?? '#4f46e5',
                'secondary' => $settings->secondary_color ?? '#6366f1',
                'sidebar' => $settings->sidebar_color ?? '#ffffff',
                'sidebar_text' => $settings->sidebar_text_color ?? '#1f2937',
                'font' => $settings->font_family ?? 'Inter',
                'font_size' => $settings->font_size ?? 'base',
            ],
        ];
    }
}
