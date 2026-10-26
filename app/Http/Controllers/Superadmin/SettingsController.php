<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Mail\TestMail;
use App\Models\CompanySetting;
use App\Models\SystemSetting;
use App\Services\CloudflareCustomHostnames;
use App\Support\TenantDomain;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /* ------------------------------ General ------------------------------ */

    public function general()
    {
        $s = SystemSetting::getMany([
            'site_name' => 'RecurringPress',
            'site_tagline' => null,
            'site_logo' => null,
            'site_favicon' => null,
            'support_email' => null,
            'contact_email' => null,
            'footer_text' => null,
            'hide_site_name' => false,
            'email_verification_enabled' => false,
            'google_analytics_script' => null,
            'livechat_script' => null,
            'seo_description' => null,
            'seo_keywords' => null,
            'seo_og_image' => null,
        ]);

        return Inertia::render('Superadmin/Settings/General', [
            'settings' => [
                ...collect($s)->except(['site_logo', 'site_favicon', 'seo_og_image'])->all(),
                'hide_site_name' => $this->bool($s['hide_site_name']),
                'email_verification_enabled' => $this->bool($s['email_verification_enabled']),
            ],
            'images' => [
                'site_logo' => $s['site_logo'] ? asset('storage/' . $s['site_logo']) : null,
                'site_favicon' => $s['site_favicon'] ? asset('storage/' . $s['site_favicon']) : null,
                'seo_og_image' => $s['seo_og_image'] ? asset('storage/' . $s['seo_og_image']) : null,
            ],
        ]);
    }

    public function updateGeneral(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:255',
            'site_tagline' => 'nullable|string|max:255',
            'support_email' => 'nullable|email|max:255',
            'contact_email' => 'nullable|email|max:255',
            'footer_text' => 'nullable|string|max:500',
            'site_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'site_favicon' => 'nullable|file|mimes:png,ico,jpg,jpeg,webp|max:512',
            'hide_site_name' => 'boolean',
            'email_verification_enabled' => 'boolean',
            // Raw HTML/JS injected on every page by design (analytics / chat widgets).
            'google_analytics_script' => 'nullable|string|max:20000',
            'livechat_script' => 'nullable|string|max:20000',
            'seo_description' => 'nullable|string|max:160',
            'seo_keywords' => 'nullable|string|max:500',
            'seo_og_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $validated['hide_site_name'] = $request->boolean('hide_site_name') ? '1' : '0';
        $validated['email_verification_enabled'] = $request->boolean('email_verification_enabled') ? '1' : '0';

        foreach (['site_logo', 'site_favicon', 'seo_og_image'] as $file) {
            if ($request->hasFile($file)) {
                if ($old = SystemSetting::get($file)) {
                    Storage::disk('public')->delete($old);
                }
                $validated[$file] = $request->file($file)->store('branding', 'public');
            } elseif ($request->boolean("remove_{$file}")) {
                if ($old = SystemSetting::get($file)) {
                    Storage::disk('public')->delete($old);
                }
                $validated[$file] = null;
            } else {
                unset($validated[$file]);
            }
        }

        SystemSetting::setMany($validated);

        return back()->with('success', 'General settings saved.');
    }

    /* ------------------------------ Stripe ------------------------------ */

    public function stripe()
    {
        $s = SystemSetting::getMany([
            'stripe_enabled' => false,
            'stripe_mode' => 'test',
            'stripe_test_publishable_key' => null,
            'stripe_test_secret_key' => null,
            'stripe_live_publishable_key' => null,
            'stripe_live_secret_key' => null,
            'stripe_webhook_secret' => null,
        ]);

        return Inertia::render('Superadmin/Settings/Stripe', [
            'settings' => [
                'stripe_enabled' => $this->bool($s['stripe_enabled']),
                'stripe_mode' => $s['stripe_mode'] ?: 'test',
                'stripe_test_publishable_key' => $s['stripe_test_publishable_key'],
                'stripe_live_publishable_key' => $s['stripe_live_publishable_key'],
            ],
            // Secrets are write-only: the browser only learns whether one is saved.
            'saved' => [
                'stripe_test_secret_key' => filled($s['stripe_test_secret_key']),
                'stripe_live_secret_key' => filled($s['stripe_live_secret_key']),
                'stripe_webhook_secret' => filled($s['stripe_webhook_secret']),
            ],
            'webhookUrl' => route('stripe.webhook'),
        ]);
    }

    public function updateStripe(Request $request)
    {
        $validated = $request->validate([
            'stripe_enabled' => 'boolean',
            'stripe_mode' => 'required|in:test,live',
            'stripe_test_publishable_key' => 'nullable|string|max:255|starts_with:pk_test_',
            'stripe_test_secret_key' => 'nullable|string|max:255|starts_with:sk_test_,rk_test_',
            'stripe_live_publishable_key' => 'nullable|string|max:255|starts_with:pk_live_',
            'stripe_live_secret_key' => 'nullable|string|max:255|starts_with:sk_live_,rk_live_',
            'stripe_webhook_secret' => 'nullable|string|max:255|starts_with:whsec_',
        ]);

        $validated['stripe_enabled'] = $request->boolean('stripe_enabled') ? '1' : '0';

        foreach (['stripe_test_secret_key', 'stripe_live_secret_key', 'stripe_webhook_secret'] as $secret) {
            if (blank($validated[$secret] ?? null)) {
                unset($validated[$secret]); // blank = keep the saved value
            }
        }

        SystemSetting::setMany($validated);

        return back()->with('success', 'Stripe settings saved.');
    }

    /* ------------------------------ SMTP ------------------------------ */

    public function smtp()
    {
        $s = SystemSetting::getMany([
            'mail_mailer' => 'smtp',
            'mail_host' => null,
            'mail_port' => '587',
            'mail_username' => null,
            'mail_password' => null,
            'mail_encryption' => 'tls',
            'mail_from_address' => null,
            'mail_from_name' => config('app.name'),
        ]);

        return Inertia::render('Superadmin/Settings/Smtp', [
            'settings' => [
                ...collect($s)->except('mail_password')->all(),
                'mail_encryption' => $s['mail_encryption'] ?? 'null',
            ],
            'hasPassword' => filled($s['mail_password']),
        ]);
    }

    public function updateSmtp(Request $request)
    {
        $validated = $request->validate([
            'mail_mailer' => 'required|in:smtp,sendmail,log',
            'mail_host' => 'required_if:mail_mailer,smtp|nullable|string|max:255',
            'mail_port' => 'required_if:mail_mailer,smtp|nullable|integer|min:1|max:65535',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_encryption' => 'nullable|string|in:ssl,tls,null',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string|max:255',
        ]);

        if (($validated['mail_encryption'] ?? null) === 'null') {
            $validated['mail_encryption'] = null;
        }
        if (blank($validated['mail_password'] ?? null)) {
            unset($validated['mail_password']); // write-only
        }

        SystemSetting::setMany($validated);

        return back()->with('success', 'Email settings saved. Restart Horizon/queue workers to apply them to queued mail.');
    }

    public function sendTestEmail(Request $request)
    {
        $request->validate(['test_email' => 'required|email']);

        try {
            $s = SystemSetting::getMany(['mail_mailer', 'mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_encryption', 'mail_from_address', 'mail_from_name']);

            if (! empty($s['mail_host'])) {
                config([
                    'mail.default' => $s['mail_mailer'] ?? 'smtp',
                    'mail.mailers.smtp.host' => $s['mail_host'],
                    'mail.mailers.smtp.port' => $s['mail_port'],
                    'mail.mailers.smtp.username' => $s['mail_username'],
                    'mail.mailers.smtp.password' => $s['mail_password'],
                    'mail.mailers.smtp.encryption' => $s['mail_encryption'],
                    'mail.from.address' => $s['mail_from_address'],
                    'mail.from.name' => $s['mail_from_name'],
                ]);
            }

            Mail::purge('smtp');
            Mail::to($request->test_email)->send(new TestMail());

            return back()->with('success', 'Test email sent to ' . $request->test_email . '.');
        } catch (\Throwable $e) {
            Log::warning('SMTP test failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Sending failed: ' . $e->getMessage());
        }
    }

    /* ------------------- Custom domains (Cloudflare for SaaS) ------------------- */

    public function customDomains()
    {
        return Inertia::render('Superadmin/Settings/CustomDomains', [
            'settings' => [
                'custom_domains_enabled' => $this->bool(SystemSetting::get('custom_domains_enabled')),
                'tenant_subdomains_enabled' => $this->bool(SystemSetting::get('tenant_subdomains_enabled')),
                'cloudflare_zone_id' => SystemSetting::get('cloudflare_zone_id'),
                'cloudflare_cname_target' => SystemSetting::get('cloudflare_cname_target'),
            ],
            // The token is write-only: only whether one is saved (and still decryptable).
            'hasToken' => filled(CloudflareCustomHostnames::token()),
            'configured' => CloudflareCustomHostnames::configured(),
            'subdomainBase' => TenantDomain::subdomainBase(),
            'stats' => [
                'domains' => CompanySetting::whereNotNull('custom_domain')->count(),
                'verified' => CompanySetting::whereNotNull('custom_domain_verified_at')->count(),
                'subdomains' => CompanySetting::whereNotNull('subdomain')->count(),
            ],
        ]);
    }

    public function updateCustomDomains(Request $request)
    {
        $validated = $request->validate([
            'custom_domains_enabled' => 'boolean',
            'tenant_subdomains_enabled' => 'boolean',
            'cloudflare_zone_id' => ['nullable', 'string', 'regex:/^[a-f0-9]{32}$/i'],
            'cloudflare_api_token' => 'nullable|string|max:255',
            'cloudflare_cname_target' => ['nullable', 'string', 'max:253'],
        ], [
            'cloudflare_zone_id.regex' => 'The zone ID is the 32-character hex ID shown on the zone’s Overview page.',
        ]);

        $target = filled($validated['cloudflare_cname_target'] ?? null) ? TenantDomain::normalize($validated['cloudflare_cname_target']) : null;

        if (filled($validated['cloudflare_cname_target'] ?? null) && ! $target) {
            throw ValidationException::withMessages(['cloudflare_cname_target' => 'Enter a hostname such as customers.recurringpress.com.']);
        }

        if ($request->boolean('custom_domains_enabled') && (blank($validated['cloudflare_zone_id'] ?? null) || blank($target)
            || (blank($validated['cloudflare_api_token'] ?? null) && ! CloudflareCustomHostnames::token()))) {
            throw ValidationException::withMessages(['custom_domains_enabled' => 'Enter the zone ID, API token and CNAME target before switching custom domains on.']);
        }

        SystemSetting::setMany([
            'custom_domains_enabled' => $request->boolean('custom_domains_enabled') ? '1' : '0',
            'tenant_subdomains_enabled' => $request->boolean('tenant_subdomains_enabled') ? '1' : '0',
            'cloudflare_zone_id' => strtolower((string) ($validated['cloudflare_zone_id'] ?? '')) ?: null,
            'cloudflare_cname_target' => $target,
        ]);

        if (filled($validated['cloudflare_api_token'] ?? null)) {
            CloudflareCustomHostnames::storeToken(trim($validated['cloudflare_api_token'])); // write-only
        }

        return back()->with('success', 'Custom domain settings saved.');
    }

    public function testCloudflare(CloudflareCustomHostnames $cloudflare)
    {
        try {
            $result = $cloudflare->testConnection();
        } catch (\Throwable $e) {
            Log::warning('Cloudflare connection test failed', ['error' => $e->getMessage()]);

            return back()->with('error', $e->getMessage());
        }

        $message = 'Connected to zone ' . ($result['zone'] ?? '?') . '.';

        if (! $result['fallback_origin']) {
            return back()->with('warning', $message . ' No fallback origin is set yet: set it under SSL/TLS → Custom Hostnames to your CNAME target.');
        }

        if ($result['fallback_origin'] !== CloudflareCustomHostnames::cnameTarget()) {
            return back()->with('warning', $message . " The fallback origin is {$result['fallback_origin']}, but the CNAME target here is " . CloudflareCustomHostnames::cnameTarget() . '. They should match.');
        }

        return back()->with('success', $message . " Fallback origin {$result['fallback_origin']} is {$result['fallback_status']}.");
    }

    /* ------------------------------ Payouts ------------------------------ */

    public function payouts()
    {
        $s = SystemSetting::getMany(['payout_fee_percentage' => 0, 'payout_minimum_amount' => 50]);

        return Inertia::render('Superadmin/Settings/Payouts', ['settings' => [
            'payout_fee_percentage' => (float) $s['payout_fee_percentage'],
            'payout_minimum_amount' => (float) $s['payout_minimum_amount'],
        ]]);
    }

    public function updatePayouts(Request $request)
    {
        SystemSetting::setMany($request->validate([
            'payout_fee_percentage' => 'required|numeric|min:0|max:100',
            'payout_minimum_amount' => 'required|numeric|min:0|max:1000000',
        ]));

        return back()->with('success', 'Payout settings saved.');
    }

    /* ------------------------------ Landing ------------------------------ */

    public function landing()
    {
        $defaults = config('marketing.landing_defaults', []);
        $keys = [
            'landing_hero_title', 'landing_hero_subtitle', 'landing_cta_text', 'landing_cta_url', 'landing_trust_text',
            'landing_features_title', 'landing_features_subtitle', 'features_page_title', 'features_page_subtitle',
            'features_page_cta_title', 'features_page_cta_subtitle', 'landing_pricing_title', 'landing_pricing_subtitle',
            'landing_testimonials_title', 'landing_testimonials_subtitle', 'landing_footer_text',
        ];
        $lists = ['landing_trust_badges_json', 'landing_features_json', 'features_page_json', 'landing_testimonials_json'];

        $settings = [];
        foreach ($keys as $k) {
            $settings[$k] = SystemSetting::get($k, $defaults[$k] ?? '');
        }
        foreach ($lists as $k) {
            $decoded = json_decode((string) SystemSetting::get($k), true);
            $settings[$k] = is_array($decoded) ? array_values($decoded) : ($defaults[$k] ?? []);
        }

        return Inertia::render('Superadmin/Settings/Landing', ['settings' => $settings]);
    }

    public function updateLanding(Request $request)
    {
        $validated = $request->validate([
            'landing_hero_title' => 'required|string|max:255',
            'landing_hero_subtitle' => 'required|string|max:1000',
            'landing_cta_text' => 'required|string|max:50',
            'landing_cta_url' => ['required', 'string', 'max:255', 'regex:#^(/(?!/)|\#|https?://)#i'],
            'landing_trust_text' => 'nullable|string|max:255',
            'landing_features_title' => 'required|string|max:255',
            'landing_features_subtitle' => 'required|string|max:255',
            'features_page_title' => 'nullable|string|max:255',
            'features_page_subtitle' => 'nullable|string|max:500',
            'features_page_cta_title' => 'nullable|string|max:255',
            'features_page_cta_subtitle' => 'nullable|string|max:500',
            'landing_pricing_title' => 'required|string|max:255',
            'landing_pricing_subtitle' => 'required|string|max:255',
            'landing_testimonials_title' => 'required|string|max:255',
            'landing_testimonials_subtitle' => 'required|string|max:255',
            'landing_footer_text' => 'nullable|string|max:500',

            'landing_trust_badges_json' => 'nullable|array|max:20',
            'landing_trust_badges_json.*.name' => 'required|string|max:60',
            'landing_features_json' => 'nullable|array|max:24',
            'landing_features_json.*.title' => 'required|string|max:120',
            'landing_features_json.*.description' => 'nullable|string|max:500',
            'landing_features_json.*.icon' => 'nullable|string|max:60',
            'landing_features_json.*.color' => 'nullable|string|max:20',
            'features_page_json' => 'nullable|array|max:24',
            'features_page_json.*.title' => 'required|string|max:120',
            'features_page_json.*.description' => 'nullable|string|max:500',
            'features_page_json.*.icon' => 'nullable|string|max:60',
            'features_page_json.*.color' => 'nullable|string|max:20',
            'features_page_json.*.bullets' => 'nullable|array|max:10',
            'features_page_json.*.bullets.*' => 'nullable|string|max:120',
            'landing_testimonials_json' => 'nullable|array|max:12',
            'landing_testimonials_json.*.name' => 'required|string|max:80',
            'landing_testimonials_json.*.role' => 'nullable|string|max:120',
            'landing_testimonials_json.*.initials' => 'nullable|string|max:4',
            'landing_testimonials_json.*.color' => 'nullable|string|max:20',
            'landing_testimonials_json.*.quote' => 'required|string|max:600',
        ], [
            'landing_cta_url.regex' => 'Use a relative link (/register) or an http(s) URL.',
        ]);

        foreach (['landing_trust_badges_json', 'landing_features_json', 'features_page_json', 'landing_testimonials_json'] as $list) {
            $validated[$list] = json_encode(array_values($validated[$list] ?? []));
        }

        SystemSetting::setMany($validated);

        return back()->with('success', 'Landing page saved.');
    }

    private function bool($v): bool
    {
        return in_array($v, [true, 1, '1', 'true', 'on'], true);
    }
}
