import { router, usePage } from '@inertiajs/react';
import { Eye, Pause, Pencil, Play, Plus, Repeat, Send, Trash2, XCircle } from 'lucide-react';
import {
    Badge,
    Button,
    Card,
    Dropdown,
    EmptyState,
    FilterBar,
    LinkTabs,
    PageHeader,
    Pagination,
    SearchInput,
    StatusBadge,
    Table,
    TBody,
    TD,
    TH,
    THead,
    TR,
    useConfirm,
    useFilters,
} from '@/Components/ui';
import { route } from '@/lib/route';
import { formatDate, formatMoney } from '@/lib/utils';

export default function RecurringIndex({ recurring, filters: initial, statuses, billingCycles, counts, mrr }) {
    const { auth } = usePage().props;
    const currency = auth.company?.currency;
    const df = auth.company?.date_format;
    const confirm = useConfirm();
    const [filters, setFilter] = useFilters(route('recurring-invoices.index'), initial);

    const act = (name, r) => router.post(route(name, r.id), {}, { preserveScroll: true });
    const cancel = async (r) => {
        if (await confirm({ title: `Cancel “${r.name}”?`, message: 'No further invoices will be generated. This cannot be resumed.', confirmText: 'Cancel schedule' })) act('recurring-invoices.cancel', r);
    };
    const destroy = async (r) => {
        if (await confirm({ title: `Delete “${r.name}”?`, message: 'Invoices already generated are kept.', confirmText: 'Delete' })) {
            router.delete(route('recurring-invoices.destroy', r.id), { preserveScroll: true });
        }
    };

    return (
        <>
            <PageHeader
                title="Recurring invoices"
                description={
                    <>
                        <span className="font-medium text-slate-700">{formatMoney(mrr, currency)}</span> monthly recurring revenue
                    </>
                }
                actions={
                    <Button href={route('recurring-invoices.create')} icon={Plus}>
                        New schedule
                    </Button>
                }
            />

            <LinkTabs
                className="mb-4"
                tabs={[['', 'All'], ...Object.entries(statuses)].map(([key, label]) => ({
                    label,
                    count: key ? Number(counts[key] ?? 0) : Object.values(counts).reduce((a, b) => a + Number(b), 0),
                    active: (filters.status ?? '') === key,
                    href: route('recurring-invoices.index', key ? { status: key } : {}),
                }))}
            />

            <Card>
                <FilterBar>
                    <SearchInput value={filters.search} onChange={(v) => setFilter('search', v)} placeholder="Schedule or client…" className="sm:max-w-sm sm:flex-1" />
                </FilterBar>
                {recurring.data.length ? (
                    <>
                        <Table>
                            <THead>
                                <TH>Schedule</TH>
                                <TH className="hidden md:table-cell">Frequency</TH>
                                <TH className="hidden md:table-cell">Next invoice</TH>
                                <TH>Status</TH>
                                <TH align="right">Amount</TH>
                                <TH className="w-10" />
                            </THead>
                            <TBody>
                                {recurring.data.map((r) => (
                                    <TR key={r.id} href={route('recurring-invoices.show', r.id)}>
                                        <TD>
                                            <div className="flex items-center gap-2">
                                                <span className="font-medium text-slate-900">{r.name}</span>
                                                {r.auto_send ? (
                                                    <Badge tone="blue">
                                                        <Send className="size-3" /> Auto
                                                    </Badge>
                                                ) : null}
                                            </div>
                                            <span className="text-xs text-slate-500">
                                                {r.client_name} · {r.invoices_generated} generated
                                            </span>
                                        </TD>
                                        <TD className="hidden md:table-cell">{billingCycles[r.billing_cycle] ?? r.billing_cycle}</TD>
                                        <TD className="hidden md:table-cell">{r.status === 'active' ? formatDate(r.next_invoice_date, df) : '—'}</TD>
                                        <TD>
                                            <StatusBadge status={r.status} />
                                        </TD>
                                        <TD align="right" className="font-medium text-slate-900 tabular-nums">
                                            {formatMoney(r.total, currency)}
                                        </TD>
                                        <TD>
                                            <Dropdown
                                                items={[
                                                    { label: 'View', icon: Eye, href: route('recurring-invoices.show', r.id) },
                                                    { label: 'Edit', icon: Pencil, href: route('recurring-invoices.edit', r.id), hidden: r.status === 'cancelled' },
                                                    { label: 'Pause', icon: Pause, onClick: () => act('recurring-invoices.pause', r), hidden: r.status !== 'active' },
                                                    { label: 'Resume', icon: Play, onClick: () => act('recurring-invoices.resume', r), hidden: r.status !== 'paused' },
                                                    { label: 'Cancel schedule', icon: XCircle, onClick: () => cancel(r), hidden: ['cancelled', 'completed'].includes(r.status) },
                                                    { separator: true },
                                                    { label: 'Delete', icon: Trash2, danger: true, onClick: () => destroy(r) },
                                                ]}
                                            />
                                        </TD>
                                    </TR>
                                ))}
                            </TBody>
                        </Table>
                        <Pagination paginator={recurring} />
                    </>
                ) : (
                    <EmptyState
                        icon={Repeat}
                        title="No recurring invoices"
                        description="Bill retainers and subscriptions automatically — weekly, monthly or yearly."
                        action={
                            <Button href={route('recurring-invoices.create')} icon={Plus}>
                                New schedule
                            </Button>
                        }
                    />
                )}
            </Card>
        </>
    );
}
