import { useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, ExternalLink, Plus, Save, Trash2 } from 'lucide-react';
import { faIcon } from '@/Components/marketing/shared';
import { Button, Card, CardBody, CardHeader, FieldError, FormField, Input, PageHeader, Select, Tabs, Textarea } from '@/Components/ui';
import { route } from '@/lib/route';

const COLORS = { sky: 'Sky', blue: 'Blue', green: 'Green', purple: 'Purple', orange: 'Orange', pink: 'Pink', red: 'Red', teal: 'Teal', indigo: 'Indigo' };

export default function LandingSettings({ settings }) {
    const form = useForm({
        ...settings,
        features_page_json: (settings.features_page_json ?? []).map((f) => ({ ...f, bullets: f.bullets ?? [] })),
    });
    const d = form.data;
    const e = form.errors;

    const text = (name, label, props = {}) => (
        <FormField label={label} error={e[name]} required={props.required} className={props.className} hint={props.hint}>
            {props.textarea ? (
                <Textarea rows={props.rows ?? 2} value={d[name] ?? ''} onChange={(ev) => form.setData(name, ev.target.value)} invalid={Boolean(e[name])} />
            ) : (
                <Input value={d[name] ?? ''} onChange={(ev) => form.setData(name, ev.target.value)} invalid={Boolean(e[name])} />
            )}
        </FormField>
    );

    const listErrors = (name) =>
        Object.entries(e)
            .filter(([k]) => k.startsWith(`${name}.`))
            .map(([, v]) => v)[0];

    const save = () => form.put(route('superadmin.settings.landing.update'), { preserveScroll: true });

    return (
        <>
            <PageHeader
                title="Landing page"
                description="Content of the public marketing site."
                actions={
                    <>
                        <Button href={route('landing')} external target="_blank" variant="secondary" icon={ExternalLink}>
                            View site
                        </Button>
                        <Button icon={Save} loading={form.processing} onClick={save}>
                            Save
                        </Button>
                    </>
                }
            />
            {Object.keys(e).length ? <p className="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">Some fields need attention — check each tab.</p> : null}
            <Tabs
                tabs={[
                    {
                        key: 'hero',
                        label: 'Hero',
                        content: (
                            <Card>
                                <CardBody className="grid gap-4 sm:grid-cols-2">
                                    {text('landing_hero_eyebrow', 'Eyebrow', { className: 'sm:col-span-2', hint: 'Small blue line above the headline.' })}
                                    {text('landing_hero_title', 'Headline', { required: true, className: 'sm:col-span-2' })}
                                    {text('landing_hero_subtitle', 'Subheadline', { required: true, textarea: true, className: 'sm:col-span-2' })}
                                    {text('landing_cta_text', 'Button text', { required: true })}
                                    {text('landing_cta_url', 'Button link', { required: true, hint: '/register or https://…' })}
                                    {text('landing_footer_text', 'Footer blurb', { textarea: true, className: 'sm:col-span-2' })}
                                </CardBody>
                            </Card>
                        ),
                    },
                    {
                        key: 'features',
                        label: 'Features',
                        content: (
                            <div className="space-y-6">
                                <Card>
                                    <CardHeader title="Homepage features section" />
                                    <CardBody className="space-y-4">
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            {text('landing_features_title', 'Title', { required: true })}
                                            {text('landing_features_subtitle', 'Subtitle', { required: true })}
                                        </div>
                                        <ListEditor
                                            items={d.landing_features_json}
                                            onChange={(v) => form.setData('landing_features_json', v)}
                                            blank={{ icon: 'fas fa-star', color: 'indigo', title: '', description: '' }}
                                            render={(item, set) => <FeatureFields item={item} set={set} />}
                                        />
                                        <FieldError>{listErrors('landing_features_json')}</FieldError>
                                    </CardBody>
                                </Card>
                                <Card>
                                    <CardHeader title="/features page" description="Leave the list empty to reuse the homepage features." />
                                    <CardBody className="space-y-4">
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            {text('features_page_title', 'Title')}
                                            {text('features_page_subtitle', 'Subtitle')}
                                            {text('features_page_cta_title', 'Closing CTA title')}
                                            {text('features_page_cta_subtitle', 'Closing CTA subtitle', { hint: '{site_name} is replaced with your site name.' })}
                                        </div>
                                        <ListEditor
                                            items={d.features_page_json}
                                            onChange={(v) => form.setData('features_page_json', v)}
                                            blank={{ icon: 'fas fa-star', color: 'indigo', title: '', description: '', bullets: [] }}
                                            render={(item, set) => (
                                                <>
                                                    <FeatureFields item={item} set={set} />
                                                    <FormField label="Bullet points (one per line)" className="sm:col-span-2">
                                                        <Textarea rows={3} value={(item.bullets ?? []).join('\n')} onChange={(ev) => set({ bullets: ev.target.value.split('\n') })} />
                                                    </FormField>
                                                </>
                                            )}
                                        />
                                        <FieldError>{listErrors('features_page_json')}</FieldError>
                                    </CardBody>
                                </Card>
                            </div>
                        ),
                    },
                    {
                        key: 'social',
                        label: 'Social proof',
                        content: (
                            <div className="space-y-6">
                                <Card>
                                    <CardHeader title="Trust bar" />
                                    <CardBody className="space-y-4">
                                        {text('landing_trust_text', 'Caption')}
                                        <ListEditor
                                            items={d.landing_trust_badges_json}
                                            onChange={(v) => form.setData('landing_trust_badges_json', v)}
                                            blank={{ name: '' }}
                                            compact
                                            render={(item, set) => <Input value={item.name ?? ''} onChange={(ev) => set({ name: ev.target.value })} placeholder="Company name" className="sm:col-span-2" />}
                                        />
                                        <FieldError>{listErrors('landing_trust_badges_json')}</FieldError>
                                    </CardBody>
                                </Card>
                                <Card>
                                    <CardHeader title="Testimonials" />
                                    <CardBody className="space-y-4">
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            {text('landing_testimonials_title', 'Title', { required: true })}
                                            {text('landing_testimonials_subtitle', 'Subtitle', { required: true })}
                                        </div>
                                        <ListEditor
                                            items={d.landing_testimonials_json}
                                            onChange={(v) => form.setData('landing_testimonials_json', v)}
                                            blank={{ name: '', role: '', initials: '', color: 'sky', quote: '' }}
                                            render={(item, set) => (
                                                <>
                                                    <FormField label="Name">
                                                        <Input value={item.name ?? ''} onChange={(ev) => set({ name: ev.target.value })} />
                                                    </FormField>
                                                    <FormField label="Role">
                                                        <Input value={item.role ?? ''} onChange={(ev) => set({ role: ev.target.value })} />
                                                    </FormField>
                                                    <FormField label="Initials">
                                                        <Input maxLength={4} value={item.initials ?? ''} onChange={(ev) => set({ initials: ev.target.value })} />
                                                    </FormField>
                                                    <FormField label="Accent">
                                                        <Select value={item.color ?? 'sky'} onChange={(ev) => set({ color: ev.target.value })} options={COLORS} />
                                                    </FormField>
                                                    <FormField label="Quote" className="sm:col-span-2">
                                                        <Textarea rows={2} value={item.quote ?? ''} onChange={(ev) => set({ quote: ev.target.value })} />
                                                    </FormField>
                                                </>
                                            )}
                                        />
                                        <FieldError>{listErrors('landing_testimonials_json')}</FieldError>
                                    </CardBody>
                                </Card>
                            </div>
                        ),
                    },
                    {
                        key: 'pricing',
                        label: 'Pricing',
                        content: (
                            <Card>
                                <CardHeader title="Pricing section" description="Plans themselves are managed under Plans." />
                                <CardBody className="grid gap-4 sm:grid-cols-2">
                                    {text('landing_pricing_title', 'Title', { required: true })}
                                    {text('landing_pricing_subtitle', 'Subtitle', { required: true })}
                                </CardBody>
                            </Card>
                        ),
                    },
                ]}
            />
        </>
    );
}

function FeatureFields({ item, set }) {
    const Icon = faIcon(item.icon);
    return (
        <>
            <FormField label="Title">
                <Input value={item.title ?? ''} onChange={(ev) => set({ title: ev.target.value })} />
            </FormField>
            <div className="grid grid-cols-[1fr_120px] gap-3">
                <FormField label="Icon" hint="Font Awesome class">
                    <Input value={item.icon ?? ''} onChange={(ev) => set({ icon: ev.target.value })} prefix={<Icon className="size-4" />} />
                </FormField>
                <FormField label="Accent">
                    <Select value={item.color ?? 'indigo'} onChange={(ev) => set({ color: ev.target.value })} options={COLORS} />
                </FormField>
            </div>
            <FormField label="Description" className="sm:col-span-2">
                <Textarea rows={2} value={item.description ?? ''} onChange={(ev) => set({ description: ev.target.value })} />
            </FormField>
        </>
    );
}

/** Reorderable list of objects, each edited with `render(item, patch)`. */
function ListEditor({ items = [], onChange, blank, render, compact = false }) {
    const update = (i, patch) => onChange(items.map((x, k) => (k === i ? { ...x, ...patch } : x)));
    const move = (i, dir) => {
        const j = i + dir;
        if (j < 0 || j >= items.length) return;
        const next = [...items];
        [next[i], next[j]] = [next[j], next[i]];
        onChange(next);
    };
    return (
        <div className="space-y-3">
            {items.map((item, i) => (
                <div key={i} className={compact ? 'flex items-center gap-2' : 'rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200'}>
                    <div className={compact ? 'flex-1' : 'grid gap-4 sm:grid-cols-2'}>{render(item, (patch) => update(i, patch))}</div>
                    <div className={compact ? 'flex' : 'mt-3 flex justify-end gap-1'}>
                        <button type="button" onClick={() => move(i, -1)} disabled={i === 0} className="rounded p-1 text-slate-400 hover:text-slate-700 disabled:opacity-30" aria-label="Move up">
                            <ArrowUp className="size-4" />
                        </button>
                        <button type="button" onClick={() => move(i, 1)} disabled={i === items.length - 1} className="rounded p-1 text-slate-400 hover:text-slate-700 disabled:opacity-30" aria-label="Move down">
                            <ArrowDown className="size-4" />
                        </button>
                        <button type="button" onClick={() => onChange(items.filter((_, k) => k !== i))} className="rounded p-1 text-slate-400 hover:text-red-600" aria-label="Remove">
                            <Trash2 className="size-4" />
                        </button>
                    </div>
                </div>
            ))}
            <Button size="xs" variant="soft" icon={Plus} onClick={() => onChange([...items, { ...blank }])}>
                Add
            </Button>
        </div>
    );
}
