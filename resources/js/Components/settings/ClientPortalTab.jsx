import { router } from '@inertiajs/react';
import { Check, Copy, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { Badge, Button, Card, CardBody, CardHeader, Input, Switch } from '@/Components/ui';
import { route } from '@/lib/route';

/** Settings → Client portal: the company-wide switch, or an upgrade prompt when the plan lacks it. */
export default function ClientPortalTab({ portal }) {
    const [copied, setCopied] = useState(false);

    const copy = () => {
        navigator.clipboard?.writeText(portal.url);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const setEnabled = (enabled) => router.put(route('settings.client-portal'), { enabled }, { preserveScroll: true, preserveState: true });

    if (!portal.included) {
        return (
            <Card>
                <CardHeader title="Client portal" description="Let clients log in to see all their invoices and quotations in one place." actions={<Badge tone="brand">Upgrade</Badge>} />
                <CardBody>
                    <p className="text-sm text-slate-600">Your current plan doesn't include the client portal{portal.enabled ? ', so your clients can no longer sign in' : ''}. Upgrade to switch it on.</p>
                    <Button href={route('settings.subscription')} icon={Sparkles} className="mt-4">
                        See plans
                    </Button>
                    {portal.enabled ? (
                        <div className="mt-6 border-t border-slate-100 pt-5">
                            <Switch label="Client portal switched on" description="Stays off for clients until your plan includes it again." checked onChange={() => setEnabled(false)} />
                        </div>
                    ) : null}
                </CardBody>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader title="Client portal" description="Let clients log in to see all their invoices and quotations in one place." />
            <CardBody className="space-y-6">
                <Switch
                    label="Allow client sign in"
                    description="Switching this off signs out every client straight away. The private links in your emails keep working."
                    checked={portal.enabled}
                    onChange={setEnabled}
                />
                {portal.enabled ? (
                    <>
                        <div>
                            <p className="mb-1.5 text-sm font-medium text-slate-700">Sign-in address for your clients</p>
                            <div className="flex gap-2">
                                <Input readOnly value={portal.url ?? ''} aria-label="Client portal address" />
                                <Button variant="secondary" icon={copied ? Check : Copy} onClick={copy}>
                                    {copied ? 'Copied' : 'Copy'}
                                </Button>
                            </div>
                        </div>
                        <p className="text-sm text-slate-600">
                            Clients get in by invitation only: open a client and choose <strong>Invite to portal</strong>. {portal.accounts} {portal.accounts === 1 ? 'client has' : 'clients have'} access.
                        </p>
                    </>
                ) : null}
            </CardBody>
        </Card>
    );
}
