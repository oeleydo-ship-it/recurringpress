<?php

namespace App\Services\Ai\Providers;

use RuntimeException;

/**
 * A technical failure (auth, rate limit, outage, timeout, unusable output): AiClient logs it
 * and tries the next provider. $publicMessage is safe to show when no provider is left.
 */
class ProviderUnavailable extends RuntimeException
{
    public function __construct(string $message, public readonly string $publicMessage = 'Could not reach the AI service. Please try again.', public readonly int $inputTokens = 0, public readonly int $outputTokens = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
