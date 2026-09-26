import { Link } from '@inertiajs/react';
import { Banknote, Building2, FileText, Mail, Pencil, Phone, Users } from 'lucide-react';
import { Avatar, Badge, Button, Card, CardHeader, DescriptionItem, PageHeader, StatCard, StatusBadge } from '@/Components/ui';
import { route } from '@/lib/route';
import { formatDate, formatMoney, titleCase } from '@/lib/utils';

export default function SuperadminUserShow({ user, company }) {
    return (
        <>
            <PageHeader
                title={user.name}
                back={route('superadmin.users.index')}
                meta={
                    <>
                        <Badge tone="brand">{titleCase(user.role)}</Badge>
                        {!user.is_active ? <Badge tone="red">Disabled</Badge> : null}
                    </>
                }
                description={user.email}
                actions={
                    <Button href={route('superadmin.users.edit', user.id)} icon={Pencil} variant="secondary">
                        Edit
                    </Button>
                }
            />

            {company ? (
                <div className="mb-6 grid gap-4 sm:grid-cols-3">
                    <StatCard label="Invoices" value={company.stats.invoices} icon={FileText} tone="blue" />
                    <StatCard label="Clients" value={company.stats.clients} icon={Users} tone="purple" />
                    <StatCard label="Collected" value={formatMoney(company.stats.collected, company.currency || 'USD')} icon={Banknote} tone="green" />
                </div>
            ) : null}

            <div className="grid gap-6 lg:grid-cols-3">
                <Card>
                    <div className="flex items-center gap-4 border-b border-slate-100 p-5">
                        <Avatar src={user.avatar_url} name={user.name} size="lg" />
                        <div className="min-w-0">
                            <p className="truncate font-semibold text-slate-900">{user.name}</p>
                            <p className="truncate text-sm text-slate-500">{user.email}</p>
                        </div>
                    </div>
                    <dl className="space-y-4 p-5">
                        <DescriptionItem label="Email">
                            <span className="inline-flex items-center gap-1.5">
                                <Mail className="size-4 text-slate-400" /> {user.email} {user.email_verified ? <Badge tone="green">Verified</Badge> : <Badge tone="yellow">Unverified</Badge>}
                            </span>
                        </DescriptionItem>
                        {user.phone ? (
                            <DescriptionItem label="Phone">
                                <span className="inline-flex items-center gap-1.5">
                                    <Phone className="size-4 text-slate-400" /> {user.phone}
                                </span>
                            </DescriptionItem>
                        ) : null}
                        <DescriptionItem label="Joined">{formatDate(user.created_at)}</DescriptionItem>
                    </dl>
                </Card>

                {company ? (
                    <Card className="lg:col-span-2">
                        <CardHeader title={company.name} description={company.email} icon={Building2} actions={<StatusBadge status={company.status} />} />
                        <dl className="grid gap-5 p-5 sm:grid-cols-3">
                            <DescriptionItem label="Plan">{company.plan}</DescriptionItem>
                            <DescriptionItem label="Renews / ends">{company.subscription_ends_at ? formatDate(company.subscription_ends_at) : '—'}</DescriptionItem>
                            <DescriptionItem label="Currency">{company.currency}</DescriptionItem>
                            <DescriptionItem label="Onboarding">{company.onboarding_completed ? 'Completed' : 'In progress'}</DescriptionItem>
                            <DescriptionItem label="Created">{formatDate(company.created_at)}</DescriptionItem>
                        </dl>
                        <div className="border-t border-slate-100">
                            <p className="px-5 pt-4 text-xs font-semibold tracking-wide text-slate-500 uppercase">Team ({company.members.length})</p>
                            <ul className="divide-y divide-slate-100">
                                {company.members.map((m) => (
                                    <li key={m.id}>
                                        <Link href={route('superadmin.users.show', m.id)} className="flex items-center justify-between px-5 py-3 text-sm hover:bg-slate-50">
                                            <span>
                                                <span className="font-medium text-slate-900">{m.name}</span> <span className="text-slate-500">{m.email}</span>
                                            </span>
                                            <Badge>{titleCase(m.role)}</Badge>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </Card>
                ) : (
                    <Card className="p-5 text-sm text-slate-500 lg:col-span-2">This user doesn’t belong to a company.</Card>
                )}
            </div>
        </>
    );
}
