import { router, useForm } from '@inertiajs/react';
import { AlertTriangle, ArrowDown, ArrowUp, List, Plug, Save } from 'lucide-react';
import { useState } from 'react';
import { Badge, Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Select, Switch } from '@/Components/ui';
import { postJson } from '@/lib/http';
import { route } from '@/lib/route';
import { formatDateTime } from '@/lib/utils';

const CUSTOM = '__custom__';

const FEATURES = { insights: 'Business insights', reminder: 'Reminder drafts', line_items: 'Line-item writer', connection_test: 'Connection tests' };

export default function AiSettings({ settings, providers, usage }) {
    const labels = Object.fromEntries(providers.map((p) => [p.name, p.label]));
    const form = useForm({
        ...settings,
        providers: Object.fromEntries(
            providers.map((p) => [p.name, { enabled: p.enabled, key: '', model: p.model ?? '', price_in: p.price_in ?? '', price_out: p.price_out ?? '' }]),
        ),
    });
    const d = form.data;
    const e = form.errors;
    const setProvider = (name, patch) => form.setData('providers', { ...d.providers, [name]: { ...d.providers[name], ...patch } });
    const move = (i, dir) => {
        const order = [...d.ai_provider_order];
        const j = i + dir;
        if (j < 0 || j >= order.length) return;
        [order[i], order[j]] = [order[j], order[i]];
        form.setData('ai_provider_order', order);
    };

    return (
        <>
            <PageHeader title="AI" description="AI features for tenants: business insights, payment reminder drafts and the line-item writer." />
            <form
                onSubmit={(ev) => {
                    ev.preventDefault();
                    form.put(route('superadmin.settings.ai.update'), {
                        preserveScroll: true,
                        onSuccess: () => form.setData('providers', Object.fromEntries(Object.entries(d.providers).map(([n, p]) => [n, { ...p, key: '' }]))),
                    });
                }}
                noValidate
            >
                <div className="grid max-w-6xl gap-6 lg:grid-cols-[1fr_340px]">
                    <div className="space-y-6">
                        <Card>
                            <CardHeader title="General" description="Requests are made from this server; API keys are stored encrypted and never sent to browsers." />
                            <CardBody className="space-y-5">
                                <Switch
                                    label="AI features for tenants"
                                    description="Only invoice numbers, client names, amounts and dates are sent to the AI provider; never emails, addresses, phone numbers or bank details."
                                    checked={d.ai_enabled}
                                    onChange={(v) => form.setData('ai_enabled', v)}
                                />
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <FormField label="Default provider" error={e.ai_default_provider}>
                                        <Select value={d.ai_default_provider} onChange={(ev) => form.setData('ai_default_provider', ev.target.value)} options={labels} />
                                    </FormField>
                                    <FormField label="Requests per tenant per month" error={e.ai_monthly_quota} hint="0 disables AI for tenants without switching it off.">
                                        <Input type="number" min="0" value={d.ai_monthly_quota} onChange={(ev) => form.setData('ai_monthly_quota', ev.target.value)} invalid={Boolean(e.ai_monthly_quota)} />
                                    </FormField>
                                </div>
                                <div className="rounded-xl bg-slate-50 p-4">
                                    <Switch
                                        label="Fail over to other providers"
                                        description="If the default provider is down, rate-limited or rejects the key, the request is retried on the next switched-on provider below. A request a provider declines for content reasons is not re-sent."
                                        checked={d.ai_failover}
                                        onChange={(v) => form.setData('ai_failover', v)}
                                    />
                                    {d.ai_failover ? (
                                        <ol className="mt-4 space-y-1.5">
                                            {d.ai_provider_order.map((name, i) => (
                                                <li key={name} className="flex items-center justify-between gap-2 rounded-lg bg-white px-3 py-2 text-sm ring-1 ring-slate-200">
                                                    <span className="flex items-center gap-2">
                                                        <span className="text-xs text-slate-400 tabular-nums">{i + 1}.</span>
                                                        {labels[name]}
                                                        {name === d.ai_default_provider ? <Badge tone="brand">Default, tried first</Badge> : null}
                                                        {!d.providers[name]?.enabled ? <Badge tone="gray">Off</Badge> : null}
                                                    </span>
                                                    <span className="flex gap-1">
                                                        <button type="button" className="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700 disabled:opacity-30" onClick={() => move(i, -1)} disabled={i === 0} aria-label="Move up">
                                                            <ArrowUp className="size-4" />
                                                        </button>
                                                        <button type="button" className="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700 disabled:opacity-30" onClick={() => move(i, 1)} disabled={i === d.ai_provider_order.length - 1} aria-label="Move down">
                                                            <ArrowDown className="size-4" />
                                                        </button>
                                                    </span>
                                                </li>
                                            ))}
                                        </ol>
                                    ) : null}
                                </div>
                            </CardBody>
                        </Card>

                        {providers.map((p) => (
                            <ProviderCard
                                key={p.name}
                                provider={p}
                                data={d.providers[p.name]}
                                errors={e}
                                isDefault={d.ai_default_provider === p.name}
                                onChange={(patch) => setProvider(p.name, patch)}
                                dirty={form.isDirty}
                                kimiRegion={d.ai_kimi_region}
                                onKimiRegion={(v) => form.setData('ai_kimi_region', v)}
                            />
                        ))}

                        <div className="flex justify-end">
                            <Button type="submit" icon={Save} loading={form.processing}>
                                Save AI settings
                            </Button>
                        </div>
                    </div>

                    <UsageCard usage={usage} labels={labels} />
                </div>
            </form>
        </>
    );
}

function ProviderCard({ provider: p, data, errors, isDefault, onChange, dirty, kimiRegion, onKimiRegion }) {
    const [models, setModels] = useState(p.suggested_models);
    // A saved model that isn't in the suggestions starts in "custom" mode so it stays editable.
    const [custom, setCustom] = useState(Boolean(data.model) && !p.suggested_models.includes(data.model));
    const [loadingModels, setLoadingModels] = useState(false);
    const [modelError, setModelError] = useState(null);
    const [testing, setTesting] = useState(false);
    const err = (f) => errors[`providers.${p.name}.${f}`];

    const loadModels = async () => {
        setLoadingModels(true);
        setModelError(null);
        try {
            const res = await postJson(route('superadmin.settings.ai.models'), { provider: p.name });
            const all = [...new Set([...p.suggested_models, ...res.models])];
            setModels(all);
            // The typed model is now in the list: show it as a normal choice.
            if (all.includes(data.model)) setCustom(false);
        } catch (ex) {
            setModelError(ex.message);
        } finally {
            setLoadingModels(false);
        }
    };

    const status = isDefault ? (
        <Badge tone="brand" dot>
            Default
        </Badge>
    ) : p.usable ? (
        <Badge tone="green" dot>
            Ready
        </Badge>
    ) : (
        <Badge tone="gray" dot>
            {p.has_key ? 'Off' : 'Not set up'}
        </Badge>
    );

    return (
        <Card>
            <CardHeader title={p.label} description={`Key from ${p.console}.`} actions={status} />
            <CardBody className="space-y-4">
                <Switch label={`Use ${p.label}`} checked={data.enabled} onChange={(v) => onChange({ enabled: v })} />
                {p.note ? (
                    <p className="flex gap-2 rounded-lg bg-amber-50 p-3 text-xs text-amber-800">
                        <AlertTriangle className="size-4 shrink-0" /> {p.note} Check this fits your privacy policy before switching it on.
                    </p>
                ) : null}
                <div className="grid gap-4 sm:grid-cols-2">
                    <FormField label="API key" error={err('key')} hint={p.has_key ? 'Saved (encrypted). Leave blank to keep it.' : 'Stored encrypted.'}>
                        <Input type="password" autoComplete="new-password" value={data.key} onChange={(ev) => onChange({ key: ev.target.value })} placeholder={p.has_key ? '•••••••• (saved)' : 'sk-…'} invalid={Boolean(err('key'))} />
                    </FormField>
                    <FormField label="Model" error={err('model') ?? modelError} hint={p.has_key ? 'Pick a model, or click Load for the full list your key can use.' : 'Pick a model. After saving a key, Load shows the full list your key can use.'}>
                        <div className="space-y-2">
                            <div className="flex gap-2">
                                <Select
                                    className="font-mono"
                                    value={custom ? CUSTOM : data.model}
                                    onChange={(ev) => {
                                        const v = ev.target.value;
                                        setCustom(v === CUSTOM);
                                        if (v !== CUSTOM) onChange({ model: v });
                                    }}
                                    options={{ '': 'Choose a model…', ...Object.fromEntries(models.map((m) => [m, m])), [CUSTOM]: 'Custom model id…' }}
                                    invalid={Boolean(err('model'))}
                                />
                                <Button variant="secondary" icon={List} onClick={loadModels} loading={loadingModels} disabled={!p.has_key} title={p.has_key ? 'Load the models your key can use' : 'Save an API key first'}>
                                    <span className="sr-only sm:not-sr-only">Load</span>
                                </Button>
                            </div>
                            {custom ? <Input value={data.model} onChange={(ev) => onChange({ model: ev.target.value.trim() })} className="font-mono" placeholder="Type the model id" autoFocus invalid={Boolean(err('model'))} /> : null}
                        </div>
                    </FormField>
                    {p.name === 'kimi' ? (
                        <FormField label="Endpoint" className="sm:col-span-2">
                            <Select value={kimiRegion} onChange={(ev) => onKimiRegion(ev.target.value)} options={{ intl: 'International (api.moonshot.ai)', cn: 'China (api.moonshot.cn)' }} />
                        </FormField>
                    ) : null}
                    {!p.known_prices ? (
                        <>
                            <FormField label="Input price" error={err('price_in')} hint="USD per 1M tokens, for the cost estimate (optional).">
                                <Input type="number" min="0" step="0.01" value={data.price_in} onChange={(ev) => onChange({ price_in: ev.target.value })} prefix="$" />
                            </FormField>
                            <FormField label="Output price" error={err('price_out')} hint="USD per 1M tokens.">
                                <Input type="number" min="0" step="0.01" value={data.price_out} onChange={(ev) => onChange({ price_out: ev.target.value })} prefix="$" />
                            </FormField>
                        </>
                    ) : null}
                </div>
            </CardBody>
            <CardFooter>
                <Button
                    variant="secondary"
                    icon={Plug}
                    loading={testing}
                    disabled={!p.has_key || dirty}
                    title={dirty ? 'Save your changes first' : undefined}
                    onClick={() => router.post(route('superadmin.settings.ai.test'), { provider: p.name }, { preserveScroll: true, onStart: () => setTesting(true), onFinish: () => setTesting(false) })}
                >
                    Test connection
                </Button>
            </CardFooter>
        </Card>
    );
}

function UsageCard({ usage, labels }) {
    return (
        <Card className="h-fit lg:sticky lg:top-6">
            <CardHeader title="This month" description="Cost is estimated from token usage and the prices you set." />
            <CardBody className="space-y-3 text-sm text-slate-600">
                <p>
                    <strong className="text-slate-900">{usage.requests}</strong> requests · <strong className="text-slate-900">${usage.cost_usd.toFixed(2)}</strong>
                    {usage.errors ? <span className="text-red-600"> · {usage.errors} failed</span> : null}
                </p>
                <p className="text-xs">
                    {usage.input_tokens.toLocaleString()} input / {usage.output_tokens.toLocaleString()} output tokens
                </p>
                <Rows title="By provider" rows={usage.by_provider.map((r) => [labels[r.provider] ?? r.provider, `${r.requests}${r.errors ? ` (${r.errors} failed)` : ''} · $${r.cost_usd.toFixed(2)}`])} />
                <Rows title="By feature" rows={usage.by_feature.map((r) => [FEATURES[r.feature] ?? r.feature, `${r.requests} · $${r.cost_usd.toFixed(2)}`])} />
                <Rows title="Top tenants" rows={usage.top_companies.map((r) => [r.company, `${r.requests} · $${r.cost_usd.toFixed(2)}`])} />
                {usage.recent_errors.length ? (
                    <>
                        <p className="pt-2 text-xs font-semibold tracking-wide text-slate-500 uppercase">Recent problems</p>
                        <ul className="space-y-1 text-xs">
                            {usage.recent_errors.map((r, i) => (
                                <li key={i} className="break-words">
                                    <span className="font-medium">
                                        {labels[r.provider] ?? r.provider} · {FEATURES[r.feature] ?? r.feature}
                                    </span>{' '}
                                    ({r.status}, {formatDateTime(r.at)}): {r.error ?? 'declined'}
                                </li>
                            ))}
                        </ul>
                    </>
                ) : null}
            </CardBody>
        </Card>
    );
}

function Rows({ title, rows }) {
    if (!rows.length) return null;
    return (
        <>
            <p className="pt-2 text-xs font-semibold tracking-wide text-slate-500 uppercase">{title}</p>
            <ul className="space-y-1">
                {rows.map(([label, value]) => (
                    <li key={label} className="flex justify-between gap-2">
                        <span className="truncate">{label}</span>
                        <span className="shrink-0 tabular-nums">{value}</span>
                    </li>
                ))}
            </ul>
        </>
    );
}
