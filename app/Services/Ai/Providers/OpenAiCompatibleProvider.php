<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiSchema;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * OpenAI Chat Completions, and the OpenAI-compatible APIs of DeepSeek and Kimi (Moonshot).
 *
 * json_mode "schema": Structured Outputs (response_format json_schema, strict), as on OpenAI.
 * json_mode "object": JSON mode only; the schema is described in the system prompt and the
 * answer is checked locally against it before use.
 */
class OpenAiCompatibleProvider implements AiProvider
{
    public function __construct(
        private string $name,
        private string $apiKey,
        private string $model,
        private string $baseUrl,
        private string $jsonMode,
        private string $maxTokensParam,
    ) {
    }

    public function json(string $system, string $prompt, array $schema, string $effort, int $maxTokens): ProviderResult
    {
        $responseFormat = $this->jsonMode === 'schema'
            ? ['type' => 'json_schema', 'json_schema' => ['name' => 'result', 'strict' => true, 'schema' => $schema]]
            : ['type' => 'json_object'];

        if ($this->jsonMode === 'object') {
            // JSON mode requires the word "json" in the prompt; give the exact shape too.
            $system .= "\n\nRespond with only a JSON object that matches this JSON Schema exactly:\n" . json_encode($schema, JSON_UNESCAPED_UNICODE);
        }

        $response = $this->send('post', 'chat/completions', [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $prompt],
            ],
            'response_format' => $responseFormat,
            $this->maxTokensParam => $maxTokens,
        ]);

        $model = (string) ($response->json('model') ?: $this->model);
        $in = (int) $response->json('usage.prompt_tokens', 0);
        $out = (int) $response->json('usage.completion_tokens', 0);
        $choice = $response->json('choices.0', []);
        $finish = $choice['finish_reason'] ?? null;

        // OpenAI reports a safety refusal in message.refusal; others use finish_reason content_filter.
        if (filled($choice['message']['refusal'] ?? null) || $finish === 'content_filter') {
            return new ProviderResult(null, $model, $in, $out, refused: true, refusalReason: $finish === 'content_filter' ? 'content_filter' : 'refusal');
        }

        $data = json_decode((string) ($choice['message']['content'] ?? ''), true);

        if ($finish === 'length' || ! is_array($data) || ! AiSchema::valid($data, $schema)) {
            throw new ProviderUnavailable("Unusable output from {$this->name} (finish: {$finish})", 'The AI response was incomplete. Please try again.', $in, $out);
        }

        return new ProviderResult($data, $model, $in, $out);
    }

    public function ping(): string
    {
        $response = $this->send('post', 'chat/completions', [
            'model' => $this->model,
            'messages' => [['role' => 'user', 'content' => 'Reply with the single word: OK']],
            $this->maxTokensParam => 256,
        ]);

        return (string) ($response->json('model') ?: $this->model);
    }

    public function models(): array
    {
        return collect($this->send('get', 'models')->json('data', []))->pluck('id')->filter()->sort()->values()->all();
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim($this->baseUrl, '/'))
            ->withToken($this->apiKey)
            ->acceptJson()
            ->timeout(90)
            // Retry once on connection errors and 5xx/429 before failing over.
            ->retry(2, 1000, fn ($e) => $e instanceof ConnectionException || ($e->response?->status() ?? 0) >= 500 || ($e->response?->status() ?? 0) === 429, throw: false);
    }

    private function send(string $method, string $path, array $body = []): Response
    {
        try {
            $response = $method === 'get' ? $this->client()->get($path) : $this->client()->post($path, $body);
        } catch (ConnectionException $e) {
            throw new ProviderUnavailable("{$this->name}: " . $e->getMessage(), previous: $e);
        }

        if ($response->successful()) {
            return $response;
        }

        $status = $response->status();
        $detail = "{$this->name} HTTP {$status}: " . mb_substr((string) ($response->json('error.message') ?? $response->body()), 0, 300);

        throw new ProviderUnavailable($detail, match (true) {
            in_array($status, [401, 403], true) => 'The AI service rejected the platform API key. Please contact support.',
            $status === 404 => 'The configured AI model is not available. Please contact support.',
            $status === 429 => 'The AI service is busy. Please try again in a minute.',
            default => 'Could not reach the AI service. Please try again.',
        });
    }
}
