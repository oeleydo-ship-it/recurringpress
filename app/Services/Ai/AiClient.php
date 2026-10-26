<?php

namespace App\Services\Ai;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\BadRequestException;
use Anthropic\Core\Exceptions\NotFoundException;
use Anthropic\Core\Exceptions\PermissionDeniedException;
use Anthropic\Core\Exceptions\RateLimitException;
use App\Models\AiUsageLog;
use App\Models\Company;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Claude (Anthropic API) for tenant AI features. Configured in Superadmin → AI: API key
 * (encrypted), model, on/off switch and a per-tenant monthly request quota. Every request
 * is logged to ai_usage_logs for quota counting and cost reporting.
 */
class AiClient
{
    public const MODELS = [
        'claude-opus-5-5' => 'Claude Opus 5.5 (best quality)',
        'claude-sonnet-5-5' => 'Claude Sonnet 5.5 (faster, cheaper)',
        'claude-haiku-5-5' => 'Claude Haiku 5.5 (fastest, cheapest)',
    ];

    public const DEFAULT_MODEL = 'claude-opus-5-5';

    public const DEFAULT_QUOTA = 100;

    /** USD per million tokens [input, output], for the cost estimate in the usage log. */
    private const PRICES = [
        'claude-opus-5-5' => [4.00, 20.00],
        'claude-sonnet-5-5' => [2.00, 10.00],
        'claude-haiku-5-5' => [0.10, 0.50],
        'claude-opus-5' => [5.00, 25.00],
        'claude-opus-4-8' => [5.00, 25.00],
    ];

    /* ------------------------------ Settings ------------------------------ */

    public static function apiKey(): ?string
    {
        $stored = SystemSetting::get('ai_api_key');

        if (! $stored) {
            return null;
        }

        try {
            return Crypt::decryptString($stored);
        } catch (\Throwable) {
            return null; // APP_KEY changed: the key has to be saved again
        }
    }

    public static function storeApiKey(string $key): void
    {
        SystemSetting::set('ai_api_key', Crypt::encryptString($key));
    }

    public static function model(): string
    {
        $model = SystemSetting::get('ai_model');

        return array_key_exists((string) $model, self::MODELS) ? $model : self::DEFAULT_MODEL;
    }

    public static function monthlyQuota(): int
    {
        return max(0, (int) (SystemSetting::get('ai_monthly_quota') ?? self::DEFAULT_QUOTA));
    }

    /** Switched on in Superadmin and an API key is saved. */
    public static function enabled(): bool
    {
        return filter_var(SystemSetting::get('ai_enabled'), FILTER_VALIDATE_BOOL) && filled(self::apiKey());
    }

    public static function usedThisMonth(Company $company): int
    {
        return AiUsageLog::where('company_id', $company->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->whereIn('status', ['ok', 'refused'])
            ->count();
    }

    public static function remaining(Company $company): int
    {
        return max(0, self::monthlyQuota() - self::usedThisMonth($company));
    }

    /* ------------------------------ Requests ------------------------------ */

    /**
     * One structured request: Claude must answer with JSON matching $schema.
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

        if ($company && self::remaining($company) <= 0) {
            throw new AiException('You have used all ' . self::monthlyQuota() . ' AI requests for this month. The limit resets on the 1st.');
        }

        $model = self::model();

        try {
            $message = $this->client()->beta->messages->create(
                model: $model,
                maxTokens: $maxTokens,
                system: $system,
                messages: [['role' => 'user', 'content' => $prompt]],
                outputConfig: [
                    'effort' => $effort,
                    'format' => ['type' => 'json_schema', 'schema' => $schema],
                ],
                // Re-run a request the model declines on a fallback model, chosen by Anthropic
                // per refusal category (Haiku has no server-side fallback).
                fallbacks: $model === 'claude-haiku-5-5' ? null : 'default',
                betas: $model === 'claude-haiku-5-5' ? null : ['server-side-fallback-2026-07-01'],
            );
        } catch (AuthenticationException|PermissionDeniedException $e) {
            $this->log($company, $feature, $model, 'error', error: $e->getMessage());
            Log::error('Anthropic API key rejected', ['error' => $e->getMessage()]);

            throw new AiException('The AI service rejected the platform API key. Please contact support.', previous: $e);
        } catch (NotFoundException|BadRequestException $e) {
            $this->log($company, $feature, $model, 'error', error: $e->getMessage());
            Log::error('Anthropic request rejected', ['feature' => $feature, 'model' => $model, 'error' => $e->getMessage()]);

            throw new AiException('The AI request could not be processed. Please try again later.', previous: $e);
        } catch (RateLimitException $e) {
            $this->log($company, $feature, $model, 'error', error: $e->getMessage());

            throw new AiException('The AI service is busy. Please try again in a minute.', previous: $e);
        } catch (APIStatusException|APIConnectionException $e) {
            $this->log($company, $feature, $model, 'error', error: $e->getMessage());
            Log::warning('Anthropic request failed', ['feature' => $feature, 'error' => $e->getMessage()]);

            throw new AiException('Could not reach the AI service. Please try again.', previous: $e);
        }

        $usedModel = $message->model ?: $model;
        $in = (int) $message->usage->inputTokens;
        $out = (int) $message->usage->outputTokens;

        if ($message->stopReason === 'refusal') {
            $this->log($company, $feature, $usedModel, 'refused', $in, $out, $message->stopDetails?->category);

            throw new AiException('The AI declined this request.');
        }

        $text = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        $data = json_decode($text, true);

        if (! is_array($data) || $message->stopReason === 'max_tokens') {
            $this->log($company, $feature, $usedModel, 'error', $in, $out, 'Unparseable or truncated output (stop: ' . $message->stopReason . ')');

            throw new AiException('The AI response was incomplete. Please try again.');
        }

        $this->log($company, $feature, $usedModel, 'ok', $in, $out);

        return $data;
    }

    /** Minimal request for Superadmin's "Test connection". Returns the model that answered. */
    public function ping(): string
    {
        $message = $this->client()->messages->create(
            model: self::model(),
            maxTokens: 64,
            messages: [['role' => 'user', 'content' => 'Reply with the single word: OK']],
            outputConfig: ['effort' => 'low'],
        );

        $this->log(null, 'connection_test', $message->model ?: self::model(), 'ok', (int) $message->usage->inputTokens, (int) $message->usage->outputTokens);

        return $message->model ?: self::model();
    }

    private function client(): Client
    {
        $key = self::apiKey();

        if (! $key) {
            throw new AiException('No AI API key is configured.');
        }

        return new Client(
            apiKey: $key,
            baseUrl: config('services.anthropic.base_url') ?: null,
            requestOptions: ['timeout' => 90, 'maxRetries' => 2],
        );
    }

    private function log(?Company $company, string $feature, string $model, string $status, int $in = 0, int $out = 0, ?string $error = null): void
    {
        [$pin, $pout] = self::PRICES[$model] ?? self::PRICES[self::DEFAULT_MODEL];

        AiUsageLog::create([
            'company_id' => $company?->id,
            'user_id' => auth()->id(),
            'feature' => $feature,
            'model' => $model,
            'status' => $status,
            'input_tokens' => $in,
            'output_tokens' => $out,
            'cost_usd' => round(($in * $pin + $out * $pout) / 1_000_000, 6),
            'error' => $error ? mb_substr($error, 0, 500) : null,
        ]);
    }
}
