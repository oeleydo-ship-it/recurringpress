<?php

namespace App\Services;

use App\Models\Company;
use App\Models\TenantNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Writes in-app notifications (the top-bar bell) for every active user of one company.
 *
 * Called from the places where events already happen (controllers, jobs, Stripe flows).
 * Never throws: a failed notification must not break a payment or a settings save.
 * Rows are written on the caller's connection, so inside a transaction they roll back with it.
 */
class TenantNotifier
{
    public const AUDIENCE_ALL = 'all';

    public const AUDIENCE_ADMINS = 'admins';

    /**
     * @param  string|null  $url  app-relative path (tenants may be on custom domains)
     * @param  string|null  $dedupeKey  skip if the company already has a notification with this key
     */
    public static function notify(
        Company|int|null $company,
        string $type,
        string $title,
        ?string $body = null,
        ?string $url = null,
        string $audience = self::AUDIENCE_ALL,
        ?string $dedupeKey = null,
    ): void {
        $companyId = $company instanceof Company ? $company->id : $company;

        if (! $companyId) {
            return;
        }

        try {
            if ($dedupeKey && TenantNotification::where('company_id', $companyId)->where('dedupe_key', $dedupeKey)->exists()) {
                return;
            }

            $recipients = User::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->when($audience === self::AUDIENCE_ADMINS, fn ($q) => $q->whereIn('role', [User::ROLE_OWNER, User::ROLE_ADMIN]))
                ->pluck('id');

            if ($recipients->isEmpty()) {
                return;
            }

            $now = now();
            TenantNotification::insert($recipients->map(fn ($userId) => [
                'company_id' => $companyId,
                'user_id' => $userId,
                'type' => $type,
                'title' => mb_substr($title, 0, 255),
                'body' => $body !== null ? mb_substr($body, 0, 500) : null,
                'url' => $url,
                'dedupe_key' => $dedupeKey,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        } catch (\Throwable $e) {
            Log::warning('Tenant notification failed', ['company_id' => $companyId, 'type' => $type, 'error' => $e->getMessage()]);
        }
    }

    /** "AED 1,250.00" */
    public static function money(float|string|null $amount, ?string $currency): string
    {
        return strtoupper($currency ?: 'USD') . ' ' . number_format((float) $amount, 2);
    }

    /** App-relative URL for a named route. */
    public static function path(string $name, mixed $parameters = []): ?string
    {
        try {
            return route($name, $parameters, false);
        } catch (\Throwable) {
            return null;
        }
    }

    /** Who did it, for settings/team notifications ("by Jane"). */
    public static function byActor(): string
    {
        $name = auth()->user()?->name;

        return $name ? " by {$name}" : '';
    }
}
