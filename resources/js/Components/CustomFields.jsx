import { usePage } from '@inertiajs/react';
import { Card, CardBody, CardHeader, Checkbox, DescriptionItem, FormField, Input, Select, Textarea } from '@/Components/ui';
import { cn, formatDate } from '@/lib/utils';

/**
 * Tenant-defined custom fields (Settings → Custom fields). `fields` come from the server
 * (App\Support\CustomFields::forForm); values are a {key: value} object stored on the record
 * and submitted as `custom_values`. Server-side errors arrive as `custom_values.<key>`.
 */

/** Form state for `custom_values`: saved values, with checkboxes defaulting to false. */
export function initialCustomValues(fields = [], values) {
    const out = { ...(values ?? {}) };
    fields.forEach((f) => {
        if (out[f.key] === undefined) out[f.key] = f.type === 'checkbox' ? false : '';
    });
    return out;
}

/** Inputs for every field; `onChange(values)` gets the whole updated object. */
export function CustomFieldInputs({ fields = [], values = {}, onChange, errors = {}, className }) {
    if (!fields.length) return null;
    const set = (key, value) => onChange({ ...values, [key]: value });

    return (
        <div className={cn('grid gap-5 md:grid-cols-2', className)}>
            {fields.map((f) => {
                const error = errors[`custom_values.${f.key}`];
                const value = values?.[f.key];

                if (f.type === 'checkbox') {
                    return (
                        <div key={f.key} className="md:col-span-2">
                            <Checkbox label={f.required ? `${f.label} *` : f.label} checked={Boolean(value) && value !== '0'} onChange={(e) => set(f.key, e.target.checked)} />
                            {error ? <p className="mt-1.5 text-sm text-red-600">{error}</p> : null}
                        </div>
                    );
                }

                return (
                    <FormField key={f.key} label={f.label} required={f.required} error={error} className={f.type === 'textarea' ? 'md:col-span-2' : undefined}>
                        {({ id, invalid }) =>
                            f.type === 'textarea' ? (
                                <Textarea id={id} rows={3} value={value ?? ''} onChange={(e) => set(f.key, e.target.value)} invalid={invalid} />
                            ) : f.type === 'select' ? (
                                <Select id={id} value={value ?? ''} onChange={(e) => set(f.key, e.target.value)} invalid={invalid} placeholder="Select…" options={f.options.map((o) => ({ value: o, label: o }))} />
                            ) : (
                                <Input id={id} type={f.type === 'number' ? 'number' : f.type === 'date' ? 'date' : 'text'} step={f.type === 'number' ? 'any' : undefined} value={value ?? ''} onChange={(e) => set(f.key, e.target.value)} invalid={invalid} />
                            )
                        }
                    </FormField>
                );
            })}
        </div>
    );
}

/** A card wrapping the inputs, for forms that are made of cards. */
export function CustomFieldsCard({ fields = [], title = 'Additional details', ...props }) {
    if (!fields.length) return null;
    return (
        <Card>
            <CardHeader title={title} />
            <CardBody>
                <CustomFieldInputs fields={fields} {...props} />
            </CardBody>
        </Card>
    );
}

export function formatCustomValue(field, value, dateFormat) {
    if (value === undefined || value === null || value === '') return null;
    if (field.type === 'checkbox') return value && value !== '0' ? 'Yes' : 'No';
    if (field.type === 'date') return formatDate(value, dateFormat);
    return String(value);
}

/** Label/value pairs for show pages. Renders nothing when no field has a value. */
export function CustomFieldValues({ fields = [], values, className }) {
    const df = usePage().props.auth?.company?.date_format;
    const shown = fields.filter((f) => formatCustomValue(f, values?.[f.key]) !== null);
    if (!shown.length) return null;
    return (
        <dl className={cn('grid gap-x-6 gap-y-4 sm:grid-cols-2', className)}>
            {shown.map((f) => (
                <DescriptionItem key={f.key} label={f.label} className={f.type === 'textarea' ? 'sm:col-span-2' : undefined}>
                    <span className="whitespace-pre-line">{formatCustomValue(f, values[f.key], df)}</span>
                </DescriptionItem>
            ))}
        </dl>
    );
}

/** Show-page card; hidden when nothing is filled in. */
export function CustomFieldValuesCard({ fields = [], values, title = 'Additional details' }) {
    if (!fields.some((f) => formatCustomValue(f, values?.[f.key]) !== null)) return null;
    return (
        <Card>
            <CardHeader title={title} />
            <CardBody>
                <CustomFieldValues fields={fields} values={values} />
            </CardBody>
        </Card>
    );
}

/** Table-cell text for index columns. */
export function CustomCell({ field, values }) {
    const df = usePage().props.auth?.company?.date_format;
    return formatCustomValue(field, values?.[field.key], df) ?? <span className="text-slate-400">—</span>;
}
