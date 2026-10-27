<?php

namespace App\Services\Ai;

/**
 * Checks decoded AI output against the subset of JSON Schema the features use (object,
 * array, string, number, integer, enum, required). Providers without schema enforcement
 * (JSON mode only) are only trusted after passing this.
 */
class AiSchema
{
    public static function valid(mixed $value, array $schema): bool
    {
        if (isset($schema['enum']) && ! in_array($value, $schema['enum'], true)) {
            return false;
        }

        return match ($schema['type'] ?? null) {
            'object' => is_array($value) && (array_is_list($value) ? $value === [] && empty($schema['required']) : true) && self::object($value, $schema),
            'array' => is_array($value) && array_is_list($value) && self::items($value, $schema['items'] ?? []),
            'string' => is_string($value),
            'number' => is_int($value) || is_float($value),
            'integer' => is_int($value) || (is_float($value) && floor($value) === $value),
            'boolean' => is_bool($value),
            default => true,
        };
    }

    private static function object(array $value, array $schema): bool
    {
        foreach ($schema['required'] ?? [] as $key) {
            if (! array_key_exists($key, $value)) {
                return false;
            }
        }

        foreach ($schema['properties'] ?? [] as $key => $sub) {
            if (array_key_exists($key, $value) && ! self::valid($value[$key], $sub)) {
                return false;
            }
        }

        return true;
    }

    private static function items(array $value, array $schema): bool
    {
        foreach ($value as $item) {
            if (! self::valid($item, $schema)) {
                return false;
            }
        }

        return true;
    }
}
