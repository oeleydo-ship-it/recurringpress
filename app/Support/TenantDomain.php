<?php

namespace App\Support;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Services\DnsLookup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Tenant custom domains (e.g. billing.client-business.com) for client-facing invoice and
 * quotation links. A domain is only used once its DNS is verified: an ownership TXT record
 * plus a CNAME/A record pointing at the platform.
 */
class TenantDomain
{
    /** Request attribute holding the company id when a request arrived on a custom domain. */
    public const ATTRIBUTE = 'custom_domain_company_id';

    public static function enabled(): bool
    {
        return (bool) config('tenancy.custom_domains.enabled');
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

    /** The company whose verified custom domain is $host, if any. */
    public static function companyForHost(string $host): ?Company
    {
        if (! self::enabled()) {
            return null;
        }

        $settings = CompanySetting::query()
            ->where('custom_domain', strtolower($host))
            ->whereNotNull('custom_domain_verified_at')
            ->first();

        return $settings?->company;
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

    /** Absolute URL for a relative path, on the company's verified domain when it has one. */
    public static function url(?Company $company, string $path): string
    {
        $settings = $company?->settings;

        if ($settings?->hasVerifiedCustomDomain()) {
            return config('tenancy.custom_domains.scheme', 'https') . '://' . $settings->custom_domain . '/' . ltrim($path, '/');
        }

        return url($path);
    }

    /* ----------------------------- Verification ----------------------------- */

    public static function newToken(): string
    {
        return Str::lower(Str::random(32));
    }

    public static function txtName(string $domain): string
    {
        return config('tenancy.custom_domains.txt_prefix', '_recurringpress') . '.' . $domain;
    }

    public static function txtValue(string $token): string
    {
        return 'recurringpress-verify=' . $token;
    }

    public static function cnameTarget(): string
    {
        return strtolower((string) config('tenancy.custom_domains.cname_target'));
    }

    /** @return string[] IPs an apex A record may point at. */
    public static function targetIps(DnsLookup $dns): array
    {
        $ips = config('tenancy.custom_domains.ips', []);

        return $ips ?: $dns->a(self::cnameTarget());
    }

    /**
     * Check the domain's DNS. Returns null when everything is in place, otherwise a message
     * the tenant can act on.
     */
    public static function check(string $domain, string $token, ?DnsLookup $dns = null): ?string
    {
        $dns ??= app(DnsLookup::class);

        if (! in_array(self::txtValue($token), array_map('trim', $dns->txt(self::txtName($domain))), true)) {
            return 'TXT record ' . self::txtName($domain) . ' was not found or does not match.';
        }

        $target = self::cnameTarget();
        $cname = $dns->cname($domain);

        if ($cname === $target) {
            return null;
        }

        $targetIps = self::targetIps($dns);
        $domainIps = $dns->a($domain);

        if ($domainIps && $targetIps && array_intersect($domainIps, $targetIps) && ! array_diff($domainIps, $targetIps)) {
            return null;
        }

        return $cname
            ? "{$domain} is a CNAME to {$cname}, expected {$target}."
            : "{$domain} does not point to {$target} yet (no matching CNAME or A record).";
    }

    /**
     * Run the DNS check and persist the result. With $grace, a previously verified domain is
     * only un-verified after two failed checks in a row, so one DNS hiccup doesn't break links.
     */
    public static function verify(CompanySetting $settings, ?DnsLookup $dns = null, bool $grace = true): bool
    {
        if (blank($settings->custom_domain) || blank($settings->custom_domain_token)) {
            return false;
        }

        $error = self::check($settings->custom_domain, $settings->custom_domain_token, $dns);
        $wasFailing = ! $grace || filled($settings->custom_domain_error);

        $settings->forceFill([
            'custom_domain_checked_at' => now(),
            'custom_domain_error' => $error,
            'custom_domain_verified_at' => match (true) {
                $error === null => $settings->custom_domain_verified_at ?? now(),
                $settings->custom_domain_verified_at !== null && ! $wasFailing => $settings->custom_domain_verified_at,
                default => null,
            },
        ])->save();

        return $error === null;
    }
}
