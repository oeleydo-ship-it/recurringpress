import { Dialog, DialogBackdrop, DialogPanel } from '@headlessui/react';
import { Link, usePage } from '@inertiajs/react';
import {
    Activity,
    BarChart3,
    CreditCard,
    Download,
    FileStack,
    Gauge,
    Globe,
    Landmark,
    LayoutTemplate,
    ListTree,
    LogOut,
    Mail,
    Menu as MenuIcon,
    Newspaper,
    Package,
    Settings2,
    Shield,
    Sparkles,
    UserCog,
    Users,
    Wallet,
    X,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { Avatar, Dropdown } from '@/Components/ui';
import { isCurrent, route } from '@/lib/route';
import { useApplyTheme } from '@/lib/theme';

const ADMIN_THEME = { primary: '#4f46e5', sidebar: '#020617', sidebar_text: '#e2e8f0', font: 'Inter', font_size: 'base' };
import { cn } from '@/lib/utils';

const groups = [
    { label: 'Overview', items: [{ label: 'Dashboard', route: 'superadmin.dashboard', icon: Gauge }] },
    {
        label: 'Business',
        items: [
            { label: 'Tenants', route: 'superadmin.users.index', pattern: 'superadmin.users.*', icon: Users },
            { label: 'Plans', route: 'superadmin.plans.index', pattern: 'superadmin.plans.*', icon: Package },
            { label: 'Payouts', route: 'superadmin.payouts.index', pattern: 'superadmin.payouts.*', icon: Wallet },
            { label: 'Reports', route: 'superadmin.reports.index', pattern: 'superadmin.reports.*', icon: BarChart3 },
        ],
    },
    {
        label: 'Content',
        items: [
            { label: 'Pages', route: 'superadmin.pages.index', pattern: 'superadmin.pages.*', icon: FileStack },
            { label: 'Blog posts', route: 'superadmin.posts.index', pattern: 'superadmin.posts.*', icon: Newspaper },
            { label: 'Menus', route: 'superadmin.menus.index', pattern: 'superadmin.menus.*', icon: ListTree },
            { label: 'Landing page', route: 'superadmin.settings.landing', icon: LayoutTemplate },
        ],
    },
    {
        label: 'System',
        items: [
            { label: 'General', route: 'superadmin.settings.general', icon: Settings2 },
            { label: 'Stripe', route: 'superadmin.settings.stripe', icon: CreditCard },
            { label: 'Email (SMTP)', route: 'superadmin.settings.smtp', icon: Mail },
            { label: 'Custom domains', route: 'superadmin.settings.custom-domains', icon: Globe },
            { label: 'AI (Claude)', route: 'superadmin.settings.ai', icon: Sparkles },
            { label: 'Payout settings', route: 'superadmin.settings.payouts', icon: Landmark },
            { label: 'Updates', route: 'superadmin.updates.index', icon: Download },
            { label: 'Queues (Horizon)', href: '/horizon', icon: Activity, external: true },
        ],
    },
];

export default function SuperadminLayout({ children }) {
    const { superadmin, site } = usePage().props;
    const { url } = usePage();
    const [open, setOpen] = useState(false);
    // Fixed platform theme (also on <html> so modals/menus rendered in portals match).
    useApplyTheme(ADMIN_THEME);

    useEffect(() => setOpen(false), [url]);
    useEffect(() => {
        document.body.dataset.site = site?.name ?? '';
    }, [site?.name]);

    return (
        <div className="min-h-screen font-sans" style={{ '--brand': '#4f46e5' }}>
            <Dialog open={open} onClose={setOpen} className="relative z-50 lg:hidden">
                <DialogBackdrop transition className="fixed inset-0 bg-slate-900/60 transition-opacity data-closed:opacity-0" />
                <div className="fixed inset-0 flex">
                    <DialogPanel transition className="relative mr-16 flex w-full max-w-xs flex-1 transition duration-300 data-closed:-translate-x-full">
                        <button type="button" onClick={() => setOpen(false)} className="absolute top-4 -right-12 p-2 text-white" aria-label="Close sidebar">
                            <X className="size-6" />
                        </button>
                        <Nav url={url} site={site} />
                    </DialogPanel>
                </div>
            </Dialog>

            <div className="hidden lg:fixed lg:inset-y-0 lg:z-40 lg:flex lg:w-64 lg:flex-col">
                <Nav url={url} site={site} />
            </div>

            <div className="lg:pl-64">
                <div className="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200/80 bg-white/85 px-4 backdrop-blur-md sm:px-6 lg:px-8">
                    <button type="button" onClick={() => setOpen(true)} className="-m-2 rounded-lg p-2 text-slate-600 lg:hidden" aria-label="Open sidebar">
                        <MenuIcon className="size-6" />
                    </button>
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-600/20">
                        <Shield className="size-3.5" /> Superadmin
                    </span>
                    <div className="flex-1" />
                    <a href={route('landing')} target="_blank" rel="noreferrer" className="hidden items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800 sm:inline-flex">
                        <Globe className="size-4" /> View site
                    </a>
                    <Dropdown
                        trigger={
                            <button type="button" className="rounded-full p-0.5 hover:bg-slate-100">
                                <Avatar src={superadmin?.avatar_url} name={superadmin?.name} size="sm" />
                            </button>
                        }
                        items={[
                            { label: 'Your account', icon: UserCog, href: route('superadmin.account') },
                            { separator: true },
                            { label: 'Sign out', icon: LogOut, href: route('superadmin.logout'), method: 'post', danger: true },
                        ]}
                    />
                </div>
                <main className="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                    <div className="animate-slide-up">{children}</div>
                </main>
            </div>
        </div>
    );
}

function Nav({ url, site }) {
    return (
        <div className="flex grow flex-col overflow-y-auto bg-slate-950 scrollbar-thin">
            <div className="flex h-16 shrink-0 items-center gap-3 px-5">
                <span className="flex size-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-sky-500">
                    <Shield className="size-4 text-white" />
                </span>
                <span className="truncate font-semibold text-white">{site?.name ?? 'Admin'}</span>
            </div>
            <nav className="flex-1 space-y-6 px-3 pb-6">
                {groups.map((g) => (
                    <div key={g.label}>
                        <div className="px-3 pb-1.5 text-[11px] font-semibold tracking-wider text-slate-500 uppercase">{g.label}</div>
                        <ul className="space-y-0.5">
                            {g.items.map((item) => {
                                const Icon = item.icon;
                                const active = item.route ? isCurrent(item.pattern ?? item.route, url) : false;
                                const cls = cn(
                                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                    active ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white',
                                );
                                return (
                                    <li key={item.label}>
                                        {item.external ? (
                                            <a href={item.href} className={cls}>
                                                <Icon className="size-[18px]" />
                                                {item.label}
                                            </a>
                                        ) : (
                                            <Link href={route(item.route)} className={cls}>
                                                <Icon className="size-[18px]" />
                                                {item.label}
                                            </Link>
                                        )}
                                    </li>
                                );
                            })}
                        </ul>
                    </div>
                ))}
            </nav>
        </div>
    );
}
