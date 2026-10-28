<?php

namespace App\Support;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\SystemSetting;
use App\Services\CloudflareCustomHostnames;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Tenant hosts for client-facing invoice and quotation links:
 *  - custom domains (e.g. billing.client-business.com), registered as Cloudflare for SaaS
 *    custom hostnames and used once Cloudflare reports the hostname and certificate active;
 *  - platform subdomains (e.g. acme.recurringpress.com), active as soon as they're claimed.
 * Both are switched on in Superadmin → Custom domains.
 */
class TenantDomain
{
    /** Request attribute holding the company id when a request arrived on a tenant host. */
    public const ATTRIBUTE = 'custom_domain_company_id';

    /** Tenant custom domains: switched on in Superadmin and Cloudflare fully configured. */
    public static function enabled(): bool
    {
        return filter_var(SystemSetting::get('custom_domains_enabled'), FILTER_VALIDATE_BOOL) && CloudflareCustomHostnames::configured();
    }

    /** Normalise user input ("https://Billing.Example.com/") to a bare hostname, or null if invalid. */
    public static function normalize(?string $input): ?string
    {
        $host = strtolower(trim((string) $input));
        $host = preg_replace('#^[a-z][a-z0-9+.-]*://#', '', $host);
        $host = rtrim(explode(':', explode('/', $host)[0])[0], '.');

        $valid = strlen($host) <= 253
            && filter_var($host, FILTER_VALIDATE_IP) === false
            && preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z][a-z0-9-]{0,61}[a-z0-9]$/', $host);

        return $valid ? $host : null;
    }

    /** Hostnames a tenant may not claim: the platform itself and anything under it. */
    public static function isReserved(string $host): bool
    {
        foreach (self::platformHosts() as $platform) {
            $base = str_starts_with($platform, 'www.') ? substr($platform, 4) : $platform;

            if ($host === $platform || str_ends_with($host, '.' . $base)) {
                return true;
            }
        }

        return in_array($host, ['localhost'], true) || preg_match('/\.(localhost|local|test|example|invalid|internal)$/', $host) === 1;
    }

    /** @return string[] */
    public static function platformHosts(): array
    {
        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $hosts = [$appHost, str_starts_with($appHost, 'www.') ? substr($appHost, 4) : 'www.' . $appHost, 'localhost'];

        return array_values(array_unique(array_filter([...$hosts, ...array_map('strtolower', config('tenancy.platform_hosts', []))])));
    }

    public static function isPlatformHost(string $host): bool
    {
        $host = strtolower($host);

        return filter_var($host, FILTER_VALIDATE_IP) !== false || in_array($host, self::platformHosts(), true);
    }

    /** The company whose verified custom domain or platform subdomain is $host, if any. */
    public static function companyForHost(string $host): ?Company
    {
        $host = strtolower($host);

        if (self::enabled()) {
            $settings = CompanySetting::query()
                ->where('custom_domain', $host)
                ->whereNotNull('custom_domain_verified_at')
                ->first();

            if ($settings) {
                return $settings->hasVerifiedCustomDomain() ? $settings->company : null;
            }
        }

        $slug = self::subdomainSlugFromHost($host);

        $settings = $slug ? CompanySetting::where('subdomain', $slug)->first() : null;

        return $settings?->hasActiveSubdomain() ? $settings->company : null;
    }

    /* ------------------------- Platform subdomains ------------------------- */

    public static function subdomainsEnabled(): bool
    {
        return filter_var(SystemSetting::get('tenant_subdomains_enabled'), FILTER_VALIDATE_BOOL);
    }

    /** True when either custom domains or subdomains are on (host filtering is active). */
    public static function anyEnabled(): bool
    {
        return self::enabled() || self::subdomainsEnabled();
    }

    public static function subdomainBase(): string
    {
        return strtolower((string) config('tenancy.subdomains.base'));
    }

    public static function subdomainHost(string $slug): string
    {
        return $slug . '.' . self::subdomainBase();
    }

    /** "acme" for "acme.<base>"; null for anything else (including deeper subdomains). */
    public static function subdomainSlugFromHost(string $host): ?string
    {
        $suffix = '.' . self::subdomainBase();

        if (! self::subdomainsEnabled() || ! str_ends_with($host, $suffix)) {
            return null;
        }

        $slug = substr($host, 0, -strlen($suffix));

        return self::slugRejection($slug) === null ? $slug : null;
    }

    /** Why a slug can't be used (format or reserved), or null if it's acceptable. */
    public static function slugRejection(string $slug): ?string
    {
        if (! preg_match('/^[a-z0-9](?:[a-z0-9-]{1,30}[a-z0-9])$/', $slug) || str_contains($slug, '--')) {
            return 'Use 3–32 lowercase letters, numbers and single hyphens, starting and ending with a letter or number.';
        }

        if (in_array($slug, config('tenancy.subdomains.reserved', []), true)) {
            return 'This name is reserved.';
        }

        return null;
    }

    /**
     * Limit a public document lookup to the company that owns the domain the request came in
     * on, so tenant A's domain can never render tenant B's invoice.
     */
    public static function scopeToRequestDomain(Builder $query, ?Request $request = null): Builder
    {
        $companyId = ($request ?? request())->attributes->get(self::ATTRIBUTE);

        return $companyId ? $query->where('company_id', $companyId) : $query;
    }

    /**
     * Absolute URL for a relative path. Precedence: verified custom domain, then platform
     * subdomain, then the platform address.
     */
    public static function url(?Company $company, string $path): string
    {
        $settings = $company?->settings;
        $path = '/' . ltrim($path, '/');

        if ($settings?->hasVerifiedCustomDomain()) {
            return config('tenancy.custom_domains.scheme', 'https') . '://' . $settings->custom_domain . $path;
        }

        if ($settings?->hasActiveSubdomain()) {
            return self::subdomainUrl($settings->subdomain, $path);
        }

        return url($path);
    }

    public static function subdomainUrl(string $slug, string $path = '/'): string
    {
        return config('tenancy.subdomains.scheme', 'https') . '://' . self::subdomainHost($slug) . self::portSuffix() . '/' . ltrim($path, '/');
    }

    /** Keep a non-standard APP_URL port (local dev, e.g. :8002) on subdomain links. */
    private static function portSuffix(): string
    {
        $port = parse_url((string) config('app.url'), PHP_URL_PORT);

        return $port ? ':' . $port : '';
    }

    /* ------------------------ Cloudflare verification ------------------------ */

    /**
     * Register the domain with Cloudflare (replacing any previous one) and store the DNS
     * records the tenant has to add. Throws with Cloudflare's message on API errors.
     */
    public static function connect(CompanySetting $settings, string $domain, ?CloudflareCustomHostnames $cf = null): void
    {
        $cf ??= app(CloudflareCustomHostnames::class);

        $hostname = $cf->create($domain);

        if ($settings->cf_hostname_id && $settings->cf_hostname_id !== ($hostname['id'] ?? null)) {
            self::disconnect($settings, $cf);
        }

        $settings->forceFill([
            'custom_domain' => $domain,
            'custom_domain_verified_at' => null,
            'custom_domain_checked_at' => now(),
            'custom_domain_error' => null,
        ])->save();

        self::store($settings, $hostname);
    }

    /** Remove the custom hostname from Cloudflare (best effort) and clear it locally. */
    public static function disconnect(CompanySetting $settings, ?CloudflareCustomHostnames $cf = null): void
    {
        if ($settings->cf_hostname_id) {
            try {
                ($cf ?? app(CloudflareCustomHostnames::class))->delete($settings->cf_hostname_id);
            } catch (\Throwable $e) {
                Log::warning('Could not delete Cloudflare custom hostname', ['id' => $settings->cf_hostname_id, 'error' => $e->getMessage()]);
            }
        }

        $settings->forceFill([
            'custom_domain' => null,
            'cf_hostname_id' => null,
            'cf_hostname_status' => null,
            'cf_ssl_status' => null,
            'cf_dns_records' => null,
            'custom_domain_verified_at' => null,
            'custom_domain_checked_at' => null,
            'custom_domain_error' => null,
        ])->save();
    }

    /**
     * Refresh the hostname's status from Cloudflare. It counts as verified once both the
     * hostname and its certificate are active. With $grace, a verified domain is only
     * un-verified after two failed checks in a row, so one hiccup doesn't break links.
     */
    public static function verify(CompanySetting $settings, ?CloudflareCustomHostnames $cf = null, bool $grace = true): bool
    {
        if (blank($settings->custom_domain)) {
            return false;
        }

        $cf ??= app(CloudflareCustomHostnames::class);

        try {
            $hostname = $settings->cf_hostname_id ? $cf->get($settings->cf_hostname_id) : null;
            // Deleted on Cloudflare's side (or never created): register it again.
            $hostname ??= $cf->create($settings->custom_domain);
        } catch (\Throwable $e) {
            // API outage: record it, but don't count it against the tenant's domain.
            $settings->forceFill(['custom_domain_checked_at' => now(), 'custom_domain_error' => $e->getMessage()])->save();

            return $settings->custom_domain_verified_at !== null;
        }

        $active = ($hostname['status'] ?? null) === 'active' && ($hostname['ssl']['status'] ?? null) === 'active';
        $error = $active ? null : (CloudflareCustomHostnames::problemFor($hostname) ?? self::pendingMessage($hostname));
        $wasFailing = ! $grace || filled($settings->custom_domain_error);

        $settings->forceFill([
            'custom_domain_checked_at' => now(),
            'custom_domain_error' => $error,
            'custom_domain_verified_at' => match (true) {
                $active => $settings->custom_domain_verified_at ?? now(),
                $settings->custom_domain_verified_at !== null && ! $wasFailing => $settings->custom_domain_verified_at,
                default => null,
            },
        ])->save();

        self::store($settings, $hostname);

        return $active;
    }

    private static function store(CompanySetting $settings, array $hostname): void
    {
        $settings->forceFill([
            'cf_hostname_id' => $hostname['id'] ?? $settings->cf_hostname_id,
            'cf_hostname_status' => $hostname['status'] ?? null,
            'cf_ssl_status' => $hostname['ssl']['status'] ?? null,
            'cf_dns_records' => CloudflareCustomHostnames::recordsFor($hostname),
        ])->save();
    }

    private static function pendingMessage(array $hostname): string
    {
        if (($hostname['status'] ?? null) !== 'active') {
            return 'Waiting for the DNS records below (Cloudflare status: ' . str_replace('_', ' ', $hostname['status'] ?? 'pending') . ').';
        }

        return 'Domain confirmed; the SSL certificate is being issued (status: ' . str_replace('_', ' ', $hostname['ssl']['status'] ?? 'pending') . ').';
    }
}
