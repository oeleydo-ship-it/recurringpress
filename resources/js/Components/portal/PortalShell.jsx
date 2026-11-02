import { Link, router, usePage } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import PublicShell from '@/Components/PublicShell';
import { Button, Card } from '@/Components/ui';
import { cn } from '@/lib/utils';

/** Tenant-branded frame for signed-in portal pages. */
export default function PortalShell({ title, children }) {
    const { portal } = usePage().props;
    const { url } = usePage();
    const path = url.split('?')[0];
    const pathOf = (href) => new URL(href).pathname;

    const links = [
        { label: 'Overview', href: portal.urls.dashboard, exact: true },
        { label: 'Invoices', href: portal.urls.invoices },
        { label: 'Quotations', href: portal.urls.quotations },
        { label: 'Account', href: portal.urls.account },
    ];

    return (
        <PublicShell
            title={`${title} · ${portal.company.name}`}
            company={portal.company}
            actions={
                <Button variant="secondary" icon={LogOut} onClick={() => router.post(portal.urls.logout)}>
                    Sign out
                </Button>
            }
            nav={
                <nav className="mx-auto flex max-w-6xl gap-1 overflow-x-auto px-4 sm:px-6" aria-label="Client portal">
                    {links.map((l) => {
                        const active = l.exact ? path === pathOf(l.href) : path.startsWith(pathOf(l.href));
                        return (
                            <Link
                                key={l.label}
                                href={l.href}
                                aria-current={active ? 'page' : undefined}
                                className={cn('border-b-2 px-3 py-2.5 text-sm font-medium whitespace-nowrap', active ? 'border-brand-600 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-800')}
                            >
                                {l.label}
                            </Link>
                        );
                    })}
                </nav>
            }
        >
            {children}
        </PublicShell>
    );
}

/** Small centred card for the signed-out pages (sign in, password links, unavailable). */
export function PortalAuthCard({ title, description, children }) {
    const { portal } = usePage().props;

    return (
        <PublicShell title={`${title} · ${portal.company.name}`} company={portal.company}>
            <Card className="mx-auto mt-6 max-w-md p-6 sm:p-8">
                <h1 className="text-xl font-semibold tracking-tight text-slate-900">{title}</h1>
                {description ? <p className="mt-1.5 text-sm text-slate-500">{description}</p> : null}
                <div className="mt-6">{children}</div>
            </Card>
        </PublicShell>
    );
}
