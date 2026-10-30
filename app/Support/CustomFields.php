<?php

namespace App\Support;

use App\Models\CustomField;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Tenant-defined custom fields: definitions (App\Models\CustomField) and the values each
 * record stores in its `custom_values` JSON column.
 *
 * Definitions are always looked up by explicit company id without the auth-based global
 * scope, so this also works for public client links, queued PDF jobs and the AI tools.
 */
class CustomFields
{
    /** Active definitions for one entity, in display order. */
    public static function definitions(string $entity, ?int $companyId = null, bool $activeOnly = true): Collection
    {
        $companyId ??= auth()->user()?->company_id;
        if (! $companyId) {
            return collect();
        }

        return CustomField::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('entity', $entity)
            ->when($activeOnly, fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')->orderBy('id')
            ->get();
    }

    /** Definitions shaped for the shared React component. */
    public static function forForm(string $entity, ?int $companyId = null): array
    {
        return self::definitions($entity, $companyId)->map(fn (CustomField $f) => [
            'key' => $f->key,
            'label' => $f->label,
            'type' => $f->type,
            'options' => array_values($f->options ?? []),
            'required' => $f->is_required,
            'show_in_list' => $f->show_in_list,
        ])->values()->all();
    }

    /** Fields shown as index-list columns. */
    public static function listColumns(string $entity, ?int $companyId = null): array
    {
        return array_values(array_filter(self::forForm($entity, $companyId), fn ($f) => $f['show_in_list']));
    }

    /**
     * Validate `custom_values` from the request against the entity's active definitions and
     * return the cleaned values to store. Unknown keys are dropped; values of deactivated
     * fields already on the record are kept so switching a field off doesn't erase data.
     */
    public static function validate(Request $request, string $entity, ?array $existing = null): array
    {
        $all = self::definitions($entity, null, false);
        $active = $all->where('is_active', true);

        $input = $request->input('custom_values');
        $input = is_array($input) ? $input : [];

        $rules = [];
        $attributes = [];
        foreach ($active as $field) {
            $rules["custom_values.{$field->key}"] = self::rulesFor($field);
            $attributes["custom_values.{$field->key}"] = $field->label;
        }

        Validator::make(['custom_values' => $input], $rules, [], $attributes)->validate();

        $values = [];
        foreach ($all->where('is_active', false) as $field) {
            if (is_array($existing) && array_key_exists($field->key, $existing)) {
                $values[$field->key] = $existing[$field->key];
            }
        }
        foreach ($active as $field) {
            $value = self::cast($field, $input[$field->key] ?? null);
            if ($value !== null) {
                $values[$field->key] = $value;
            }
        }

        return $values;
    }

    /**
     * Run the form's own validation and the custom-field validation together, so the user
     * sees every error at once. Returns [main result, cleaned custom values].
     */
    public static function validateWith(Request $request, string $entity, callable $main, ?array $existing = null): array
    {
        $errors = [];
        $result = null;
        $custom = [];

        try {
            $result = $main();
        } catch (ValidationException $e) {
            $errors = $e->errors();
        }

        try {
            $custom = self::validate($request, $entity, $existing);
        } catch (ValidationException $e) {
            $errors = array_merge($errors, $e->errors());
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return [$result, $custom];
    }

    private static function rulesFor(CustomField $field): array
    {
        $presence = $field->is_required ? 'required' : 'nullable';

        return match ($field->type) {
            CustomField::TYPE_TEXTAREA => [$presence, 'string', 'max:5000'],
            CustomField::TYPE_NUMBER => [$presence, 'numeric', 'between:-999999999999,999999999999'],
            CustomField::TYPE_DATE => [$presence, 'date_format:Y-m-d'],
            CustomField::TYPE_SELECT => [$presence, 'string', Rule::in($field->options ?? [])],
            CustomField::TYPE_CHECKBOX => $field->is_required ? ['accepted'] : ['nullable', 'boolean'],
            default => [$presence, 'string', 'max:255'],
        };
    }

    /** Normalise a validated input value for storage; null means "not set". */
    private static function cast(CustomField $field, mixed $value): mixed
    {
        if ($field->type === CustomField::TYPE_CHECKBOX) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }

        return match ($field->type) {
            CustomField::TYPE_NUMBER => $value + 0,
            default => is_string($value) ? trim($value) : (string) $value,
        };
    }

    /**
     * Labelled, formatted values for display: [['key', 'label', 'value'], …].
     * $pdfOnly limits to fields marked "show on PDF / client view".
     */
    public static function display(string $entity, ?array $values, ?int $companyId, bool $pdfOnly = false, ?Collection $definitions = null): array
    {
        if (! $values) {
            return [];
        }

        $definitions ??= self::definitions($entity, $companyId);

        return $definitions
            ->when($pdfOnly, fn ($c) => $c->where('show_on_pdf', true))
            ->filter(fn (CustomField $f) => array_key_exists($f->key, $values) && $values[$f->key] !== null && $values[$f->key] !== '')
            ->map(fn (CustomField $f) => [
                'key' => $f->key,
                'label' => $f->label,
                'value' => self::format($f, $values[$f->key]),
            ])->values()->all();
    }

    /** {label: formatted value} for exports and the AI tools. */
    public static function labelled(string $entity, ?array $values, ?int $companyId, ?Collection $definitions = null): array
    {
        return collect(self::display($entity, $values, $companyId, false, $definitions))
            ->mapWithKeys(fn ($r) => [$r['label'] => $r['value']])->all();
    }

    /** CSV cells for the given definitions, formula-neutralised like the rest of the export. */
    public static function csvRow(Collection $definitions, ?array $values): array
    {
        return $definitions->map(function (CustomField $f) use ($values) {
            if (! isset($values[$f->key]) || $values[$f->key] === '') {
                return '';
            }
            $cell = self::format($f, $values[$f->key]);

            return preg_match('/^[=+\-@\t\r]/', $cell) ? "'" . $cell : $cell;
        })->all();
    }

    public static function format(CustomField $field, mixed $value): string
    {
        return match ($field->type) {
            CustomField::TYPE_CHECKBOX => $value ? 'Yes' : 'No',
            CustomField::TYPE_DATE => self::formatDate($value),
            default => (string) $value,
        };
    }

    private static function formatDate(mixed $value): string
    {
        try {
            return Carbon::createFromFormat('Y-m-d', (string) $value)->format('M d, Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    /**
     * Values to copy onto another entity (quotation → invoice, recurring → invoice): only
     * keys that the target entity also defines (and has active) are kept.
     */
    public static function carryOver(?array $values, string $toEntity, int $companyId): ?array
    {
        if (! $values) {
            return null;
        }

        $keys = self::definitions($toEntity, $companyId)->pluck('key')->all();
        $copied = array_intersect_key($values, array_flip($keys));

        return $copied ?: null;
    }
}
