import { router, usePage } from '@inertiajs/react';
import { Banknote, Clock, FilePlus2, FileText, Mail, MapPin, Pencil, Phone, Receipt, Trash2 } from 'lucide-react';
import {
    Avatar,
    Badge,
    Button,
    Card,
    CardHeader,
    DescriptionItem,
    Dropdown,
    EmptyState,
    PageHeader,
    StatCard,
    StatusBadge,
    Table,
    TBody,
    TD,
    TH,
    THead,
    TR,
    useConfirm,
} from '@/Components/ui';
import { CustomFieldValues } from '@/Components/CustomFields';
import { route } from '@/lib/route';
import { formatDate, formatMoney } from '@/lib/utils';

export default function ClientShow({ client, invoices, stats, customFields = [] }) {
    const { auth } = usePage().props;
    const currency = auth.company?.currency;
    const df = auth.company?.date_format;
    const confirm = useConfirm();
    const money = (v) => formatMoney(v, currency);

    const destroy = async () => {
        if (await confirm({ title: `Delete ${client.bill_to_name}?`, message: 'This cannot be undone. Clients with invoices cannot be deleted.', confirmText: 'Delete client' })) {
            router.delete(route('clients.destroy', client.id));
        }
    };

    return (
        <>
            <PageHeader
                title={client.bill_to_name}
                back={route('clients.index')}
                meta={!client.is_active ? <Badge>Inactive</Badge> : client.client_type === 'company' ? <Badge tone="blue">Company</Badge> : null}
                actions={
                    <>
                        <Button href={route('invoices.create', { client_id: client.id })} icon={FilePlus2}>
                            New invoice
                        </Button>
                        <Button href={route('clients.edit', client.id)} variant="secondary" icon={Pencil}>
                            Edit
                        </Button>
                        <Dropdown
                            items={[
                                { label: 'New quotation', icon: FileText, href: route('quotations.create', { client_id: client.id }) },
                                { separator: true },
                                { label: 'Delete client', icon: Trash2, danger: true, onClick: destroy },
                            ]}
                        />
                    </>
                }
            />

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Invoices" value={stats.total_invoices} icon={FileText} tone="gray" />
                <StatCard label="Total billed" value={money(stats.total_billed)} icon={Receipt} tone="brand" />
                <StatCard label="Paid" value={money(stats.total_paid)} icon={Banknote} tone="green" />
                <StatCard label="Outstanding" value={money(stats.outstanding)} icon={Clock} tone={stats.outstanding > 0 ? 'yellow' : 'gray'} />
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-1">
                    <div className="flex items-center gap-4 border-b border-slate-100 p-5">
                        <Avatar name={client.bill_to_name} size="lg" />
                        <div className="min-w-0">
                            <p className="truncate font-semibold text-slate-900">{client.bill_to_name}</p>
                            {client.client_type === 'company' ? <p className="truncate text-sm text-slate-500">{client.name}</p> : null}
                        </div>
                    </div>
                    <dl className="space-y-4 p-5">
                        <ContactRow icon={Mail} value={client.email} href={client.email ? `mailto:${client.email}` : null} />
                        <ContactRow icon={Phone} value={client.phone} href={client.phone ? `tel:${client.phone}` : null} />
                        <ContactRow icon={MapPin} value={client.full_address} />
                        <DescriptionItem label="Tax / VAT number">{client.tax_number}</DescriptionItem>
                        <DescriptionItem label="Client since">{formatDate(client.created_at, df)}</DescriptionItem>
                        {client.notes ? (
                            <DescriptionItem label="Notes">
                                <span className="whitespace-pre-line text-slate-600">{client.notes}</span>
                            </DescriptionItem>
                        ) : null}
                    </dl>
                    <CustomFieldValues fields={customFields} values={client.custom_values} className="border-t border-slate-100 p-5 sm:grid-cols-1" />
                </Card>

                <Card className="lg:col-span-2">
                    <CardHeader title="Recent invoices" description="The 10 most recent invoices for this client" />
                    {invoices.length ? (
                        <Table>
                            <THead>
                                <TH>Invoice</TH>
                                <TH className="hidden sm:table-cell">Issued</TH>
                                <TH className="hidden sm:table-cell">Due</TH>
                                <TH>Status</TH>
                                <TH align="right">Amount</TH>
                            </THead>
                            <TBody>
                                {invoices.map((i) => (
                                    <TR key={i.id} href={route('invoices.show', i.id)}>
                                        <TD className="font-medium text-slate-900">{i.invoice_number}</TD>
                                        <TD className="hidden sm:table-cell">{formatDate(i.issue_date, df)}</TD>
                                        <TD className="hidden sm:table-cell">{formatDate(i.due_date, df)}</TD>
                                        <TD>
                                            <StatusBadge status={i.status} />
                                        </TD>
                                        <TD align="right" className="font-medium text-slate-900">
                                            {money(i.total)}
                                        </TD>
                                    </TR>
                                ))}
                            </TBody>
                        </Table>
                    ) : (
                        <EmptyState
                            icon={FileText}
                            title="No invoices yet"
                            description={`Bill ${client.bill_to_name} for the first time.`}
                            action={
                                <Button href={route('invoices.create', { client_id: client.id })} icon={FilePlus2}>
                                    Create invoice
                                </Button>
                            }
                        />
                    )}
                </Card>
            </div>
        </>
    );
}

function ContactRow({ icon: Icon, value, href }) {
    if (!value) return null;
    return (
        <div className="flex items-start gap-3 text-sm">
            <Icon className="mt-0.5 size-4 shrink-0 text-slate-400" />
            {href ? (
                <a href={href} className="break-all text-slate-700 hover:text-brand-600">
                    {value}
                </a>
            ) : (
                <span className="text-slate-700">{value}</span>
            )}
        </div>
    );
}
