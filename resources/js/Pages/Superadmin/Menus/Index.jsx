import { useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Plus, Save, Trash2 } from 'lucide-react';
import { Button, Card, CardBody, CardHeader, FieldError, Input, PageHeader } from '@/Components/ui';
import { route } from '@/lib/route';

export default function SuperadminMenus({ locations, menus, suggestions }) {
    const form = useForm({ menus });

    const setItems = (loc, items) => form.setData('menus', { ...form.data.menus, [loc]: items });
    const move = (loc, i, d) => {
        const items = [...form.data.menus[loc]];
        const j = i + d;
        if (j < 0 || j >= items.length) return;
        [items[i], items[j]] = [items[j], items[i]];
        setItems(loc, items);
    };

    return (
        <>
            <PageHeader
                title="Navigation menus"
                description="Links shown in the public site header and footer."
                actions={
                    <Button icon={Save} loading={form.processing} onClick={() => form.put(route('superadmin.menus.update'), { preserveScroll: true })}>
                        Save menus
                    </Button>
                }
            />
            <div className="grid gap-6 lg:grid-cols-2">
                {Object.entries(locations).map(([loc, label]) => {
                    const items = form.data.menus[loc] ?? [];
                    return (
                        <Card key={loc}>
                            <CardHeader title={label} description={`${items.length} link${items.length === 1 ? '' : 's'}`} />
                            <CardBody className="space-y-2">
                                {items.map((item, i) => (
                                    <div key={i}>
                                        <div className="flex items-center gap-2">
                                            <Input value={item.label} onChange={(e) => setItems(loc, items.map((x, k) => (k === i ? { ...x, label: e.target.value } : x)))} placeholder="Label" className="w-36 shrink-0" aria-label="Label" />
                                            <Input
                                                value={item.url}
                                                onChange={(e) => setItems(loc, items.map((x, k) => (k === i ? { ...x, url: e.target.value } : x)))}
                                                placeholder="/pricing or https://…"
                                                className="font-mono text-xs"
                                                invalid={Boolean(form.errors[`menus.${loc}.${i}.url`])}
                                                aria-label="URL"
                                            />
                                            <button type="button" onClick={() => move(loc, i, -1)} disabled={i === 0} className="rounded p-1 text-slate-400 hover:text-slate-700 disabled:opacity-30" aria-label="Move up">
                                                <ArrowUp className="size-4" />
                                            </button>
                                            <button type="button" onClick={() => move(loc, i, 1)} disabled={i === items.length - 1} className="rounded p-1 text-slate-400 hover:text-slate-700 disabled:opacity-30" aria-label="Move down">
                                                <ArrowDown className="size-4" />
                                            </button>
                                            <button type="button" onClick={() => setItems(loc, items.filter((_, k) => k !== i))} className="rounded p-1 text-slate-400 hover:text-red-600" aria-label="Remove">
                                                <Trash2 className="size-4" />
                                            </button>
                                        </div>
                                        <FieldError className="text-xs">{form.errors[`menus.${loc}.${i}.url`] ?? form.errors[`menus.${loc}.${i}.label`]}</FieldError>
                                    </div>
                                ))}
                                <div className="flex flex-wrap items-center gap-2 pt-2">
                                    <Button size="xs" variant="soft" icon={Plus} onClick={() => setItems(loc, [...items, { label: '', url: '/' }])}>
                                        Add link
                                    </Button>
                                    <select
                                        className="h-7 rounded-md border-0 bg-white py-0 pr-8 pl-2 text-xs ring-1 ring-slate-300"
                                        value=""
                                        onChange={(e) => {
                                            const s = suggestions[Number(e.target.value)];
                                            if (s) setItems(loc, [...items, s]);
                                        }}
                                        aria-label="Add a page"
                                    >
                                        <option value="">Add a page…</option>
                                        {suggestions.map((s, i) => (
                                            <option key={s.url} value={i}>
                                                {s.label} ({s.url})
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </CardBody>
                        </Card>
                    );
                })}
            </div>
        </>
    );
}

