import { router, useForm } from '@inertiajs/react';
import { Plug, Save } from 'lucide-react';
import { useState } from 'react';
import { Badge, Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Select, Switch } from '@/Components/ui';
import { route } from '@/lib/route';
import { formatDateTime } from '@/lib/utils';

const FEATURES = { insights: 'Business insights', reminder: 'Reminder drafts', line_items: 'Line-item writer', connection_test: 'Connection tests' };

export default function AiSettings({ settings, hasKey, models, usage }) {
    const form = useForm({ ...settings, ai_api_key: '' });
    const [testing, setTesting] = useState(false);
    const d = form.data;
    const e = form.errors;

    return (
        <>
            <PageHeader title="AI (Claude)" description="AI features for tenants: business insights, payment reminder drafts and the line-item writer." />
            <div className="grid max-w-5xl gap-6 lg:grid-cols-[1fr_320px]">
                <form
                    onSubmit={(ev) => {
                        ev.preventDefault();
                        form.put(route('superadmin.settings.ai.update'), { preserveScroll: true, onSuccess: () => form.setData('ai_api_key', '') });
                    }}
                    noValidate
                >
                    <Card>
                        <CardHeader title="Anthropic API" description="Requests are made from this server; the key is never sent to browsers." actions={hasKey ? <Badge tone="green" dot>Key saved</Badge> : <Badge tone="gray" dot>No key</Badge>} />
                        <CardBody className="space-y-5">
                            <FormField label="API key" error={e.ai_api_key} hint={(hasKey ? 'Saved (encrypted). Leave blank to keep it. ' : 'Stored encrypted. ') + 'Create one at console.anthropic.com → API keys.'}>
                                <Input type="password" autoComplete="new-password" value={d.ai_api_key} onChange={(ev) => form.setData('ai_api_key', ev.target.value)} placeholder={hasKey ? '•••••••• (saved)' : 'sk-ant-…'} invalid={Boolean(e.ai_api_key)} />
                            </FormField>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <FormField label="Model" error={e.ai_model}>
                                    <Select value={d.ai_model} onChange={(ev) => form.setData('ai_model', ev.target.value)} options={models} />
                                </FormField>
                                <FormField label="Requests per tenant per month" error={e.ai_monthly_quota} hint="0 disables AI for tenants without switching it off.">
                                    <Input type="number" min="0" value={d.ai_monthly_quota} onChange={(ev) => form.setData('ai_monthly_quota', ev.target.value)} invalid={Boolean(e.ai_monthly_quota)} />
                                </FormField>
                            </div>
                            <div className="rounded-xl bg-slate-50 p-4">
                                <Switch label="AI features for tenants" description="Shows the AI insights card, reminder drafts and line-item writer. Only invoice numbers, client names, amounts and dates are sent to Anthropic." checked={d.ai_enabled} onChange={(v) => form.setData('ai_enabled', v)} />
                                {e.ai_enabled ? <p className="mt-2 text-sm text-red-600">{e.ai_enabled}</p> : null}
                            </div>
                        </CardBody>
                        <CardFooter>
                            <Button type="submit" icon={Save} loading={form.processing}>
                                Save
                            </Button>
                        </CardFooter>
                    </Card>
                </form>
                <div className="space-y-6">
                    <Card className="h-fit">
                        <CardHeader title="Test connection" description="Sends one tiny request with the saved key and model." />
                        <CardBody>
                            <Button
                                variant="secondary"
                                icon={Plug}
                                className="w-full"
                                loading={testing}
                                disabled={!hasKey || form.isDirty}
                                onClick={() => router.post(route('superadmin.settings.ai.test'), {}, { preserveScroll: true, onStart: () => setTesting(true), onFinish: () => setTesting(false) })}
                            >
                                Test AI connection
                            </Button>
                            {form.isDirty ? <p className="mt-2 text-xs text-slate-500">Save your changes first.</p> : null}
                        </CardBody>
                    </Card>
                    <Card className="h-fit">
                        <CardHeader title="This month" description="Estimated from token usage at list prices." />
                        <CardBody className="space-y-3 text-sm text-slate-600">
                            <p>
                                <strong className="text-slate-900">{usage.requests}</strong> requests · <strong className="text-slate-900">${usage.cost_usd.toFixed(2)}</strong>
                                {usage.errors ? <span className="text-red-600"> · {usage.errors} failed</span> : null}
                            </p>
                            <p className="text-xs">
                                {usage.input_tokens.toLocaleString()} input / {usage.output_tokens.toLocaleString()} output tokens
                            </p>
                            {usage.by_feature.length ? (
                                <ul className="space-y-1">
                                    {usage.by_feature.map((f) => (
                                        <li key={f.feature} className="flex justify-between">
                                            <span>{FEATURES[f.feature] ?? f.feature}</span>
                                            <span className="tabular-nums">
                                                {f.requests} · ${f.cost_usd.toFixed(2)}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            ) : null}
                            {usage.top_companies.length ? (
                                <>
                                    <p className="pt-2 text-xs font-semibold tracking-wide text-slate-500 uppercase">Top tenants</p>
                                    <ul className="space-y-1">
                                        {usage.top_companies.map((c) => (
                                            <li key={c.company} className="flex justify-between gap-2">
                                                <span className="truncate">{c.company}</span>
                                                <span className="shrink-0 tabular-nums">
                                                    {c.requests} · ${c.cost_usd.toFixed(2)}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                </>
                            ) : null}
                            {usage.recent_errors.length ? (
                                <>
                                    <p className="pt-2 text-xs font-semibold tracking-wide text-slate-500 uppercase">Recent problems</p>
                                    <ul className="space-y-1 text-xs">
                                        {usage.recent_errors.map((r, i) => (
                                            <li key={i}>
                                                <span className="font-medium">{FEATURES[r.feature] ?? r.feature}</span> ({r.status}, {formatDateTime(r.at)}): {r.error ?? 'declined'}
                                            </li>
                                        ))}
                                    </ul>
                                </>
                            ) : null}
                        </CardBody>
                    </Card>
                </div>
            </div>
        </>
    );
}
