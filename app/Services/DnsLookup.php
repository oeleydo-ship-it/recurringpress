<?php

namespace App\Services;

/**
 * Thin wrapper around PHP's resolver so custom-domain checks can be faked locally.
 */
class DnsLookup
{
    /** @return string[] */
    public function txt(string $name): array
    {
        return array_values(array_filter(array_map(
            fn ($r) => $r['txt'] ?? (isset($r['entries']) ? implode('', $r['entries']) : null),
            $this->records($name, DNS_TXT)
        )));
    }

    public function cname(string $name): ?string
    {
        $target = $this->records($name, DNS_CNAME)[0]['target'] ?? null;

        return $target ? rtrim(strtolower($target), '.') : null;
    }

    /** @return string[] IPv4 addresses */
    public function a(string $name): array
    {
        return array_values(array_filter(array_column($this->records($name, DNS_A), 'ip')));
    }

    private function records(string $name, int $type): array
    {
        try {
            return @dns_get_record($name, $type) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }
}
