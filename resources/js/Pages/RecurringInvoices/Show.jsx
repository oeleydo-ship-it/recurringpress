import { Link, router, usePage } from '@inertiajs/react';
import { CalendarClock, FileText, Pause, Pencil, Play, Send, Trash2, XCircle, Zap } from 'lucide-react';
import { Badge, Button, Card, CardHeader, DescriptionItem, Dropdown, EmptyState, PageHeader, StatusBadge, Table, TBody, TD, TH, THead, TR, useConfirm } from '@/Components/ui';
import { CustomFieldValuesCard } from '@/Components/CustomFields';
import { route } from '@/lib/route';
import { formatDate, formatMoney } from '@/lib/utils';

const cycleLabel = { weekly: 'Weekly', monthly: 'Monthly', quarterly: 'Every 3 months', semi_annual: 'Every 6 months', annual: 'Yearly' };

export default function RecurringShow({ recurring: r, upcoming, invoices, customFields = [] }) {
    const { auth } = usePage().props;
    const currency = auth.company?.currency;
    const df = auth.company?.date_format;
    const confirm = useConfirm();
    const money = (v) => formatMoney(v, currency);
    const act = (name) => router.post(route(name, r.id), {}, { preserveScroll: true });

    const generate = async () => {
        if (await confirm({ title: 'Generate an invoice now?', message: 'This creates the next invoice immediately and moves the schedule forward from today.', confirmText: 'Generate', tone: 'brand' })) {
            act('recurring-invoices.generate');
        }
    };
    const cancel = async () => {
        if (await confirm({ title: 'Cancel this schedule?', message: 'No further invoices will be generated.', confirmText: 'Cancel schedule' })) act('recurring-invoices.cancel');
    };
    const destroy = async () => {
        if (await confirm({ title: `Delete “${r.name}”?`, message: 'Invoices already generated are kept.', confirmText: 'Delete' })) router.delete(route('recurring-invoices.destroy', r.id));
    };

    return (
        <>
            <PageHeader
                title={r.name}
                back={route('recurring-invoices.index')}
                meta={
                    <>
                        <StatusBadge status={r.status} />
                        {r.auto_send ? (
                            <Badge tone="blue">
                                <Send className="size-3" /> Auto-send
                            </Badge>
                        ) : null}
                    </>
                }
                description={r.client ? `${cycleLabel[r.billing_cycle] ?? r.billing_cycle} · ${r.client.name}` : null}
                actions={
                    <>
                        {r.status === 'active' ? (
                            <Button icon={Zap} onClick={generate}>
                                Generate now
                            </Button>
                        ) : null}
                        {r.status === 'paused' ? (
                            <Button icon={Play} onClick={() => act('recurring-invoices.resume')}>
                                Resume
                            </Button>
                        ) : null}
                        <Button href={route('recurring-invoices.edit', r.id)} variant="secondary" icon={Pencil} className={r.status === 'cancelled' ? 'hidden' : ''}>
                            Edit
                        </Button>
                        <Dropdown
                            items={[
                                { label: 'Pause', icon: Pause, onClick: () => act('recurring-invoices.pause'), hidden: r.status !== 'active' },
                                { label: 'Cancel schedule', icon: XCircle, onClick: cancel, hidden: ['cancelled', 'completed'].includes(r.status) },
                                { separator: true },
                                { label: 'Delete', icon: Trash2, danger: true, onClick: destroy },
                            ]}
                        />
                    </>
                }
            />

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card>
                        <CardHeader title="Line items" description={`${money(r.total)} per invoice`} />
                        <Table>
                            <THead>
                                <TH>Item</TH>
                                <TH align="right">Qty</TH>
                                <TH align="right">Price</TH>
                                <TH align="right" className="hidden sm:table-cell">
                                    Tax
                                </TH>
                                <TH align="right">Amount</TH>
                            </THead>
                            <TBody>
                                {r.items.map((i) => (
                                    <TR key={i.id}>
                                        <TD className="whitespace-normal">
                                            <span className="font-medium text-slate-900">{i.name}</span>
                                            {i.description ? <span className="block text-xs text-slate-500">{i.description}</span> : null}
                                        </TD>
                                        <TD align="right">{i.quantity}</TD>
                                        <TD align="right">{money(i.price)}</TD>
                                        <TD align="right" className="hidden sm:table-cell">
                                            {i.tax_rate}%
                                        </TD>
                                        <TD align="right" className="font-medium text-slate-900">
                                            {money(i.quantity * i.price)}
                                        </TD>
                                    </TR>
                                ))}
                            </TBody>
                        </Table>
                        <dl className="space-y-2 border-t border-slate-100 px-5 py-4 text-sm sm:ml-auto sm:w-72">
                            <div className="flex justify-between">
                                <dt className="text-slate-500">Subtotal</dt>
                                <dd>{money(r.subtotal)}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-slate-500">Tax</dt>
                                <dd>{money(r.tax_amount)}</dd>
                            </div>
                            {r.discount_amount > 0 ? (
                                <div className="flex justify-between">
                                    <dt className="text-slate-500">Discount</dt>
                                    <dd>{r.discount_type === 'percentage' ? `${r.discount_amount}%` : `− ${money(r.discount_amount)}`}</dd>
                                </div>
                            ) : null}
                            <div className="flex justify-between border-t border-slate-100 pt-2 text-base font-semibold">
                                <dt>Total</dt>
                                <dd>{money(r.total)}</dd>
                            </div>
                        </dl>
                    </Card>

                    <Card>
                        <CardHeader title="Generated invoices" description={`${invoices.length} so far`} icon={FileText} />
                        {invoices.length ? (
                            <Table>
                                <THead>
                                    <TH>Invoice</TH>
                                    <TH>Issued</TH>
                                    <TH>Status</TH>
                                    <TH align="right">Amount</TH>
                                </THead>
                                <TBody>
                                    {invoices.map((i) => (
                                        <TR key={i.id} href={route('invoices.show', i.id)}>
                                            <TD className="font-medium text-slate-900">{i.invoice_number}</TD>
                                            <TD>{formatDate(i.issue_date, df)}</TD>
                                            <TD>
                                                <StatusBadge status={i.status} />
                                            </TD>
                                            <TD align="right">{money(i.total)}</TD>
                                        </TR>
                                    ))}
                                </TBody>
                            </Table>
                        ) : (
                            <EmptyState icon={FileText} title="Nothing generated yet" description={r.next_invoice_date ? `The first invoice is due on ${formatDate(r.next_invoice_date, df)}.` : undefined} />
                        )}
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader title="Schedule" icon={CalendarClock} />
                        <dl className="grid grid-cols-2 gap-5 p-5">
                            <DescriptionItem label="Frequency">{cycleLabel[r.billing_cycle] ?? r.billing_cycle}</DescriptionItem>
                            <DescriptionItem label="Generated">
                                {r.invoices_generated}
                                {r.billing_cycle_count ? ` of ${r.billing_cycle_count}` : ''}
                            </DescriptionItem>
                            <DescriptionItem label="Started">{formatDate(r.start_date, df)}</DescriptionItem>
                            <DescriptionItem label="Ends">{r.end_date ? formatDate(r.end_date, df) : 'Never'}</DescriptionItem>
                            <DescriptionItem label="Last invoice">{r.last_invoice_date ? formatDate(r.last_invoice_date, df) : null}</DescriptionItem>
                            <DescriptionItem label="Client">
                                {r.client ? (
                                    <Link href={route('clients.show', r.client.id)} className="text-brand-600 hover:text-brand-700">
                                        {r.client.name}
                                    </Link>
                                ) : null}
                            </DescriptionItem>
                        </dl>
                    </Card>
                    <CustomFieldValuesCard fields={customFields} values={r.custom_values} />
                    {upcoming.length ? (
                        <Card>
                            <CardHeader title="Upcoming invoices" />
                            <ol className="divide-y divide-slate-100">
                                {upcoming.map((d, i) => (
                                    <li key={d} className="flex items-center justify-between px-5 py-2.5 text-sm">
                                        <span className="flex items-center gap-3">
                                            <span className="flex size-6 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-700">{i + 1}</span>
                                            {formatDate(d, df)}
                                        </span>
                                        <span className="font-medium text-slate-700">{money(r.total)}</span>
                                    </li>
                                ))}
                            </ol>
                        </Card>
                    ) : null}
                </div>
            </div>
        </>
    );
}
