import { router } from '@inertiajs/react';
import { Crown, Pencil, ShieldCheck, Trash2, User, UserPlus, Users } from 'lucide-react';
import { Avatar, Badge, Button, Card, Dropdown, EmptyState, PageHeader, Table, TBody, TD, TH, THead, TR, useConfirm } from '@/Components/ui';
import { route } from '@/lib/route';
import { formatDate } from '@/lib/utils';

const roleMeta = {
    owner: { tone: 'purple', icon: Crown },
    admin: { tone: 'brand', icon: ShieldCheck },
    member: { tone: 'gray', icon: User },
};

export default function UsersIndex({ users, roles, limit }) {
    const confirm = useConfirm();
    const atLimit = limit.max && limit.used >= limit.max;

    const destroy = async (u) => {
        if (await confirm({ title: `Remove ${u.name}?`, message: 'They will lose access immediately.', confirmText: 'Remove member' })) {
            router.delete(route('users.destroy', u.id), { preserveScroll: true });
        }
    };

    return (
        <>
            <PageHeader
                title="Team"
                description={limit.max ? `${limit.used} of ${limit.max} seats used` : `${limit.used} member${limit.used === 1 ? '' : 's'}`}
                actions={
                    <Button href={route('users.create')} icon={UserPlus} disabled={atLimit} className={atLimit ? 'pointer-events-none opacity-50' : ''}>
                        Invite member
                    </Button>
                }
            />
            <Card>
                {users.data.length ? (
                    <Table>
                        <THead>
                            <TH>Member</TH>
                            <TH>Role</TH>
                            <TH className="hidden md:table-cell">Joined</TH>
                            <TH className="w-10" />
                        </THead>
                        <TBody>
                            {users.data.map((u) => {
                                const meta = roleMeta[u.role] ?? roleMeta.member;
                                const Icon = meta.icon;
                                return (
                                    <TR key={u.id}>
                                        <TD>
                                            <div className="flex items-center gap-3">
                                                <Avatar src={u.avatar_url} name={u.name} size="sm" />
                                                <div className="min-w-0">
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-medium text-slate-900">{u.name}</span>
                                                        {u.is_me ? <Badge tone="blue">You</Badge> : null}
                                                        {!u.is_active ? <Badge>Deactivated</Badge> : null}
                                                    </div>
                                                    <span className="text-xs text-slate-500">{u.email}</span>
                                                </div>
                                            </div>
                                        </TD>
                                        <TD>
                                            <Badge tone={meta.tone}>
                                                <Icon className="size-3" /> {roles[u.role] ?? u.role}
                                            </Badge>
                                        </TD>
                                        <TD className="hidden text-slate-500 md:table-cell">{formatDate(u.joined)}</TD>
                                        <TD>
                                            {u.can_manage ? (
                                                <Dropdown
                                                    items={[
                                                        { label: 'Edit', icon: Pencil, href: route('users.edit', u.id) },
                                                        { separator: true, hidden: u.role === 'owner' || u.is_me },
                                                        { label: 'Remove', icon: Trash2, danger: true, onClick: () => destroy(u), hidden: u.role === 'owner' || u.is_me },
                                                    ]}
                                                />
                                            ) : null}
                                        </TD>
                                    </TR>
                                );
                            })}
                        </TBody>
                    </Table>
                ) : (
                    <EmptyState icon={Users} title="Just you so far" description="Invite teammates to create invoices and manage clients." />
                )}
            </Card>
            <p className="mt-4 text-xs text-slate-500">Owners manage everyone. Admins can manage members, clients, invoices and settings. Members can work with clients and documents.</p>
        </>
    );
}
