import { usePage } from '@inertiajs/react';
import { Clock, FileSignature, FileText } from 'lucide-react';
import PortalShell from '@/Components/portal/PortalShell';
import { InvoiceTable, QuotationTable } from '@/Components/portal/DocumentTables';
import { Button, Card, CardHeader, EmptyState, StatCard } from '@/Components/ui';
import { formatMoney } from '@/lib/utils';

export default function PortalDashboard({ stats, invoices, quotations }) {
    const { portal } = usePage().props;

    return (
        <PortalShell title="Overview">
            <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Welcome, {portal.client?.name}</h1>
            <div className="mt-6 grid gap-4 sm:grid-cols-3">
                <StatCard label="Amount due" value={formatMoney(stats.outstanding, portal.currency)} icon={Clock} tone={stats.outstanding > 0 ? 'yellow' : 'gray'} hint={`${stats.open_invoices} open ${stats.open_invoices === 1 ? 'invoice' : 'invoices'}`} />
                <StatCard label="Invoices" value={stats.invoices} icon={FileText} tone="gray" />
                <StatCard label="Quotations" value={stats.quotations} icon={FileSignature} tone="gray" />
            </div>

            <Card className="mt-6">
                <CardHeader title="Recent invoices" actions={stats.invoices > invoices.length ? <Button href={portal.urls.invoices} variant="secondary">View all</Button> : null} />
                {invoices.length ? <InvoiceTable invoices={invoices} /> : <EmptyState icon={FileText} title="No invoices yet" />}
            </Card>

            <Card className="mt-6">
                <CardHeader title="Recent quotations" actions={stats.quotations > quotations.length ? <Button href={portal.urls.quotations} variant="secondary">View all</Button> : null} />
                {quotations.length ? <QuotationTable quotations={quotations} /> : <EmptyState icon={FileSignature} title="No quotations yet" />}
            </Card>
        </PortalShell>
    );
}
