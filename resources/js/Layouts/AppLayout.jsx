import { Dialog, DialogBackdrop, DialogPanel } from '@headlessui/react';
import { Link, usePage } from '@inertiajs/react';
import {
    BarChart3,
    Box,
    ChevronDown,
    CreditCard,
    FileText,
    FileSignature,
    LayoutDashboard,
    LogOut,
    Menu as MenuIcon,
    Plus,
    Receipt,
    Repeat,
    Settings,
    Sparkles,
    User,
    UserCog,
    Users,
    Wallet,
    Landmark,
    X,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import AiChat from '@/Components/ai/AiChat';
import NotificationBell from '@/Components/notifications/NotificationBell';
import { Avatar, Button, Dropdown } from '@/Components/ui';
import { isCurrent, route } from '@/lib/route';
import { useApplyTheme } from '@/lib/theme';
import { cn, formatDate } from '@/lib/utils';

function navigation(company, user) {
    const groups = [
        { label: 'Overview', items: [{ label: 'Dashboard', route: 'dashboard', pattern: 'dashboard', icon: LayoutDashboard }] },
        {
            label: 'Sales',
            items: [
                { label: 'Invoices', route: 'invoices.index', pattern: 'invoices.*', icon: FileText },
                { label: 'Recurring', route: 'recurring-invoices.index', pattern: 'recurring-invoices.*', icon: Repeat },
                { label: 'Quotations', route: 'quotations.index', pattern: 'quotations.*', icon: FileSignature },
                { label: 'Clients', route: 'clients.index', pattern: 'clients.*', icon: Users },
                { label: 'Products', route: 'products.index', pattern: 'products.*', icon: Box },
            ],
        },
        {
            label: 'Finance',
            items: [
                { label: 'Transactions', route: 'payments.index', pattern: 'payments.*', icon: CreditCard },
                { label: 'Receipts', route: 'receipts.index', pattern: 'receipts.*', icon: Receipt },
                { label: 'Expenses', route: 'expenses.index', pattern: 'expenses.*', icon: Wallet },
                { label: 'Reports', route: 'reports.index', pattern: 'reports.*', icon: BarChart3 },
                ...(company?.show_payouts ? [{ label: 'Payouts', route: 'payouts.index', pattern: 'payouts.*', icon: Landmark }] : []),
            ],
        },
    ];
    if (user?.is_admin) {
        groups.push({
            label: 'Workspace',
            items: [
                { label: 'Team', route: 'users.index', pattern: 'users.*', icon: UserCog },
                { label: 'Settings', route: 'settings.index', pattern: 'settings.*', icon: Settings },
            ],
        });
    }
    return groups;
}

export default function AppLayout({ children }) {
    const { auth, site } = usePage().props;
    const { url } = usePage();
    const [mobileOpen, setMobileOpen] = useState(false);
    const company = auth?.company;
    const user = auth?.user;
    const theme = auth?.theme ?? {};

    useEffect(() => setMobileOpen(false), [url]);

    const style = useApplyTheme(theme);

    useEffect(() => {
        document.body.dataset.site = site?.name ?? '';
    }, [site?.name]);

    const groups = navigation(company, user);

    return (
        <div style={style} className="min-h-screen font-sans">
            {/* Mobile sidebar */}
            <Dialog open={mobileOpen} onClose={setMobileOpen} className="relative z-50 lg:hidden">
                <DialogBackdrop transition className="fixed inset-0 bg-slate-900/50 transition-opacity duration-300 data-closed:opacity-0" />
                <div className="fixed inset-0 flex" style={style}>
                    <DialogPanel transition className="relative mr-16 flex w-full max-w-xs flex-1 transform transition duration-300 ease-in-out data-closed:-translate-x-full">
                        <button type="button" onClick={() => setMobileOpen(false)} className="absolute top-4 -right-12 rounded-lg p-2 text-white" aria-label="Close sidebar">
                            <X className="size-6" />
                        </button>
                        <Sidebar company={company} user={user} groups={groups} url={url} />
                    </DialogPanel>
                </div>
            </Dialog>

            {/* Desktop sidebar */}
            <div className="hidden lg:fixed lg:inset-y-0 lg:z-40 lg:flex lg:w-64 lg:flex-col">
                <Sidebar company={company} user={user} groups={groups} url={url} />
            </div>

            <div className="lg:pl-64">
                <TopBar user={user} company={company} onMenu={() => setMobileOpen(true)} />
                {company?.on_trial ? <TrialBanner company={company} /> : null}
                <main className="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                    <div className="animate-slide-up">{children}</div>
                </main>
            </div>
        </div>
    );
}

function Sidebar({ company, user, groups, url }) {
    return (
        <div className="flex grow flex-col overflow-y-auto border-r border-black/5 bg-sidebar scrollbar-thin">
            <div className="flex h-16 shrink-0 items-center gap-3 px-5">
                <Link href={route('dashboard')} className="flex min-w-0 items-center gap-3">
                    {company?.logo_url ? (
                        <img src={company.logo_url} alt={company.name} className="h-9 w-auto max-w-[140px] object-contain" />
                    ) : (
                        <>
                            <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-600 font-bold text-brand-contrast shadow-sm">
                                {(company?.name ?? 'R').slice(0, 1).toUpperCase()}
                            </span>
                            <span className="truncate text-base font-semibold text-sidebar-text">{company?.name}</span>
                        </>
                    )}
                </Link>
            </div>

            <nav className="flex flex-1 flex-col px-3 pb-4">
                <ul className="flex flex-1 flex-col gap-y-6">
                    {groups.map((group) => (
                        <li key={group.label}>
                            <div className="px-3 pb-1.5 text-[11px] font-semibold tracking-wider text-sidebar-text uppercase opacity-50">{group.label}</div>
                            <ul className="space-y-0.5">
                                {group.items.map((item) => {
                                    const active = isCurrent(item.pattern, url);
                                    const Icon = item.icon;
                                    return (
                                        <li key={item.route}>
                                            <Link
                                                href={route(item.route)}
                                                className={cn(
                                                    'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                                    active
                                                        ? 'bg-[color-mix(in_oklab,var(--brand)_14%,var(--sidebar))] text-[color-mix(in_oklab,var(--brand)_55%,var(--sidebar-text))]'
                                                        : 'text-sidebar-text/75 hover:bg-[color-mix(in_oklab,var(--sidebar-text)_7%,var(--sidebar))] hover:text-sidebar-text',
                                                )}
                                            >
                                                <Icon className={cn('size-[18px] shrink-0', active ? 'opacity-100' : 'opacity-60 group-hover:opacity-90')} />
                                                {item.label}
                                            </Link>
                                        </li>
                                    );
                                })}
                            </ul>
                        </li>
                    ))}
                </ul>

                <Link
                    href={route('profile')}
                    className="mt-6 flex items-center gap-3 rounded-xl p-2 transition-colors hover:bg-[color-mix(in_oklab,var(--sidebar-text)_7%,var(--sidebar))]"
                >
                    <Avatar src={user?.avatar_url} name={user?.name} size="sm" />
                    <span className="min-w-0">
                        <span className="block truncate text-sm font-medium text-sidebar-text">{user?.name}</span>
                        <span className="block truncate text-xs text-sidebar-text opacity-60">{company?.plan ? `${company.plan} plan` : user?.email}</span>
                    </span>
                </Link>
            </nav>
        </div>
    );
}

function TopBar({ user, onMenu }) {
    return (
        <div className="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-3 border-b border-slate-200/80 bg-white/85 px-4 backdrop-blur-md sm:px-6 lg:px-8">
            <button type="button" onClick={onMenu} className="-m-2 rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Open sidebar">
                <MenuIcon className="size-6" />
            </button>
            <div className="flex-1" />

            <AiChat />

            <NotificationBell />

            <Dropdown
                width="w-56"
                trigger={
                    <Button icon={Plus} size="sm">
                        <span className="hidden sm:inline">Create</span>
                        <ChevronDown className="hidden size-3.5 opacity-70 sm:block" />
                    </Button>
                }
                items={[
                    { label: 'New invoice', icon: FileText, href: route('invoices.create') },
                    { label: 'New quotation', icon: FileSignature, href: route('quotations.create') },
                    { label: 'New recurring invoice', icon: Repeat, href: route('recurring-invoices.create') },
                    { separator: true },
                    { label: 'New client', icon: Users, href: route('clients.create') },
                    { label: 'New product', icon: Box, href: route('products.create') },
                    { label: 'New expense', icon: Wallet, href: route('expenses.create') },
                ]}
            />

            <Dropdown
                width="w-60"
                trigger={
                    <button type="button" className="flex items-center gap-2 rounded-full p-0.5 hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-brand-600">
                        <Avatar src={user?.avatar_url} name={user?.name} size="sm" />
                        <ChevronDown className="mr-1 hidden size-4 text-slate-400 sm:block" />
                    </button>
                }
                items={[
                    { label: user?.email ?? '', icon: User, href: route('profile') },
                    { label: 'Settings', icon: Settings, href: route('settings.index'), hidden: !user?.is_admin },
                    { label: 'Subscription', icon: Sparkles, href: route('settings.subscription'), hidden: !user?.is_owner },
                    { separator: true },
                    { label: 'Sign out', icon: LogOut, href: route('logout'), method: 'post', danger: true },
                ]}
            />
        </div>
    );
}

function TrialBanner({ company }) {
    return (
        <div className="border-b border-brand-100 bg-gradient-to-r from-brand-50 via-white to-brand-50">
            <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 px-4 py-2 text-sm sm:px-6 lg:px-8">
                <p className="flex items-center gap-2 text-brand-800">
                    <Sparkles className="size-4" />
                    You&apos;re on a free trial{company.subscription_ends_at ? ` until ${formatDate(company.subscription_ends_at)}` : ''}.
                </p>
                <Link href={route('settings.subscription')} className="font-semibold text-brand-700 hover:text-brand-800">
                    Choose a plan →
                </Link>
            </div>
        </div>
    );
}
