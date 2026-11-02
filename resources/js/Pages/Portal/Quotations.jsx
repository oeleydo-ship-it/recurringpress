import { FileSignature } from 'lucide-react';
import PortalShell from '@/Components/portal/PortalShell';
import { QuotationTable } from '@/Components/portal/DocumentTables';
import { Card, EmptyState, Pagination } from '@/Components/ui';

export default function PortalQuotations({ quotations }) {
    return (
        <PortalShell title="Quotations">
            <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Quotations</h1>
            <Card className="mt-6">
                {quotations.data.length ? <QuotationTable quotations={quotations.data} /> : <EmptyState icon={FileSignature} title="No quotations yet" />}
                <Pagination paginator={quotations} />
            </Card>
        </PortalShell>
    );
}
