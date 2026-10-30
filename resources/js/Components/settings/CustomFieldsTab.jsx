import { router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Badge, Button, Card, CardBody, CardHeader, FormField, Input, Modal, Segmented, Select, Switch, Table, TBody, TD, TH, THead, TR, Textarea, useConfirm } from '@/Components/ui';
import { route } from '@/lib/route';

/** Settings → Custom fields: per-entity field definitions for this company. */
export default function CustomFieldsTab({ customFields }) {
    const { fields, entities, documentEntities, types } = customFields;
    const [entity, setEntity] = useState('invoice');
    const [editing, setEditing] = useState(null); // null | 'new' | field
    const confirm = useConfirm();
    const list = fields.filter((f) => f.entity === entity);

    const remove = async (field) => {
        if (await confirm({ title: `Delete “${field.label}”?`, message: 'The field disappears from forms and documents. Values already saved stay on the records and come back if you re-add the same key.', confirmText: 'Delete' })) {
            router.delete(route('settings.custom-fields.destroy', field.id), { preserveScroll: true });
        }
    };

    return (
        <Card>
            <CardHeader
                title="Custom fields"
                description="Extra fields on your forms, records, lists and documents."
                actions={
                    <Button icon={Plus} onClick={() => setEditing('new')}>
                        Add field
                    </Button>
                }
            />
            <CardBody className="space-y-4">
                <div className="overflow-x-auto scrollbar-thin">
                    <Segmented value={entity} onChange={setEntity} options={Object.entries(entities).map(([value, label]) => ({ value, label: `${label} (${fields.filter((f) => f.entity === value).length})` }))} />
                </div>
                {list.length ? (
                    <Table className="-mx-5">
                        <THead>
                            <TH>Label</TH>
                            <TH>Key</TH>
                            <TH>Type</TH>
                            <TH>Shown</TH>
                            <TH align="right">Order</TH>
                            <TH align="right">
                                <span className="sr-only">Actions</span>
                            </TH>
                        </THead>
                        <TBody>
                            {list.map((f) => (
                                <TR key={f.id} className={f.is_active ? '' : 'opacity-60'}>
                                    <TD className="font-medium text-slate-900">
                                        {f.label}
                                        {f.is_required ? <span className="ml-0.5 text-red-500">*</span> : null}
                                        {!f.is_active ? <Badge className="ml-2">Inactive</Badge> : null}
                                    </TD>
                                    <TD className="font-mono text-xs">{f.key}</TD>
                                    <TD>
                                        {types[f.type]}
                                        {f.type === 'select' ? <span className="ml-1 text-xs text-slate-500">({f.options.length})</span> : null}
                                    </TD>
                                    <TD className="space-x-1">
                                        {f.show_on_pdf ? <Badge tone="brand">PDF</Badge> : null}
                                        {f.show_in_list ? <Badge tone="blue">List</Badge> : null}
                                    </TD>
                                    <TD align="right">{f.sort_order}</TD>
                                    <TD align="right" className="space-x-1">
                                        <Button variant="ghost" size="icon-sm" icon={Pencil} onClick={() => setEditing(f)} aria-label={`Edit ${f.label}`} />
                                        <Button variant="ghost" size="icon-sm" icon={Trash2} onClick={() => remove(f)} aria-label={`Delete ${f.label}`} />
                                    </TD>
                                </TR>
                            ))}
                        </TBody>
                    </Table>
                ) : (
                    <p className="rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">No custom fields for {entities[entity].toLowerCase()} yet.</p>
                )}
            </CardBody>
            {editing ? (
                <FieldModal
                    key={editing === 'new' ? `new-${entity}` : editing.id}
                    field={editing === 'new' ? null : editing}
                    entity={editing === 'new' ? entity : editing.entity}
                    entities={entities}
                    types={types}
                    isDocument={documentEntities.includes(editing === 'new' ? entity : editing.entity)}
                    nextOrder={(list.reduce((m, f) => Math.max(m, f.sort_order), 0) || 0) + 10}
                    onClose={() => setEditing(null)}
                />
            ) : null}
        </Card>
    );
}

const slugify = (s) =>
    s
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^[^a-z]+|_+$/g, '')
        .slice(0, 64);

function FieldModal({ field, entity, entities, types, isDocument, nextOrder, onClose }) {
    const [keyTouched, setKeyTouched] = useState(false);
    const form = useForm({
        entity,
        label: field?.label ?? '',
        key: field?.key ?? '',
        type: field?.type ?? 'text',
        options: field?.options?.join('\n') ?? '',
        is_required: field?.is_required ?? false,
        sort_order: field?.sort_order ?? nextOrder,
        is_active: field?.is_active ?? true,
        show_on_pdf: field?.show_on_pdf ?? false,
        show_in_list: field?.show_in_list ?? false,
    });
    const d = form.data;
    const optionsError = form.errors.options ?? Object.entries(form.errors).find(([k]) => k.startsWith('options.'))?.[1];

    const submit = (e) => {
        e.preventDefault();
        form.transform((data) => ({ ...data, options: data.type === 'select' ? data.options.split('\n') : [] }));
        const opts = { preserveScroll: true, onSuccess: onClose };
        if (field) form.put(route('settings.custom-fields.update', field.id), opts);
        else form.post(route('settings.custom-fields.store'), opts);
    };

    return (
        <Modal
            open
            onClose={onClose}
            title={field ? `Edit “${field.label}”` : `New field for ${entities[entity].toLowerCase()}`}
            footer={
                <>
                    <Button variant="secondary" onClick={onClose}>
                        Cancel
                    </Button>
                    <Button type="submit" form="custom-field-form" loading={form.processing}>
                        {field ? 'Save field' : 'Add field'}
                    </Button>
                </>
            }
        >
            <form id="custom-field-form" onSubmit={submit} noValidate className="space-y-4">
                <div className="grid gap-4 sm:grid-cols-2">
                    <FormField label="Label" required error={form.errors.label}>
                        <Input
                            value={d.label}
                            autoFocus
                            onChange={(e) => {
                                const label = e.target.value;
                                form.setData((x) => ({ ...x, label, key: field || keyTouched ? x.key : slugify(label) }));
                            }}
                            invalid={Boolean(form.errors.label)}
                            placeholder="e.g. PO number"
                        />
                    </FormField>
                    <FormField label="Key" required error={form.errors.key} hint={field ? 'The key can’t change once created.' : 'Lowercase letters, numbers and underscores.'}>
                        <Input
                            value={d.key}
                            disabled={Boolean(field)}
                            onChange={(e) => {
                                setKeyTouched(true);
                                form.setData('key', e.target.value);
                            }}
                            className="font-mono"
                            invalid={Boolean(form.errors.key)}
                        />
                    </FormField>
                    <FormField label="Type" required error={form.errors.type}>
                        <Select value={d.type} onChange={(e) => form.setData('type', e.target.value)} options={types} />
                    </FormField>
                    <FormField label="Sort order" error={form.errors.sort_order} hint="Lower numbers come first.">
                        <Input type="number" min="0" value={d.sort_order} onChange={(e) => form.setData('sort_order', e.target.value)} />
                    </FormField>
                </div>
                {d.type === 'select' ? (
                    <FormField label="Options" required error={optionsError} hint="One option per line.">
                        <Textarea rows={4} value={d.options} onChange={(e) => form.setData('options', e.target.value)} invalid={Boolean(optionsError)} />
                    </FormField>
                ) : null}
                <div className="space-y-3 rounded-lg bg-slate-50 p-4">
                    <Switch label="Required" description={d.type === 'checkbox' ? 'The box must be ticked to save.' : 'Must be filled in to save.'} checked={d.is_required} onChange={(v) => form.setData('is_required', v)} />
                    <Switch label="Active" description="Inactive fields are hidden everywhere; saved values are kept." checked={d.is_active} onChange={(v) => form.setData('is_active', v)} />
                    {isDocument ? <Switch label="Show on PDF / client view" description="Printed on the document and the client’s link." checked={d.show_on_pdf} onChange={(v) => form.setData('show_on_pdf', v)} /> : null}
                    <Switch label="Show in list" description="Adds a column to the list page." checked={d.show_in_list} onChange={(v) => form.setData('show_in_list', v)} />
                </div>
            </form>
        </Modal>
    );
}
