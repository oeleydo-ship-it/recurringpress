<?php

namespace App\Services\Ai\Providers;

/**
 * One AI vendor (Anthropic, OpenAI, DeepSeek, Kimi). AiClient picks providers in order and
 * fails over on ProviderUnavailable; a refusal is final and never re-sent elsewhere.
 */
interface AiProvider
{
    /**
     * One structured request whose answer must be JSON matching $schema.
     *
     * @throws ProviderUnavailable on outages, rate limits, bad keys, timeouts or unusable output
     */
    public function json(string $system, string $prompt, array $schema, string $effort, int $maxTokens): ProviderResult;

    /** Minimal request for Superadmin's connection test. Returns the model that answered. */
    public function ping(): string;

    /** @return string[] model ids this key can use (from the provider's /models endpoint) */
    public function models(): array;
}
