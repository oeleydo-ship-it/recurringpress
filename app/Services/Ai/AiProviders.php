<?php

namespace App\Services\Ai;

use App\Models\SystemSetting;
use App\Services\Ai\Providers\AiProvider;
use App\Services\Ai\Providers\AnthropicProvider;
use App\Services\Ai\Providers\OpenAiCompatibleProvider;
use Illuminate\Support\Facades\Crypt;

/**
 * The AI vendors the platform can use, and their settings (Superadmin → AI). Each provider
 * has its own encrypted API key, model, on/off switch and optional prices for the cost
 * estimate; one is the default and the others are tried in order when it fails.
 */
class AiProviders
{
    public const PROVIDERS = [
        'anthropic' => [
            'label' => 'Anthropic (Claude)',
            'console' => 'console.anthropic.com → API keys',
            'default_model' => 'claude-opus-5-5',
            // USD per million tokens [input, output] for known Claude models.
            'prices' => [
                'claude-opus-5-5' => [4.00, 20.00],
                'claude-sonnet-5-5' => [2.00, 10.00],
                'claude-haiku-5-5' => [0.10, 0.50],
            ],
        ],
        'openai' => [
            'label' => 'OpenAI',
            'console' => 'platform.openai.com → API keys',
            'base_url' => 'https://api.openai.com/v1',
            'json_mode' => 'schema', // Structured Outputs (json_schema, strict)
            'max_tokens_param' => 'max_completion_tokens',
        ],
        'deepseek' => [
            'label' => 'DeepSeek',
            'console' => 'platform.deepseek.com → API keys',
            'base_url' => 'https://api.deepseek.com',
            'json_mode' => 'object', // JSON mode; schema given in the prompt, checked locally
            'max_tokens_param' => 'max_tokens',
            'default_model' => 'deepseek-chat',
            'note' => 'Requests are processed by DeepSeek (China).',
        ],
        'kimi' => [
            'label' => 'Kimi (Moonshot AI)',
            'console' => 'platform.moonshot.ai → API keys',
            'base_url' => 'https://api.moonshot.ai/v1',
            'base_url_cn' => 'https://api.moonshot.cn/v1',
            'json_mode' => 'object',
            'max_tokens_param' => 'max_tokens',
            'note' => 'Requests are processed by Moonshot AI. Keys from platform.moonshot.cn need the China endpoint.',
        ],
    ];

    /** Claude models offered in the model list even before "Load models" is used. */
    public const ANTHROPIC_MODELS = ['claude-opus-5-5', 'claude-sonnet-5-5', 'claude-haiku-5-5'];

    public static function exists(string $name): bool
    {
        return array_key_exists($name, self::PROVIDERS);
    }

    /* ------------------------------ Settings ------------------------------ */

    public static function key(string $name): ?string
    {
        // ai_api_key: where the Claude key was stored before multiple providers existed.
        $stored = SystemSetting::get("ai_{$name}_key") ?? ($name === 'anthropic' ? SystemSetting::get('ai_api_key') : null);

        if (! $stored) {
            return null;
        }

        try {
            return Crypt::decryptString($stored);
        } catch (\Throwable) {
            return null; // APP_KEY changed: the key has to be saved again
        }
    }

    public static function storeKey(string $name, string $key): void
    {
        SystemSetting::set("ai_{$name}_key", Crypt::encryptString($key));
    }

    public static function model(string $name): ?string
    {
        return SystemSetting::get("ai_{$name}_model")
            ?? ($name === 'anthropic' ? SystemSetting::get('ai_model') : null)
            ?? (self::PROVIDERS[$name]['default_model'] ?? null);
    }

    public static function isOn(string $name): bool
    {
        $flag = SystemSetting::get("ai_{$name}_enabled");

        // Before multiple providers, a saved Claude key meant Claude was in use.
        if ($flag === null && $name === 'anthropic') {
            return filled(SystemSetting::get('ai_api_key'));
        }

        return filter_var($flag, FILTER_VALIDATE_BOOL);
    }

    /** Switched on, with a key and a model. */
    public static function usable(string $name): bool
    {
        return self::exists($name) && self::isOn($name) && filled(self::key($name)) && filled(self::model($name));
    }

    public static function defaultProvider(): string
    {
        $default = SystemSetting::get('ai_default_provider');

        return self::exists((string) $default) ? $default : 'anthropic';
    }

    public static function failover(): bool
    {
        return filter_var(SystemSetting::get('ai_failover') ?? true, FILTER_VALIDATE_BOOL);
    }

    /** @return string[] all providers, in the saved failover order */
    public static function order(): array
    {
        $saved = array_values(array_filter(explode(',', (string) SystemSetting::get('ai_provider_order')), fn ($p) => self::exists($p)));

        return array_values(array_unique([...$saved, ...array_keys(self::PROVIDERS)]));
    }

    /** @return string[] usable providers to try for one request: default first, then the failover order */
    public static function chain(): array
    {
        $default = self::defaultProvider();
        $chain = self::failover() ? [$default, ...self::order()] : [$default];

        return array_values(array_filter(array_unique($chain), fn ($p) => self::usable($p)));
    }

    public static function kimiRegion(): string
    {
        return SystemSetting::get('ai_kimi_region') === 'cn' ? 'cn' : 'intl';
    }

    /** USD per million tokens [input, output]; [0, 0] when unknown. */
    public static function prices(string $name, string $model): array
    {
        if ($known = self::PROVIDERS[$name]['prices'][$model] ?? null) {
            return $known;
        }

        return [(float) SystemSetting::get("ai_{$name}_price_in"), (float) SystemSetting::get("ai_{$name}_price_out")];
    }

    public static function make(string $name): AiProvider
    {
        $config = self::PROVIDERS[$name];
        $key = (string) self::key($name);
        $model = (string) self::model($name);

        if ($name === 'anthropic') {
            return new AnthropicProvider($key, $model, config('services.ai.anthropic_base_url') ?: null);
        }

        $base = config("services.ai.{$name}_base_url")
            ?: ($name === 'kimi' && self::kimiRegion() === 'cn' ? $config['base_url_cn'] : $config['base_url']);

        return new OpenAiCompatibleProvider($name, $key, $model, $base, $config['json_mode'], $config['max_tokens_param']);
    }
}
