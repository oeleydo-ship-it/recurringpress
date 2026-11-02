import { usePage } from '@inertiajs/react';
import { PortalAuthCard } from '@/Components/portal/PortalShell';

export default function PortalUnavailable() {
    const { portal } = usePage().props;

    return (
        <PortalAuthCard title="Client sign in is not available" description={`${portal.company.name} doesn't offer client sign in at the moment.`}>
            <p className="text-sm text-slate-600">The private invoice and quotation links in your emails still work. For anything else, please contact {portal.company.name} directly.</p>
        </PortalAuthCard>
    );
}
