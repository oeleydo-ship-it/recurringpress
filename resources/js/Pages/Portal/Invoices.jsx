import { FileText } from 'lucide-react';
import PortalShell from '@/Components/portal/PortalShell';
import { InvoiceTable } from '@/Components/portal/DocumentTables';
import { Card, EmptyState, Pagination } from '@/Components/ui';

export default function PortalInvoices({ invoices }) {
    return (
        <PortalShell title="Invoices">
            <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Invoices</h1>
            <Card className="mt-6">
                {invoices.data.length ? <InvoiceTable invoices={invoices.data} /> : <EmptyState icon={FileText} title="No invoices yet" />}
                <Pagination paginator={invoices} />
            </Card>
        </PortalShell>
    );
}
