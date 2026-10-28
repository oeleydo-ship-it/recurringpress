import { router } from '@inertiajs/react';
import { Package, Pencil, Plus, Sparkles, Trash2 } from 'lucide-react';
import { Badge, Button, Card, Dropdown, EmptyState, PageHeader, Table, TBody, TD, TH, THead, TR, useConfirm } from '@/Components/ui';
import { route } from '@/lib/route';
import { formatMoney } from '@/lib/utils';

export default function SuperadminPlans({ plans }) {
    const confirm = useConfirm();
    const limit = (v) => (v != null ? v : '∞');

    const destroy = async (p) => {
        if (await confirm({ title: `Delete the ${p.name} plan?`, message: 'Plans with subscribed companies cannot be deleted.', confirmText: 'Delete plan' })) {
            router.delete(route('superadmin.plans.destroy', p.id), { preserveScroll: true });
        }
    };

    return (
        <>
            <PageHeader
                title="Subscription plans"
                description="Pricing and limits offered to tenants."
                actions={
                    <Button href={route('superadmin.plans.create')} icon={Plus}>
                        New plan
                    </Button>
                }
            />
            <Card>
                {plans.length ? (
                    <Table>
                        <THead>
                            <TH>Plan</TH>
                            <TH align="right">Price</TH>
                            <TH className="hidden md:table-cell" align="center">
                                Invoices / Clients / Users
                            </TH>
                            <TH align="right">Trial days</TH>
                            <TH align="right">Companies</TH>
                            <TH className="w-10" />
                        </THead>
                        <TBody>
                            {plans.map((p) => (
                                <TR key={p.id} href={route('superadmin.plans.edit', p.id)}>
                                    <TD>
                                        <div className="flex items-center gap-2">
                                            <span className="font-medium text-slate-900">{p.name}</span>
                                            {!p.is_active ? <Badge>Hidden</Badge> : null}
                                            {p.is_popular ? (
                                                <Badge tone="purple">
                                                    <Sparkles className="size-3" /> Popular
                                                </Badge>
                                            ) : null}
                                        </div>
                                        <span className="font-mono text-xs text-slate-500">{p.slug}</span>
                                    </TD>
                                    <TD align="right">
                                        <span className="font-medium text-slate-900">{p.price > 0 ? formatMoney(p.price, 'USD') : 'Free'}</span>
                                        {p.price > 0 ? <span className="block text-xs text-slate-500">per {p.billing_cycle === 'yearly' ? 'year' : 'month'}</span> : null}
                                    </TD>
                                    <TD className="hidden md:table-cell" align="center">
                                        {limit(p.max_invoices)} / {limit(p.max_clients)} / {limit(p.max_users)}
                                    </TD>
                                    <TD align="right">{p.price > 0 ? p.trial_days : 0}</TD>
                                    <TD align="right">{p.companies_count}</TD>
                                    <TD>
                                        <Dropdown
                                            items={[
                                                { label: 'Edit', icon: Pencil, href: route('superadmin.plans.edit', p.id) },
                                                { separator: true },
                                                { label: 'Delete', icon: Trash2, danger: true, onClick: () => destroy(p) },
                                            ]}
                                        />
                                    </TD>
                                </TR>
                            ))}
                        </TBody>
                    </Table>
                ) : (
                    <EmptyState icon={Package} title="No plans yet" />
                )}
            </Card>
        </>
    );
}
