import { router, usePage } from '@inertiajs/react';
import { Ban, Send, Settings2, ShieldCheck, Sparkles } from 'lucide-react';
import { Badge, Button, Card, CardBody, CardHeader, useConfirm } from '@/Components/ui';
import { route } from '@/lib/route';
import { formatDateTime } from '@/lib/utils';

/** Client page: this client's portal access. Only owners and admins can change it. */
export default function ClientPortalCard({ client, portal }) {
    const { auth } = usePage().props;
    const confirm = useConfirm();
    const account = portal?.account;

    if (!portal) return null;

    const post = (name) => router.post(route(name, client.id), {}, { preserveScroll: true, preserveState: true });

    const revoke = async () => {
        if (await confirm({ title: `Remove portal access for ${client.bill_to_name}?`, message: 'They are signed out straight away and can no longer sign in.', confirmText: 'Remove access' })) {
            post('clients.portal.revoke');
        }
    };

    const status = !account ? null : !account.is_enabled ? <Badge>Access removed</Badge> : account.activated ? <Badge tone="green">Active</Badge> : <Badge tone="yellow">Invited</Badge>;

    let body;

    if (!portal.included || !portal.open) {
        body = <ClientPortalNotice portal={portal} />;
    } else {
        body = (
            <>
                {account ? (
                    <dl className="space-y-1 text-sm text-slate-600">
                        <div>Signs in with {account.email}</div>
                        {account.last_login_at ? <div>Last sign in {formatDateTime(account.last_login_at)}</div> : account.invited_at ? <div>Invited {formatDateTime(account.invited_at)}</div> : null}
                    </dl>
                ) : (
                    <p className="text-sm text-slate-600">{client.email ? `Invite ${client.email} to see their invoices and quotations.` : 'Add an email address to this client to invite them.'}</p>
                )}
                {auth.user.is_admin ? (
                    <div className="mt-3 flex flex-wrap gap-2">
                        {!account ? (
                            <Button variant="secondary" icon={Send} disabled={!client.email} onClick={() => post('clients.portal.invite')}>
                                Invite to portal
                            </Button>
                        ) : account.is_enabled ? (
                            <>
                                <Button variant="secondary" icon={Send} onClick={() => post('clients.portal.invite')}>
                                    {account.activated ? 'Send password link' : 'Resend invitation'}
                                </Button>
                                <Button variant="secondary" icon={Ban} onClick={revoke}>
                                    Remove access
                                </Button>
                            </>
                        ) : (
                            <Button variant="secondary" icon={ShieldCheck} onClick={() => post('clients.portal.restore')}>
                                Restore access
                            </Button>
                        )}
                    </div>
                ) : null}
            </>
        );
    }

    return (
        <Card>
            <CardHeader title="Client portal" actions={status} />
            <CardBody>{body}</CardBody>
        </Card>
    );
}

/** Why the portal can't be used yet, and where to fix it: upgrade the plan or switch it on. */
export function ClientPortalNotice({ portal }) {
    const { auth } = usePage().props;

    if (!portal.included) {
        return (
            <>
                <p className="text-sm text-slate-600">Upgrade your plan to let this client sign in and see their invoices and quotations.</p>
                {auth.user.is_admin ? (
                    <Button href={route('settings.subscription')} variant="secondary" icon={Sparkles} className="mt-3">
                        Upgrade plan
                    </Button>
                ) : null}
            </>
        );
    }

    return (
        <>
            <p className="text-sm text-slate-600">The client portal is switched off for your company.</p>
            {auth.user.is_admin ? (
                <Button href={`${route('settings.index')}#client-portal`} variant="secondary" icon={Settings2} className="mt-3">
                    Open settings
                </Button>
            ) : null}
        </>
    );
}
