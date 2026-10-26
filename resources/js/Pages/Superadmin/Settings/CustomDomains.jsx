import { router, useForm } from '@inertiajs/react';
import { Plug, Save } from 'lucide-react';
import { useState } from 'react';
import { Badge, Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Switch } from '@/Components/ui';
import { route } from '@/lib/route';

export default function CustomDomainSettings({ settings, hasToken, configured, subdomainBase, stats }) {
    const form = useForm({
        custom_domains_enabled: Boolean(settings.custom_domains_enabled),
        tenant_subdomains_enabled: Boolean(settings.tenant_subdomains_enabled),
        cloudflare_zone_id: settings.cloudflare_zone_id ?? '',
        cloudflare_api_token: '',
        cloudflare_cname_target: settings.cloudflare_cname_target ?? '',
    });
    const [testing, setTesting] = useState(false);
    const d = form.data;
    const e = form.errors;

    return (
        <>
            <PageHeader title="Custom domains" description="Let tenants serve invoice and quotation pages on their own domain or a subdomain, through Cloudflare." />
            <div className="grid max-w-5xl gap-6 lg:grid-cols-[1fr_320px]">
                <form
                    onSubmit={(ev) => {
                        ev.preventDefault();
                        form.put(route('superadmin.settings.custom-domains.update'), { preserveScroll: true, onSuccess: () => form.setData('cloudflare_api_token', '') });
                    }}
                    noValidate
                >
                    <Card>
                        <CardHeader title="Cloudflare for SaaS" description="Each tenant domain becomes a Custom Hostname on your zone. Cloudflare verifies it, issues its certificate and proxies it to this server." actions={configured ? <Badge tone="green" dot>Configured</Badge> : <Badge tone="gray" dot>Not configured</Badge>} />
                        <CardBody className="space-y-5">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <FormField label="Zone ID" required error={e.cloudflare_zone_id} hint="Zone Overview page, under API." className="sm:col-span-2">
                                    <Input value={d.cloudflare_zone_id} onChange={(ev) => form.setData('cloudflare_zone_id', ev.target.value.trim())} className="font-mono" placeholder="32-character zone ID" invalid={Boolean(e.cloudflare_zone_id)} />
                                </FormField>
                                <FormField
                                    label="API token"
                                    required
                                    error={e.cloudflare_api_token}
                                    hint={(hasToken ? 'Saved (encrypted). Leave blank to keep it. ' : 'Stored encrypted. ') + 'Permissions: Zone → SSL and Certificates → Edit, and Zone → Zone → Read, for this zone.'}
                                    className="sm:col-span-2"
                                >
                                    <Input type="password" autoComplete="new-password" value={d.cloudflare_api_token} onChange={(ev) => form.setData('cloudflare_api_token', ev.target.value)} placeholder={hasToken ? '•••••••• (saved)' : ''} />
                                </FormField>
                                <FormField label="CNAME target (fallback origin)" required error={e.cloudflare_cname_target} hint="A proxied record in your zone that points at this server, set as the fallback origin under SSL/TLS → Custom Hostnames. Tenants CNAME their domain to it." className="sm:col-span-2">
                                    <Input value={d.cloudflare_cname_target} onChange={(ev) => form.setData('cloudflare_cname_target', ev.target.value.trim())} className="font-mono" placeholder="customers.recurringpress.com" invalid={Boolean(e.cloudflare_cname_target)} />
                                </FormField>
                            </div>
                            <div className="space-y-4 rounded-xl bg-slate-50 p-4">
                                <Switch label="Tenant custom domains" description="Tenants can connect billing.theirdomain.com in Settings → Domains." checked={d.custom_domains_enabled} onChange={(v) => form.setData('custom_domains_enabled', v)} />
                                {e.custom_domains_enabled ? <p className="text-sm text-red-600">{e.custom_domains_enabled}</p> : null}
                                <Switch
                                    label="Tenant subdomains"
                                    description={`Tenants can claim yourname.${subdomainBase}. Needs a proxied wildcard DNS record *.${subdomainBase} pointing at this server.`}
                                    checked={d.tenant_subdomains_enabled}
                                    onChange={(v) => form.setData('tenant_subdomains_enabled', v)}
                                />
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
                        <CardHeader title="Test connection" description="Checks the saved zone ID and token, and the fallback origin." />
                        <CardBody>
                            <Button
                                variant="secondary"
                                icon={Plug}
                                className="w-full"
                                loading={testing}
                                disabled={!configured || form.isDirty}
                                onClick={() => router.post(route('superadmin.settings.custom-domains.test'), {}, { preserveScroll: true, onStart: () => setTesting(true), onFinish: () => setTesting(false) })}
                            >
                                Test Cloudflare connection
                            </Button>
                            {form.isDirty ? <p className="mt-2 text-xs text-slate-500">Save your changes first.</p> : null}
                        </CardBody>
                    </Card>
                    <Card className="h-fit">
                        <CardHeader title="Usage" />
                        <CardBody className="space-y-1 text-sm text-slate-600">
                            <p>
                                Custom domains: <strong className="text-slate-900">{stats.domains}</strong> ({stats.verified} verified)
                            </p>
                            <p>
                                Subdomains: <strong className="text-slate-900">{stats.subdomains}</strong>
                            </p>
                        </CardBody>
                    </Card>
                </div>
            </div>
        </>
    );
}
