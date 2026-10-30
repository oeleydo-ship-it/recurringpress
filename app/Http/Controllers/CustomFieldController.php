<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Settings → Custom fields. Owners and admins manage per-entity field definitions for
 * their company (route group is role:owner,admin; the model's global scope keeps every
 * query on the current company).
 */
class CustomFieldController extends Controller
{
    /** Props for the Settings page tab. */
    public static function payload(): array
    {
        return [
            'fields' => CustomField::orderBy('entity')->orderBy('sort_order')->orderBy('id')->get()
                ->map(fn (CustomField $f) => [
                    ...$f->only(['id', 'entity', 'label', 'key', 'type', 'is_required', 'sort_order', 'is_active', 'show_on_pdf', 'show_in_list']),
                    'options' => array_values($f->options ?? []),
                ])->values(),
            'entities' => CustomField::entities(),
            'documentEntities' => CustomField::documentEntities(),
            'types' => CustomField::types(),
        ];
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        CustomField::create([...$data, 'company_id' => auth()->user()->company_id]);

        return back()->with('success', 'Custom field added.');
    }

    public function update(Request $request, CustomField $customField)
    {
        // The key is fixed once created: stored values are keyed by it.
        $customField->update($this->validated($request, $customField));

        return back()->with('success', 'Custom field updated.');
    }

    public function destroy(CustomField $customField)
    {
        // Stored values stay on the records (harmless, and restored if the key is re-added).
        $customField->delete();

        return back()->with('success', 'Custom field deleted.');
    }

    private function validated(Request $request, ?CustomField $existing = null): array
    {
        $companyId = auth()->user()->company_id;

        if (! $existing && blank($request->input('key')) && filled($request->input('label'))) {
            $request->merge(['key' => Str::limit(Str::slug($request->input('label'), '_'), 64, '')]);
        }

        $options = collect($request->input('options', []))
            ->map(fn ($o) => is_string($o) ? trim($o) : $o)->filter(fn ($o) => filled($o))->values()->all();
        $request->merge(['options' => $options]);

        $rules = [
            'label' => 'required|string|max:100',
            'type' => ['required', Rule::in(array_keys(CustomField::types()))],
            'options' => ['array', 'max:50', Rule::requiredIf($request->input('type') === CustomField::TYPE_SELECT)],
            'options.*' => 'string|max:100|distinct',
            'is_required' => 'boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'boolean',
            'show_on_pdf' => 'boolean',
            'show_in_list' => 'boolean',
        ];

        if (! $existing) {
            $rules['entity'] = ['required', Rule::in(array_keys(CustomField::entities()))];
            $rules['key'] = [
                'required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('custom_fields')->where(fn ($q) => $q->where('company_id', $companyId)->where('entity', $request->input('entity'))),
            ];
        }

        $data = $request->validate($rules, [
            'key.regex' => 'The key must start with a letter and use only lowercase letters, numbers and underscores.',
            'options.required' => 'Add at least one option for a select field.',
        ]);

        $entity = $existing->entity ?? $data['entity'];
        $data['options'] = $data['type'] === CustomField::TYPE_SELECT ? array_values($data['options'] ?? []) : null;
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        foreach (['is_required', 'is_active', 'show_on_pdf', 'show_in_list'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }
        if (! in_array($entity, CustomField::documentEntities(), true)) {
            $data['show_on_pdf'] = false;
        }

        return $data;
    }
}
