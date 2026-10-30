import { router, usePage } from '@inertiajs/react';
import { Copy, Download, Eye, FilePlus2, FileText, Pencil, Send, Sheet, Trash2 } from 'lucide-react';
import {
    Button,
    Card,
    Combobox,
    Dropdown,
    EmptyState,
    FilterBar,
    Input,
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
import { CustomCell } from '@/Components/CustomFields';
import { route } from '@/lib/route';
import { cn, formatDate, formatMoney } from '@/lib/utils';

const TABS = [
    ['', 'All'],
    ['draft', 'Draft'],
    ['unpaid', 'Unpaid'],
    ['overdue', 'Overdue'],
    ['partial', 'Partially paid'],
    ['paid', 'Paid'],
];

export default function InvoicesIndex({ invoices, filters: initial, counts, clients, summary, customColumns = [] }) {
    const { auth } = usePage().props;
    const currency = auth.company?.currency;
    const df = auth.company?.date_format;
    const confirm = useConfirm();
    const [filters, setFilter] = useFilters(route('invoices.index'), initial);
    const filtered = Object.values(filters).some(Boolean);

    const tabCount = (key) => {
        if (!key) return Object.values(counts).reduce((a, b) => a + Number(b), 0);
        if (key === 'unpaid') return ['sent', 'viewed', 'partial', 'overdue'].reduce((a, s) => a + Number(counts[s] ?? 0), 0);
        return Number(counts[key] ?? 0);
    };

    const destroy = async (inv) => {
        if (await confirm({ title: `Delete ${inv.invoice_number}?`, message: 'The invoice and its line items will be permanently removed.', confirmText: 'Delete invoice' })) {
            router.delete(route('invoices.destroy', inv.id), { preserveScroll: true });
        }
    };

    const exportUrl = route('invoices.export', Object.fromEntries(Object.entries(filters).filter(([, v]) => v)));

    return (
        <>
            <PageHeader
                title="Invoices"
                description={
                    <>
                        <span className="font-medium text-slate-700">{formatMoney(summary.outstanding, currency)}</span> outstanding
                        {summary.overdue > 0 ? (
                            <>
                                {' '}
                                · <span className="font-medium text-red-600">{formatMoney(summary.overdue, currency)}</span> overdue
                            </>
                        ) : null}
                    </>
                }
                actions={
                    <>
                        <Button href={exportUrl} external variant="secondary" icon={Sheet}>
                            Export CSV
                        </Button>
                        <Button href={route('invoices.create')} icon={FilePlus2}>
                            New invoice
                        </Button>
                    </>
                }
            />

            <LinkTabs
                className="mb-4"
                tabs={TABS.map(([key, label]) => ({
                    label,
                    count: tabCount(key),
                    active: (filters.status ?? '') === key,
                    href: route('invoices.index', { ...Object.fromEntries(Object.entries(filters).filter(([k, v]) => v && k !== 'status')), ...(key ? { status: key } : {}) }),
                }))}
            />

            <Card>
                <FilterBar>
                    <SearchInput value={filters.search} onChange={(v) => setFilter('search', v)} placeholder="Invoice #, reference or client…" className="sm:max-w-xs sm:flex-1" />
                    <Combobox
                        value={filters.client_id ?? ''}
                        onChange={(id) => setFilter('client_id', id)}
                        options={[{ id: '', label: 'All clients' }, ...clients]}
                        placeholder="All clients"
                        className="sm:w-56"
                    />
                    <div className="flex items-center gap-2">
                        <Input type="date" value={filters.date_from ?? ''} onChange={(e) => setFilter('date_from', e.target.value)} aria-label="From date" className="sm:w-40" />
                        <span className="text-slate-400">–</span>
                        <Input type="date" value={filters.date_to ?? ''} onChange={(e) => setFilter('date_to', e.target.value)} aria-label="To date" className="sm:w-40" />
                    </div>
                </FilterBar>

                {invoices.data.length ? (
                    <>
                        <Table>
                            <THead>
                                <TH>Invoice</TH>
                                <TH>Client</TH>
                                <TH className="hidden md:table-cell">Issued</TH>
                                <TH className="hidden md:table-cell">Due</TH>
                                <TH>Status</TH>
                                {customColumns.map((f) => (
                                    <TH key={f.key} className="hidden lg:table-cell">
                                        {f.label}
                                    </TH>
                                ))}
                                <TH align="right">Amount</TH>
                                <TH className="w-10" />
                            </THead>
                            <TBody>
                                {invoices.data.map((inv) => (
                                    <TR key={inv.id} href={route('invoices.show', inv.id)}>
                                        <TD className="font-medium text-slate-900">{inv.invoice_number}</TD>
                                        <TD>
                                            <span className="block max-w-[220px] truncate">{inv.client_name ?? '—'}</span>
                                        </TD>
                                        <TD className="hidden text-slate-500 md:table-cell">{formatDate(inv.issue_date, df)}</TD>
                                        <TD className={cn('hidden md:table-cell', inv.is_overdue ? 'font-medium text-red-600' : 'text-slate-500')}>{formatDate(inv.due_date, df)}</TD>
                                        <TD>
                                            <StatusBadge status={inv.is_overdue && inv.status !== 'overdue' ? 'overdue' : inv.status} />
                                        </TD>
                                        {customColumns.map((f) => (
                                            <TD key={f.key} className="hidden max-w-48 truncate lg:table-cell">
                                                <CustomCell field={f} values={inv.custom_values} />
                                            </TD>
                                        ))}
                                        <TD align="right">
                                            <span className="font-medium text-slate-900 tabular-nums">{formatMoney(inv.total, currency)}</span>
                                            {inv.balance > 0 && inv.balance < inv.total ? <span className="block text-xs text-slate-500">{formatMoney(inv.balance, currency)} due</span> : null}
                                        </TD>
                                        <TD>
                                            <Dropdown
                                                items={[
                                                    { label: 'View', icon: Eye, href: route('invoices.show', inv.id) },
                                                    { label: 'Edit', icon: Pencil, href: route('invoices.edit', inv.id), hidden: inv.status === 'paid' },
                                                    { label: 'Download PDF', icon: Download, href: route('invoices.download', inv.id), download: true },
                                                    { label: 'Email to client', icon: Send, href: route('invoices.email', inv.id), method: 'post', hidden: !inv.client_email },
                                                    { label: 'Duplicate', icon: Copy, href: route('invoices.duplicate', inv.id), method: 'post' },
                                                    { separator: true, hidden: inv.status === 'paid' },
                                                    { label: 'Delete', icon: Trash2, danger: true, onClick: () => destroy(inv), hidden: inv.status === 'paid' },
                                                ]}
                                            />
                                        </TD>
                                    </TR>
                                ))}
                            </TBody>
                        </Table>
                        <Pagination paginator={invoices} />
                    </>
                ) : (
                    <EmptyState
                        icon={FileText}
                        title={filtered ? 'No invoices match these filters' : 'No invoices yet'}
                        description={filtered ? 'Try clearing a filter.' : 'Create your first invoice and get paid online.'}
                        action={
                            filtered ? (
                                <Button variant="secondary" href={route('invoices.index')}>
                                    Clear filters
                                </Button>
                            ) : (
                                <Button href={route('invoices.create')} icon={FilePlus2}>
                                    New invoice
                                </Button>
                            )
                        }
                    />
                )}
            </Card>
        </>
    );
}
