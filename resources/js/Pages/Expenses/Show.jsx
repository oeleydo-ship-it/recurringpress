import { Link, router, usePage } from '@inertiajs/react';
import { ExternalLink, FileText, Pencil, Trash2 } from 'lucide-react';
import { Badge, Button, Card, CardHeader, DescriptionItem, Dropdown, PageHeader, useConfirm } from '@/Components/ui';
import { route } from '@/lib/route';
import { formatDate, formatDateTime, formatMoney } from '@/lib/utils';

export default function ExpenseShow({ expense: e, paymentMethods }) {
    const { auth } = usePage().props;
    const currency = auth.company?.currency;
    const confirm = useConfirm();

    const destroy = async () => {
        if (await confirm({ title: `Delete “${e.title}”?`, confirmText: 'Delete expense' })) router.delete(route('expenses.destroy', e.id));
    };

    return (
        <>
            <PageHeader
                title={e.title}
                back={route('expenses.index')}
                meta={
                    <>
                        <Badge>{e.category_label}</Badge>
                        {e.is_billable ? <Badge tone="blue">Billable</Badge> : null}
                        {e.is_reimbursed ? <Badge tone="green">Reimbursed</Badge> : null}
                    </>
                }
                description={`${e.expense_number} · ${formatDate(e.expense_date, auth.company?.date_format)}`}
                actions={
                    <>
                        <Button href={route('expenses.edit', e.id)} variant="secondary" icon={Pencil}>
                            Edit
                        </Button>
                        <Dropdown items={[{ label: 'Delete', icon: Trash2, danger: true, onClick: destroy }]} />
                    </>
                }
            />
            <div className="grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <div className="border-b border-slate-100 p-6">
                        <p className="text-sm text-slate-500">Total</p>
                        <p className="mt-1 text-4xl font-semibold tracking-tight text-slate-900 tabular-nums">{formatMoney(e.total, currency)}</p>
                        {e.tax_amount > 0 ? (
                            <p className="mt-1 text-sm text-slate-500">
                                {formatMoney(e.amount, currency)} + {formatMoney(e.tax_amount, currency)} tax
                            </p>
                        ) : null}
                    </div>
                    <dl className="grid gap-6 p-6 sm:grid-cols-2">
                        <DescriptionItem label="Vendor">{e.vendor}</DescriptionItem>
                        <DescriptionItem label="Paid with">{paymentMethods[e.payment_method] ?? e.payment_method}</DescriptionItem>
                        <DescriptionItem label="Reference">{e.reference}</DescriptionItem>
                        <DescriptionItem label="Related invoice">
                            {e.invoice_id ? (
                                <Link href={route('invoices.show', e.invoice_id)} className="inline-flex items-center gap-1.5 text-brand-600 hover:text-brand-700">
                                    <FileText className="size-4" /> {e.invoice_number}
                                </Link>
                            ) : null}
                        </DescriptionItem>
                        <DescriptionItem label="Recorded by">{e.created_by ? `${e.created_by} · ${formatDateTime(e.created_at)}` : null}</DescriptionItem>
                        {e.description ? (
                            <DescriptionItem label="Notes" className="sm:col-span-2">
                                <span className="whitespace-pre-line">{e.description}</span>
                            </DescriptionItem>
                        ) : null}
                    </dl>
                </Card>
                <Card className="h-fit overflow-hidden">
                    <CardHeader
                        title="Receipt"
                        actions={
                            e.receipt_url ? (
                                <a href={e.receipt_url} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:text-brand-700">
                                    Open <ExternalLink className="size-3.5" />
                                </a>
                            ) : null
                        }
                    />
                    {e.receipt_url ? (
                        e.receipt_is_pdf ? (
                            <iframe src={e.receipt_url} title="Receipt" className="h-96 w-full border-0" />
                        ) : (
                            <img src={e.receipt_url} alt="Expense receipt" className="max-h-[480px] w-full object-contain bg-slate-50" />
                        )
                    ) : (
                        <p className="p-5 text-sm text-slate-500">No receipt attached.</p>
                    )}
                </Card>
            </div>
        </>
    );
}
