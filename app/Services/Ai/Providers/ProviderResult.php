<?php

namespace App\Services\Ai\Providers;

final class ProviderResult
{
    /**
     * @param  array<string, mixed>|null  $data  decoded JSON (null when refused)
     */
    public function __construct(
        public readonly ?array $data,
        public readonly string $model,
        public readonly int $inputTokens,
        public readonly int $outputTokens,
        public readonly bool $refused = false,
        public readonly ?string $refusalReason = null,
    ) {
    }
}
