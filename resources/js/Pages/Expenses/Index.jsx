import { router, usePage } from '@inertiajs/react';
import { Paperclip, Pencil, Plus, Trash2, Wallet } from 'lucide-react';
import {
    Badge,
    Button,
    Card,
    CardHeader,
    Dropdown,
    EmptyState,
    FilterBar,
    Input,
    PageHeader,
    Pagination,
    SearchInput,
    Select,
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
import { formatDate, formatMoney } from '@/lib/utils';

export default function ExpensesIndex({ expenses, filters: initial, categories, totals, byCategory, customColumns = [] }) {
    const { auth } = usePage().props;
    const currency = auth.company?.currency;
    const df = auth.company?.date_format;
    const confirm = useConfirm();
    const [filters, setFilter] = useFilters(route('expenses.index'), initial);
    const filtered = Object.values(filters).some(Boolean);
    const topCategories = Object.entries(byCategory).slice(0, 5);
    const maxCat = Math.max(1, ...Object.values(byCategory));

    const destroy = async (e) => {
        if (await confirm({ title: `Delete “${e.title}”?`, confirmText: 'Delete expense' })) router.delete(route('expenses.destroy', e.id), { preserveScroll: true });
    };

    return (
        <>
            <PageHeader
                title="Expenses"
                description={
                    <>
                        <span className="font-medium text-slate-700">{formatMoney(totals.amount + totals.tax, currency)}</span> across {totals.count} expense{totals.count === 1 ? '' : 's'}
                        {filtered ? ' (filtered)' : ''}
                    </>
                }
                actions={
                    <Button href={route('expenses.create')} icon={Plus}>
                        Add expense
                    </Button>
                }
            />

            <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_300px]">
                <Card className="min-w-0">
                    <FilterBar className="flex-wrap">
                        <SearchInput value={filters.search} onChange={(v) => setFilter('search', v)} placeholder="Title, vendor, reference…" className="sm:max-w-xs sm:flex-1" />
                        <Select value={filters.category ?? ''} onChange={(e) => setFilter('category', e.target.value)} className="sm:w-52" options={{ '': 'All categories', ...categories }} />
                        <div className="flex items-center gap-2">
                            <Input type="date" value={filters.from ?? ''} onChange={(e) => setFilter('from', e.target.value)} className="sm:w-40" aria-label="From" />
                            <span className="text-slate-400">–</span>
                            <Input type="date" value={filters.to ?? ''} onChange={(e) => setFilter('to', e.target.value)} className="sm:w-40" aria-label="To" />
                        </div>
                    </FilterBar>
                    {expenses.data.length ? (
                        <>
                            <Table>
                                <THead>
                                    <TH>Expense</TH>
                                    <TH className="hidden md:table-cell">Category</TH>
                                    <TH className="hidden sm:table-cell">Date</TH>
                                    {customColumns.map((f) => (
                                        <TH key={f.key} className="hidden lg:table-cell">
                                            {f.label}
                                        </TH>
                                    ))}
                                    <TH align="right">Amount</TH>
                                    <TH className="w-10" />
                                </THead>
                                <TBody>
                                    {expenses.data.map((e) => (
                                        <TR key={e.id} href={route('expenses.show', e.id)}>
                                            <TD>
                                                <div className="flex items-center gap-2">
                                                    <span className="max-w-[240px] truncate font-medium text-slate-900">{e.title}</span>
                                                    {e.has_receipt ? <Paperclip className="size-3.5 text-slate-400" aria-label="Has receipt" /> : null}
                                                    {e.is_billable ? <Badge tone="blue">Billable</Badge> : null}
                                                </div>
                                                <span className="text-xs text-slate-500">
                                                    {e.expense_number}
                                                    {e.vendor ? ` · ${e.vendor}` : ''}
                                                </span>
                                            </TD>
                                            <TD className="hidden md:table-cell">
                                                <Badge tone="gray">{e.category_label}</Badge>
                                            </TD>
                                            <TD className="hidden text-slate-500 sm:table-cell">{formatDate(e.expense_date, df)}</TD>
                                            {customColumns.map((f) => (
                                                <TD key={f.key} className="hidden max-w-48 truncate lg:table-cell">
                                                    <CustomCell field={f} values={e.custom_values} />
                                                </TD>
                                            ))}
                                            <TD align="right">
                                                <span className="font-medium text-slate-900 tabular-nums">{formatMoney(e.total, currency)}</span>
                                                {e.tax_amount > 0 ? <span className="block text-xs text-slate-500">incl. {formatMoney(e.tax_amount, currency)} tax</span> : null}
                                            </TD>
                                            <TD>
                                                <Dropdown
                                                    items={[
                                                        { label: 'Edit', icon: Pencil, href: route('expenses.edit', e.id) },
                                                        { separator: true },
                                                        { label: 'Delete', icon: Trash2, danger: true, onClick: () => destroy(e) },
                                                    ]}
                                                />
                                            </TD>
                                        </TR>
                                    ))}
                                </TBody>
                            </Table>
                            <Pagination paginator={expenses} />
                        </>
                    ) : (
                        <EmptyState
                            icon={Wallet}
                            title={filtered ? 'No expenses match' : 'No expenses yet'}
                            description={filtered ? 'Try a different filter.' : 'Track costs to see real profit in your reports.'}
                            action={
                                !filtered ? (
                                    <Button href={route('expenses.create')} icon={Plus}>
                                        Add expense
                                    </Button>
                                ) : null
                            }
                        />
                    )}
                </Card>

                <Card className="h-fit">
                    <CardHeader title="Top categories" />
                    {topCategories.length ? (
                        <ul className="space-y-4 p-5">
                            {topCategories.map(([key, total]) => (
                                <li key={key}>
                                    <div className="mb-1 flex justify-between text-sm">
                                        <span className="text-slate-600">{categories[key] ?? key}</span>
                                        <span className="font-medium text-slate-900">{formatMoney(total, currency)}</span>
                                    </div>
                                    <div className="h-1.5 overflow-hidden rounded-full bg-slate-100">
                                        <div className="h-full rounded-full bg-brand-500" style={{ width: `${(total / maxCat) * 100}%` }} />
                                    </div>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="p-5 text-sm text-slate-500">No data yet.</p>
                    )}
                </Card>
            </div>
        </>
    );
}
