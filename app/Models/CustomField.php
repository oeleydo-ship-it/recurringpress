<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A tenant-defined field for one entity (invoice, quotation, client, …). Values are stored
 * on the record itself in its `custom_values` JSON column; see App\Support\CustomFields.
 */
class CustomField extends Model
{
    protected $fillable = [
        'company_id',
        'entity',
        'label',
        'key',
        'type',
        'options',
        'is_required',
        'sort_order',
        'is_active',
        'show_on_pdf',
        'show_in_list',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'show_on_pdf' => 'boolean',
        'show_in_list' => 'boolean',
    ];

    const TYPE_TEXT = 'text';
    const TYPE_TEXTAREA = 'textarea';
    const TYPE_NUMBER = 'number';
    const TYPE_DATE = 'date';
    const TYPE_SELECT = 'select';
    const TYPE_CHECKBOX = 'checkbox';

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('custom_fields.company_id', auth()->user()->company_id);
            }
        });
    }

    /** Entity key => label. "show on PDF" only applies to the document entities. */
    public static function entities(): array
    {
        return [
            'invoice' => 'Invoices',
            'recurring_invoice' => 'Recurring invoices',
            'quotation' => 'Quotations',
            'client' => 'Clients',
            'product' => 'Products',
            'expense' => 'Expenses',
        ];
    }

    public static function documentEntities(): array
    {
        return ['invoice', 'recurring_invoice', 'quotation'];
    }

    public static function types(): array
    {
        return [
            self::TYPE_TEXT => 'Text',
            self::TYPE_TEXTAREA => 'Text area',
            self::TYPE_NUMBER => 'Number',
            self::TYPE_DATE => 'Date',
            self::TYPE_SELECT => 'Select (dropdown)',
            self::TYPE_CHECKBOX => 'Checkbox',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
