import { router, usePage } from '@inertiajs/react';
import { Building2, FilePlus2, Mail, Pencil, Trash2, User, UserPlus, Users } from 'lucide-react';
import {
    Avatar,
    Badge,
    Button,
    Card,
    Dropdown,
    EmptyState,
    FilterBar,
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
import { formatMoney } from '@/lib/utils';

export default function ClientsIndex({ clients, filters: initial, customColumns = [] }) {
    const { auth } = usePage().props;
    const currency = auth.company?.currency;
    const confirm = useConfirm();
    const [filters, setFilter] = useFilters(route('clients.index'), initial);
    const filtered = Boolean(filters.search || filters.status);

    const destroy = async (client) => {
        if (await confirm({ title: `Delete ${client.display_name}?`, message: 'Clients with invoices cannot be deleted — deactivate them instead.', confirmText: 'Delete client' })) {
            router.delete(route('clients.destroy', client.id), { preserveScroll: true });
        }
    };

    return (
        <>
            <PageHeader
                title="Clients"
                description="Everyone you bill, in one place."
                actions={
                    <Button href={route('clients.create')} icon={UserPlus}>
                        Add client
                    </Button>
                }
            />

            <Card>
                <FilterBar>
                    <SearchInput value={filters.search} onChange={(v) => setFilter('search', v)} placeholder="Search name, email or phone…" className="sm:max-w-xs sm:flex-1" />
                    <Select value={filters.status ?? ''} onChange={(e) => setFilter('status', e.target.value)} className="sm:w-40" options={{ '': 'All clients', active: 'Active', inactive: 'Inactive' }} />
                </FilterBar>

                {clients.data.length ? (
                    <>
                        <Table>
                            <THead>
                                <TH>Client</TH>
                                <TH className="hidden md:table-cell">Contact</TH>
                                <TH className="hidden lg:table-cell">Location</TH>
                                {customColumns.map((f) => (
                                    <TH key={f.key} className="hidden lg:table-cell">
                                        {f.label}
                                    </TH>
                                ))}
                                <TH align="right">Invoices</TH>
                                <TH align="right">Billed</TH>
                                <TH className="w-10" />
                            </THead>
                            <TBody>
                                {clients.data.map((c) => (
                                    <TR key={c.id} href={route('clients.show', c.id)}>
                                        <TD>
                                            <div className="flex items-center gap-3">
                                                <Avatar name={c.display_name} size="sm" />
                                                <div className="min-w-0">
                                                    <div className="flex items-center gap-2">
                                                        <span className="truncate font-medium text-slate-900">{c.display_name}</span>
                                                        {!c.is_active ? <Badge>Inactive</Badge> : null}
                                                    </div>
                                                    <span className="flex items-center gap-1 text-xs text-slate-500">
                                                        {c.client_type === 'company' ? <Building2 className="size-3" /> : <User className="size-3" />}
                                                        {c.client_type === 'company' ? c.name : 'Individual'}
                                                    </span>
                                                </div>
                                            </div>
                                        </TD>
                                        <TD className="hidden md:table-cell">
                                            <div className="text-slate-700">{c.email ?? '—'}</div>
                                            <div className="text-xs text-slate-500">{c.phone}</div>
                                        </TD>
                                        <TD className="hidden text-slate-500 lg:table-cell">{[c.city, c.country].filter(Boolean).join(', ') || '—'}</TD>
                                        {customColumns.map((f) => (
                                            <TD key={f.key} className="hidden max-w-48 truncate lg:table-cell">
                                                <CustomCell field={f} values={c.custom_values} />
                                            </TD>
                                        ))}
                                        <TD align="right">{c.invoices_count}</TD>
                                        <TD align="right" className="font-medium text-slate-900">
                                            {formatMoney(c.total_billed, currency)}
                                        </TD>
                                        <TD>
                                            <Dropdown
                                                items={[
                                                    { label: 'New invoice', icon: FilePlus2, href: route('invoices.create', { client_id: c.id }) },
                                                    { label: 'Edit', icon: Pencil, href: route('clients.edit', c.id) },
                                                    { label: 'Email', icon: Mail, href: c.email ? `mailto:${c.email}` : undefined, external: true, hidden: !c.email },
                                                    { separator: true },
                                                    { label: 'Delete', icon: Trash2, danger: true, onClick: () => destroy(c) },
                                                ]}
                                            />
                                        </TD>
                                    </TR>
                                ))}
                            </TBody>
                        </Table>
                        <Pagination paginator={clients} />
                    </>
                ) : (
                    <EmptyState
                        icon={Users}
                        title={filtered ? 'No clients match your filters' : 'No clients yet'}
                        description={filtered ? 'Try a different search.' : 'Add your first client to start invoicing.'}
                        action={
                            !filtered ? (
                                <Button href={route('clients.create')} icon={UserPlus}>
                                    Add client
                                </Button>
                            ) : null
                        }
                    />
                )}
            </Card>
        </>
    );
}
