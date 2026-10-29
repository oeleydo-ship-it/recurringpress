<?php

namespace App\Services\Ai;

use App\Models\AiUsageLog;
use App\Models\Company;
use App\Models\SystemSetting;
use App\Services\Ai\Providers\ProviderUnavailable;
use Illuminate\Support\Facades\Log;

/**
 * Tenant AI requests. Configured in Superadmin → AI: an on/off switch, a per-tenant monthly
 * request quota, and providers (Anthropic, OpenAI, DeepSeek, Kimi; see AiProviders) with a
 * default and a failover order.
 *
 * A request goes to the default provider; on a technical failure (outage, rate limit, bad
 * key, unusable output) it is retried on the next usable provider. A refusal is final: the
 * same content is never re-sent to another provider. Every attempt is logged to
 * ai_usage_logs; only successful or refused requests count toward the quota.
 */
class AiClient
{
    public const DEFAULT_QUOTA = 100;

    /** Follow-up calls inside one chat turn (tool rounds): logged, but not counted or blocked by the quota. */
    public const STEP_FEATURE = 'chat_step';

    public static function monthlyQuota(): int
    {
        return max(0, (int) (SystemSetting::get('ai_monthly_quota') ?? self::DEFAULT_QUOTA));
    }

    /** Switched on in Superadmin and at least one provider is usable. */
    public static function enabled(): bool
    {
        return filter_var(SystemSetting::get('ai_enabled'), FILTER_VALIDATE_BOOL) && AiProviders::chain() !== [];
    }

    public static function usedThisMonth(Company $company): int
    {
        return AiUsageLog::where('company_id', $company->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->whereIn('status', ['ok', 'refused'])
            ->where('feature', '!=', self::STEP_FEATURE)
            ->count();
    }

    public static function remaining(Company $company): int
    {
        return max(0, self::monthlyQuota() - self::usedThisMonth($company));
    }

    /**
     * One structured request: the answer must be JSON matching $schema.
     *
     * @return array<string, mixed> the decoded JSON
     *
     * @throws AiException with a message safe to show the tenant
     */
    public function json(?Company $company, string $feature, string $system, string $prompt, array $schema, string $effort = 'low', int $maxTokens = 8000): array
    {
        if (! self::enabled()) {
            throw new AiException('AI features are not available right now.');
        }

        if ($company && $feature !== self::STEP_FEATURE && self::remaining($company) <= 0) {
            throw new AiException('You have used all ' . self::monthlyQuota() . ' AI requests for this month. The limit resets on the 1st.');
        }

        $lastFailure = null;

        foreach (AiProviders::chain() as $name) {
            $model = (string) AiProviders::model($name);

            try {
                $result = AiProviders::make($name)->json($system, $prompt, $schema, $effort, $maxTokens);
            } catch (ProviderUnavailable $e) {
                $this->log($company, $feature, $name, $model, 'error', $e->inputTokens, $e->outputTokens, $e->getMessage());
                Log::warning('AI provider failed', ['provider' => $name, 'feature' => $feature, 'error' => $e->getMessage()]);
                $lastFailure = $e;

                continue; // fail over to the next provider
            }

            if ($result->refused) {
                $this->log($company, $feature, $name, $result->model, 'refused', $result->inputTokens, $result->outputTokens, $result->refusalReason);

                throw new AiException('The AI declined this request.');
            }

            $this->log($company, $feature, $name, $result->model, 'ok', $result->inputTokens, $result->outputTokens);

            return $result->data;
        }

        throw new AiException($lastFailure?->publicMessage ?? 'Could not reach the AI service. Please try again.', previous: $lastFailure);
    }

    /** Superadmin connection test for one provider. Returns the model that answered. */
    public function ping(string $name): string
    {
        $model = AiProviders::make($name)->ping();
        $this->log(null, 'connection_test', $name, $model, 'ok');

        return $model;
    }

    private function log(?Company $company, string $feature, string $provider, string $model, string $status, int $in = 0, int $out = 0, ?string $error = null): void
    {
        [$pin, $pout] = AiProviders::prices($provider, $model);

        AiUsageLog::create([
            'company_id' => $company?->id,
            'user_id' => auth()->id(),
            'feature' => $feature,
            'provider' => $provider,
            'model' => mb_substr($model, 0, 60),
            'status' => $status,
            'input_tokens' => $in,
            'output_tokens' => $out,
            'cost_usd' => round(($in * $pin + $out * $pout) / 1_000_000, 6),
            'error' => $error ? mb_substr($error, 0, 500) : null,
        ]);
    }
}
