import { router, usePage } from '@inertiajs/react';
import { Copy, Download, Eye, FilePlus2, FileSignature, Pencil, Send, Sheet, Trash2 } from 'lucide-react';
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

const TABS = [
    ['', 'All'],
    ['draft', 'Draft'],
    ['sent', 'Sent'],
    ['accepted', 'Accepted'],
    ['rejected', 'Rejected'],
    ['expired', 'Expired'],
    ['converted', 'Converted'],
];

export default function QuotationsIndex({ quotations, filters: initial, counts, summary }) {
    const { auth } = usePage().props;
    const currency = auth.company?.currency;
    const df = auth.company?.date_format;
    const confirm = useConfirm();
    const [filters, setFilter] = useFilters(route('quotations.index'), initial);
    const filtered = Boolean(filters.search || filters.status);

    const destroy = async (q) => {
        if (await confirm({ title: `Delete ${q.quotation_number}?`, confirmText: 'Delete quotation' })) {
            router.delete(route('quotations.destroy', q.id), { preserveScroll: true });
        }
    };

    return (
        <>
            <PageHeader
                title="Quotations"
                description={
                    <>
                        <span className="font-medium text-slate-700">{formatMoney(summary.open_value, currency)}</span> open ·{' '}
                        <span className="font-medium text-emerald-700">{formatMoney(summary.accepted_value, currency)}</span> won
                    </>
                }
                actions={
                    <>
                        <Button href={route('quotations.export', Object.fromEntries(Object.entries(filters).filter(([, v]) => v)))} external variant="secondary" icon={Sheet}>
                            Export CSV
                        </Button>
                        <Button href={route('quotations.create')} icon={FilePlus2}>
                            New quotation
                        </Button>
                    </>
                }
            />

            <LinkTabs
                className="mb-4"
                tabs={TABS.map(([key, label]) => ({
                    label,
                    count: key ? Number(counts[key] ?? 0) : Object.values(counts).reduce((a, b) => a + Number(b), 0),
                    active: (filters.status ?? '') === key,
                    href: route('quotations.index', { ...(filters.search ? { search: filters.search } : {}), ...(key ? { status: key } : {}) }),
                }))}
            />

            <Card>
                <FilterBar>
                    <SearchInput value={filters.search} onChange={(v) => setFilter('search', v)} placeholder="Quotation #, reference or client…" className="sm:max-w-sm sm:flex-1" />
                </FilterBar>
                {quotations.data.length ? (
                    <>
                        <Table>
                            <THead>
                                <TH>Quotation</TH>
                                <TH>Client</TH>
                                <TH className="hidden md:table-cell">Issued</TH>
                                <TH className="hidden md:table-cell">Valid until</TH>
                                <TH>Status</TH>
                                <TH align="right">Amount</TH>
                                <TH className="w-10" />
                            </THead>
                            <TBody>
                                {quotations.data.map((q) => (
                                    <TR key={q.id} href={route('quotations.show', q.id)}>
                                        <TD className="font-medium text-slate-900">{q.quotation_number}</TD>
                                        <TD>
                                            <span className="block max-w-[220px] truncate">{q.client_name ?? '—'}</span>
                                        </TD>
                                        <TD className="hidden text-slate-500 md:table-cell">{formatDate(q.issue_date, df)}</TD>
                                        <TD className={q.is_expired ? 'hidden font-medium text-red-600 md:table-cell' : 'hidden text-slate-500 md:table-cell'}>{formatDate(q.valid_until, df)}</TD>
                                        <TD>
                                            <div className="flex items-center gap-1.5">
                                                <StatusBadge status={q.is_expired ? 'expired' : q.status} />
                                                {q.viewed && q.status === 'sent' ? <Badge tone="purple">Viewed</Badge> : null}
                                            </div>
                                        </TD>
                                        <TD align="right" className="font-medium text-slate-900 tabular-nums">
                                            {formatMoney(q.total, currency)}
                                        </TD>
                                        <TD>
                                            <Dropdown
                                                items={[
                                                    { label: 'View', icon: Eye, href: route('quotations.show', q.id) },
                                                    { label: 'Edit', icon: Pencil, href: route('quotations.edit', q.id), hidden: q.status === 'converted' },
                                                    { label: 'Download PDF', icon: Download, href: route('quotations.download', q.id), download: true },
                                                    { label: 'Email to client', icon: Send, href: route('quotations.email', q.id), method: 'post', hidden: !q.client_email },
                                                    { label: 'Duplicate', icon: Copy, href: route('quotations.duplicate', q.id), method: 'post' },
                                                    { separator: true, hidden: q.status === 'converted' },
                                                    { label: 'Delete', icon: Trash2, danger: true, onClick: () => destroy(q), hidden: q.status === 'converted' },
                                                ]}
                                            />
                                        </TD>
                                    </TR>
                                ))}
                            </TBody>
                        </Table>
                        <Pagination paginator={quotations} />
                    </>
                ) : (
                    <EmptyState
                        icon={FileSignature}
                        title={filtered ? 'No quotations match' : 'No quotations yet'}
                        description={filtered ? 'Try a different filter.' : 'Send a quote — clients can accept it online and you convert it to an invoice in one click.'}
                        action={
                            !filtered ? (
                                <Button href={route('quotations.create')} icon={FilePlus2}>
                                    New quotation
                                </Button>
                            ) : null
                        }
                    />
                )}
            </Card>
        </>
    );
}
