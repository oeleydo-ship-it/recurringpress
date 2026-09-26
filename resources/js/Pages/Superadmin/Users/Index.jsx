import { router } from '@inertiajs/react';
import { Eye, Pencil, Trash2, Users } from 'lucide-react';
import {
    Avatar,
    Badge,
    Card,
    Dropdown,
    EmptyState,
    FilterBar,
    PageHeader,
    Pagination,
    SearchInput,
    Select,
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
import { formatDate, titleCase } from '@/lib/utils';

export default function SuperadminUsers({ users, filters: initial, plans }) {
    const confirm = useConfirm();
    const [filters, setFilter] = useFilters(route('superadmin.users.index'), initial);

    const destroy = async (u) => {
        if (await confirm({ title: `Delete ${u.name}?`, message: 'The user loses access immediately. Company data is kept.', confirmText: 'Delete user' })) {
            router.delete(route('superadmin.users.destroy', u.id), { preserveScroll: true });
        }
    };

    return (
        <>
            <PageHeader title="Tenants & users" description={`${users.total} user${users.total === 1 ? '' : 's'} across all companies`} />
            <Card>
                <FilterBar>
                    <SearchInput value={filters.search} onChange={(v) => setFilter('search', v)} placeholder="Name, email or company…" className="sm:max-w-sm sm:flex-1" />
                    <Select value={filters.plan ?? ''} onChange={(e) => setFilter('plan', e.target.value)} className="sm:w-48" options={{ '': 'All plans', ...plans }} />
                </FilterBar>
                {users.data.length ? (
                    <>
                        <Table>
                            <THead>
                                <TH>User</TH>
                                <TH>Company</TH>
                                <TH className="hidden md:table-cell">Plan</TH>
                                <TH className="hidden lg:table-cell">Joined</TH>
                                <TH className="w-10" />
                            </THead>
                            <TBody>
                                {users.data.map((u) => (
                                    <TR key={u.id} href={route('superadmin.users.show', u.id)}>
                                        <TD>
                                            <div className="flex items-center gap-3">
                                                <Avatar src={u.avatar_url} name={u.name} size="sm" />
                                                <div className="min-w-0">
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-medium text-slate-900">{u.name}</span>
                                                        {!u.is_active ? <Badge tone="red">Disabled</Badge> : null}
                                                    </div>
                                                    <span className="text-xs text-slate-500">{u.email}</span>
                                                </div>
                                            </div>
                                        </TD>
                                        <TD>
                                            <span className="block max-w-[200px] truncate">{u.company_name ?? '—'}</span>
                                            <span className="text-xs text-slate-500">{titleCase(u.role ?? '')}</span>
                                        </TD>
                                        <TD className="hidden md:table-cell">
                                            <div className="flex items-center gap-2">
                                                {u.plan ? <Badge tone="brand">{u.plan}</Badge> : '—'}
                                                {u.subscription_status ? <StatusBadge status={u.subscription_status} /> : null}
                                            </div>
                                        </TD>
                                        <TD className="hidden text-slate-500 lg:table-cell">{formatDate(u.created_at)}</TD>
                                        <TD>
                                            <Dropdown
                                                items={[
                                                    { label: 'View', icon: Eye, href: route('superadmin.users.show', u.id) },
                                                    { label: 'Edit', icon: Pencil, href: route('superadmin.users.edit', u.id) },
                                                    { separator: true },
                                                    { label: 'Delete', icon: Trash2, danger: true, onClick: () => destroy(u) },
                                                ]}
                                            />
                                        </TD>
                                    </TR>
                                ))}
                            </TBody>
                        </Table>
                        <Pagination paginator={users} />
                    </>
                ) : (
                    <EmptyState icon={Users} title="No users found" />
                )}
            </Card>
        </>
    );
}
