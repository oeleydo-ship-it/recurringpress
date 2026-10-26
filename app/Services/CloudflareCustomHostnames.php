<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cloudflare for SaaS: each tenant custom domain is a "custom hostname" on the platform's
 * Cloudflare zone. Cloudflare verifies ownership, issues the certificate and proxies the
 * traffic to the fallback origin (this server). Configured in Superadmin → Custom domains.
 *
 * API token needs the "SSL and Certificates: Edit" permission on the zone (plus
 * "Zone: Read" for the connection test).
 */
class CloudflareCustomHostnames
{
    public static function zoneId(): ?string
    {
        return SystemSetting::get('cloudflare_zone_id') ?: null;
    }

    public static function token(): ?string
    {
        $stored = SystemSetting::get('cloudflare_api_token');

        if (! $stored) {
            return null;
        }

        try {
            return Crypt::decryptString($stored);
        } catch (\Throwable) {
            return null; // APP_KEY changed: the token has to be saved again
        }
    }

    public static function storeToken(string $token): void
    {
        SystemSetting::set('cloudflare_api_token', Crypt::encryptString($token));
    }

    /** What tenants point their CNAME at: the zone's fallback origin hostname. */
    public static function cnameTarget(): ?string
    {
        return strtolower((string) SystemSetting::get('cloudflare_cname_target')) ?: null;
    }

    public static function configured(): bool
    {
        return self::zoneId() && self::token() && self::cnameTarget();
    }

    /** Zone name + fallback origin status, for Superadmin's "Test connection". */
    public function testConnection(): array
    {
        $zone = $this->call('get', 'zones/' . self::zoneId());
        $fallback = $this->call('get', $this->base() . '/fallback_origin', allowNotFound: true);

        return [
            'zone' => $zone['name'] ?? null,
            'fallback_origin' => $fallback['origin'] ?? null,
            'fallback_status' => $fallback['status'] ?? null,
        ];
    }

    /** Create the custom hostname (or adopt an existing one for the same hostname). */
    public function create(string $hostname): array
    {
        if ($existing = $this->findByHostname($hostname)) {
            return $existing;
        }

        return $this->call('post', $this->base(), [
            'hostname' => $hostname,
            // TXT validation lets the certificate be issued before the CNAME is switched over.
            'ssl' => ['method' => 'txt', 'type' => 'dv', 'settings' => ['min_tls_version' => '1.2']],
        ]);
    }

    public function get(string $id): ?array
    {
        return $this->call('get', $this->base() . '/' . $id, allowNotFound: true);
    }

    public function delete(string $id): void
    {
        $this->call('delete', $this->base() . '/' . $id, allowNotFound: true);
    }

    public function findByHostname(string $hostname): ?array
    {
        $results = $this->call('get', $this->base(), ['hostname' => $hostname]);

        return collect($results)->firstWhere('hostname', $hostname);
    }

    /**
     * DNS records the tenant must add, from a custom hostname API result:
     * the CNAME to us, the ownership TXT, and the certificate validation TXT(s).
     *
     * @return array<int, array{type: string, name: string, value: string, purpose: string}>
     */
    public static function recordsFor(array $hostname): array
    {
        $records = [[
            'type' => 'CNAME',
            'name' => $hostname['hostname'],
            'value' => (string) self::cnameTarget(),
            'purpose' => 'Routes traffic to us',
        ]];

        if ($ov = $hostname['ownership_verification'] ?? null) {
            $records[] = ['type' => strtoupper($ov['type'] ?? 'TXT'), 'name' => $ov['name'], 'value' => $ov['value'], 'purpose' => 'Proves you own the domain'];
        }

        foreach ($hostname['ssl']['validation_records'] ?? [] as $vr) {
            if (! empty($vr['txt_name'])) {
                $records[] = ['type' => 'TXT', 'name' => $vr['txt_name'], 'value' => $vr['txt_value'], 'purpose' => 'Issues the SSL certificate'];
            }
        }

        return $records;
    }

    /** First human-readable problem Cloudflare reports for a hostname, if any. */
    public static function problemFor(array $hostname): ?string
    {
        $errors = array_merge(
            $hostname['verification_errors'] ?? [],
            $hostname['ssl']['validation_errors'] ?? [],
        );

        $first = $errors[0] ?? null;

        return is_array($first) ? ($first['message'] ?? null) : $first;
    }

    private function base(): string
    {
        return 'zones/' . self::zoneId() . '/custom_hostnames';
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim(config('tenancy.cloudflare.api_base'), '/'))
            ->withToken((string) self::token())
            ->acceptJson()
            ->timeout(config('tenancy.cloudflare.timeout', 15));
    }

    /** @throws RuntimeException with Cloudflare's own error message */
    private function call(string $method, string $path, array $data = [], bool $allowNotFound = false): ?array
    {
        if (! self::zoneId() || ! self::token()) {
            throw new RuntimeException('Cloudflare is not configured.');
        }

        try {
            $response = $this->client()->{$method}($path, $data);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Could not reach Cloudflare: ' . $e->getMessage(), 0, $e);
        }

        if ($allowNotFound && $response->status() === 404) {
            return null;
        }

        if (! $response->successful() || $response->json('success') === false) {
            $message = $response->json('errors.0.message') ?: ('HTTP ' . $response->status());

            throw new RuntimeException('Cloudflare: ' . $message);
        }

        return $response->json('result');
    }
}
