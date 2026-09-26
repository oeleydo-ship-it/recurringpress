import { Link, usePage } from '@inertiajs/react';
import { Banknote, Download, FileText } from 'lucide-react';
import DocumentPreview from '@/Components/documents/DocumentPreview';
import { Button, Card, DescriptionItem, PageHeader } from '@/Components/ui';
import { route } from '@/lib/route';
import { formatDateTime, formatMoney } from '@/lib/utils';

export default function ReceiptShow({ receipt: r }) {
    const { auth } = usePage().props;
    const currency = auth.company?.currency;

    return (
        <>
            <PageHeader
                title={r.receipt_number}
                back={route('receipts.index')}
                description={`${formatMoney(r.amount, currency)} · ${r.payment_method_label} · ${formatDateTime(r.issued_at)}`}
                actions={
                    <Button href={route('receipts.download', r.id)} external icon={Download}>
                        Download PDF
                    </Button>
                }
            />
            <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
                <DocumentPreview src={route('receipts.preview', r.id)} />
                <Card className="h-fit">
                    <dl className="space-y-5 p-5">
                        <DescriptionItem label="Client">
                            {r.client_id ? (
                                <Link href={route('clients.show', r.client_id)} className="text-brand-600 hover:text-brand-700">
                                    {r.client_name}
                                </Link>
                            ) : null}
                        </DescriptionItem>
                        <DescriptionItem label="Invoice">
                            {r.invoice_id ? (
                                <Link href={route('invoices.show', r.invoice_id)} className="inline-flex items-center gap-1.5 text-brand-600 hover:text-brand-700">
                                    <FileText className="size-4" /> {r.invoice_number}
                                </Link>
                            ) : null}
                        </DescriptionItem>
                        <DescriptionItem label="Payment">
                            {r.payment_id ? (
                                <Link href={route('payments.show', r.payment_id)} className="inline-flex items-center gap-1.5 text-brand-600 hover:text-brand-700">
                                    <Banknote className="size-4" /> {r.payment_number}
                                </Link>
                            ) : null}
                        </DescriptionItem>
                        {r.transaction_id ? <DescriptionItem label="Transaction ID">{<span className="font-mono text-xs break-all">{r.transaction_id}</span>}</DescriptionItem> : null}
                        {r.notes ? <DescriptionItem label="Notes">{r.notes}</DescriptionItem> : null}
                    </dl>
                </Card>
            </div>
        </>
    );
}
