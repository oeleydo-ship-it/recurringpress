<?php

namespace App\Services\Ai\Providers;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\NotFoundException;
use Anthropic\Core\Exceptions\PermissionDeniedException;
use Anthropic\Core\Exceptions\RateLimitException;
use App\Services\Ai\AiSchema;

/** Claude through the official Anthropic PHP SDK, with JSON-schema structured outputs. */
class AnthropicProvider implements AiProvider
{
    public function __construct(private string $apiKey, private string $model, private ?string $baseUrl = null)
    {
    }

    public function json(string $system, string $prompt, array $schema, string $effort, int $maxTokens): ProviderResult
    {
        // Haiku has no server-side refusal fallback.
        $fallback = ! str_starts_with($this->model, 'claude-haiku');

        try {
            $message = $this->client()->beta->messages->create(
                model: $this->model,
                maxTokens: $maxTokens,
                system: $system,
                messages: [['role' => 'user', 'content' => $prompt]],
                outputConfig: [
                    'effort' => $effort,
                    'format' => ['type' => 'json_schema', 'schema' => $schema],
                ],
                // A request the model declines is re-run on a fallback model chosen by Anthropic.
                fallbacks: $fallback ? 'default' : null,
                betas: $fallback ? ['server-side-fallback-2026-07-01'] : null,
            );
        } catch (AuthenticationException|PermissionDeniedException $e) {
            throw new ProviderUnavailable($e->getMessage(), 'The AI service rejected the platform API key. Please contact support.', previous: $e);
        } catch (NotFoundException $e) {
            throw new ProviderUnavailable($e->getMessage(), 'The configured AI model is not available. Please contact support.', previous: $e);
        } catch (RateLimitException $e) {
            throw new ProviderUnavailable($e->getMessage(), 'The AI service is busy. Please try again in a minute.', previous: $e);
        } catch (APIStatusException|APIConnectionException $e) {
            throw new ProviderUnavailable($e->getMessage(), previous: $e);
        }

        $model = $message->model ?: $this->model;
        $in = (int) $message->usage->inputTokens;
        $out = (int) $message->usage->outputTokens;

        if ($message->stopReason === 'refusal') {
            return new ProviderResult(null, $model, $in, $out, refused: true, refusalReason: $message->stopDetails?->category);
        }

        $text = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        $data = json_decode($text, true);

        if ($message->stopReason === 'max_tokens' || ! is_array($data) || ! AiSchema::valid($data, $schema)) {
            throw new ProviderUnavailable("Unusable output (stop: {$message->stopReason})", 'The AI response was incomplete. Please try again.', $in, $out);
        }

        return new ProviderResult($data, $model, $in, $out);
    }

    public function ping(): string
    {
        $message = $this->client()->messages->create(
            model: $this->model,
            maxTokens: 64,
            messages: [['role' => 'user', 'content' => 'Reply with the single word: OK']],
            outputConfig: ['effort' => 'low'],
        );

        return $message->model ?: $this->model;
    }

    public function models(): array
    {
        return collect($this->client()->models->list(limit: 100)->getItems())->pluck('id')->filter()->values()->all();
    }

    private function client(): Client
    {
        return new Client(apiKey: $this->apiKey, baseUrl: $this->baseUrl, requestOptions: ['timeout' => 90, 'maxRetries' => 2]);
    }
}
