<?php

namespace App\Models\Concerns;

/**
 * Values for tenant-defined custom fields, kept as {key: value} in the `custom_values`
 * JSON column. Definitions and validation live in App\Support\CustomFields.
 */
trait HasCustomValues
{
    public function initializeHasCustomValues(): void
    {
        $this->mergeFillable(['custom_values']);
        $this->mergeCasts(['custom_values' => 'array']);
    }
}
