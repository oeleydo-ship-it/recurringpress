<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Mail\TestMail;
use App\Models\AiUsageLog;
use App\Models\CompanySetting;
use App\Services\Ai\AiClient;
use App\Services\Ai\AiProviders;
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

    /* ------------------------------ AI providers ------------------------------ */

    public function ai()
    {
        $logs = AiUsageLog::where('created_at', '>=', now()->startOfMonth());

        $providers = collect(AiProviders::PROVIDERS)->map(fn ($p, $name) => [
            'name' => $name,
            'label' => $p['label'],
            'console' => $p['console'],
            'note' => $p['note'] ?? null,
            'enabled' => AiProviders::isOn($name),
            // Keys are write-only: only whether one is saved (and still decryptable).
            'has_key' => filled(AiProviders::key($name)),
            'model' => AiProviders::model($name),
            // Common model ids, offered in the picker before "Load" fetches the key's own list.
            'suggested_models' => $p['models'] ?? [],
            'known_prices' => $name === 'anthropic',
            'price_in' => SystemSetting::get("ai_{$name}_price_in"),
            'price_out' => SystemSetting::get("ai_{$name}_price_out"),
            'usable' => AiProviders::usable($name),
        ])->values();

        return Inertia::render('Superadmin/Settings/Ai', [
            'settings' => [
                'ai_enabled' => $this->bool(SystemSetting::get('ai_enabled')),
                'ai_monthly_quota' => AiClient::monthlyQuota(),
                'ai_default_provider' => AiProviders::defaultProvider(),
                'ai_failover' => AiProviders::failover(),
                'ai_provider_order' => AiProviders::order(),
                'ai_kimi_region' => AiProviders::kimiRegion(),
            ],
            'providers' => $providers,
            'usage' => [
                'requests' => (clone $logs)->count(),
                'errors' => (clone $logs)->where('status', 'error')->count(),
                'input_tokens' => (int) (clone $logs)->sum('input_tokens'),
                'output_tokens' => (int) (clone $logs)->sum('output_tokens'),
                'cost_usd' => round((float) (clone $logs)->sum('cost_usd'), 2),
                'by_provider' => (clone $logs)->selectRaw("provider, COUNT(*) requests, SUM(status = 'error') errors, SUM(cost_usd) cost")->groupBy('provider')->get()
                    ->map(fn ($r) => ['provider' => $r->provider, 'requests' => (int) $r->requests, 'errors' => (int) $r->errors, 'cost_usd' => round((float) $r->cost, 2)]),
                'by_feature' => (clone $logs)->selectRaw('feature, COUNT(*) requests, SUM(cost_usd) cost')->groupBy('feature')->get()
                    ->map(fn ($r) => ['feature' => $r->feature, 'requests' => (int) $r->requests, 'cost_usd' => round((float) $r->cost, 2)]),
                'top_companies' => (clone $logs)->whereNotNull('company_id')->with('company:id,name')
                    ->selectRaw('company_id, COUNT(*) requests, SUM(cost_usd) cost')->groupBy('company_id')->orderByDesc('requests')->limit(10)->get()
                    ->map(fn ($r) => ['company' => $r->company?->name ?? '#' . $r->company_id, 'requests' => (int) $r->requests, 'cost_usd' => round((float) $r->cost, 2)]),
                'recent_errors' => AiUsageLog::where('status', '!=', 'ok')->latest('created_at')->limit(5)->get(['feature', 'provider', 'status', 'error', 'created_at'])
                    ->map(fn ($r) => ['feature' => $r->feature, 'provider' => $r->provider, 'status' => $r->status, 'error' => $r->error, 'at' => $r->created_at?->toIso8601String()]),
            ],
        ]);
    }

    public function updateAi(Request $request)
    {
        $names = array_keys(AiProviders::PROVIDERS);

        $validated = $request->validate([
            'ai_enabled' => 'boolean',
            'ai_monthly_quota' => 'required|integer|min:0|max:100000',
            'ai_default_provider' => ['required', \Illuminate\Validation\Rule::in($names)],
            'ai_failover' => 'boolean',
            'ai_provider_order' => 'required|array',
            'ai_provider_order.*' => [\Illuminate\Validation\Rule::in($names)],
            'ai_kimi_region' => 'required|in:intl,cn',
            'providers' => 'required|array',
            'providers.*.enabled' => 'boolean',
            'providers.*.key' => 'nullable|string|max:255',
            'providers.*.model' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._:\/-]+$/'],
            'providers.*.price_in' => 'nullable|numeric|min:0|max:1000',
            'providers.*.price_out' => 'nullable|numeric|min:0|max:1000',
        ], ['providers.*.model.regex' => 'Model ids contain only letters, numbers and . _ : / -']);

        $input = collect($validated['providers'])->only($names);

        if (filled($input['anthropic']['key'] ?? null) && ! str_starts_with(trim($input['anthropic']['key']), 'sk-ant-')) {
            throw ValidationException::withMessages(['providers.anthropic.key' => 'Anthropic API keys start with sk-ant-.']);
        }

        // The default provider must be usable once AI is on.
        $default = $validated['ai_default_provider'];
        $d = $input[$default] ?? [];
        if ($request->boolean('ai_enabled') && (! ($d['enabled'] ?? false) || blank($d['model'] ?? null) || (blank($d['key'] ?? null) && ! AiProviders::key($default)))) {
            throw ValidationException::withMessages(['ai_default_provider' => 'Switch on the default provider and give it an API key and a model.']);
        }

        SystemSetting::setMany([
            'ai_enabled' => $request->boolean('ai_enabled') ? '1' : '0',
            'ai_monthly_quota' => (string) $validated['ai_monthly_quota'],
            'ai_default_provider' => $default,
            'ai_failover' => $request->boolean('ai_failover') ? '1' : '0',
            'ai_provider_order' => implode(',', array_values(array_unique($validated['ai_provider_order']))),
            'ai_kimi_region' => $validated['ai_kimi_region'],
        ]);

        foreach ($input as $name => $p) {
            SystemSetting::setMany([
                "ai_{$name}_enabled" => ! empty($p['enabled']) ? '1' : '0',
                "ai_{$name}_model" => filled($p['model'] ?? null) ? trim($p['model']) : null,
                "ai_{$name}_price_in" => isset($p['price_in']) && $p['price_in'] !== '' ? (string) $p['price_in'] : null,
                "ai_{$name}_price_out" => isset($p['price_out']) && $p['price_out'] !== '' ? (string) $p['price_out'] : null,
            ]);

            if (filled($p['key'] ?? null)) {
                AiProviders::storeKey($name, trim($p['key'])); // write-only
            }
        }

        return back()->with('success', 'AI settings saved.');
    }

    public function testAi(Request $request, AiClient $ai)
    {
        $name = (string) $request->input('provider');
        abort_unless(AiProviders::exists($name), 404);

        if (! AiProviders::key($name) || ! AiProviders::model($name)) {
            return back()->with('error', 'Save an API key and a model for ' . AiProviders::PROVIDERS[$name]['label'] . ' first.');
        }

        try {
            $model = $ai->ping($name);
        } catch (\Throwable $e) {
            Log::warning('AI connection test failed', ['provider' => $name, 'error' => $e->getMessage()]);

            return back()->with('error', AiProviders::PROVIDERS[$name]['label'] . ' test failed: ' . mb_substr($e->getMessage(), 0, 250));
        }

        return back()->with('success', AiProviders::PROVIDERS[$name]['label'] . " connected. {$model} answered.");
    }

    /** Model ids the saved key can use, for the model picker (JSON). */
    public function aiModels(Request $request)
    {
        $name = (string) $request->input('provider');
        abort_unless(AiProviders::exists($name), 404);

        if (! AiProviders::key($name)) {
            return response()->json(['message' => 'Save an API key first.'], 422);
        }

        try {
            return response()->json(['models' => AiProviders::make($name)->models()]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Could not load models: ' . mb_substr($e->getMessage(), 0, 250)], 422);
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
            'landing_hero_eyebrow', 'landing_hero_title', 'landing_hero_subtitle', 'landing_cta_text', 'landing_cta_url', 'landing_trust_text',
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
            'landing_hero_eyebrow' => 'nullable|string|max:80',
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
