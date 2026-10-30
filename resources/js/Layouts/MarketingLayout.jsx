import { Dialog, DialogPanel } from '@headlessui/react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowRight, FileText, Menu as MenuIcon, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import SmartLink from '@/Components/SmartLink';
import { Button } from '@/Components/ui';
import { route } from '@/lib/route';
import { cn } from '@/lib/utils';

const fallbackHeader = () => [
    { label: 'Features', url: route('landing.features') },
    { label: 'Pricing', url: route('landing.pricing') },
    { label: 'Updates', url: route('blog.index') },
    { label: 'FAQ', url: route('landing.faq') },
    { label: 'Contact', url: route('landing.contact') },
];

// Menu items that point at the blog get a small "New" pill, like a changelog link.
const isNews = (item) => /\/blog\b/.test(item.url ?? '') || /^(blog|updates|changelog)$/i.test(item.label ?? '');

export default function MarketingLayout({ children }) {
    const { site, marketing, auth } = usePage().props;
    const { url } = usePage();
    const [open, setOpen] = useState(false);
    const [scrolled, setScrolled] = useState(false);
    const menus = marketing?.menus ?? {};
    const header = menus.header?.length ? menus.header : fallbackHeader();
    const seo = marketing?.seo ?? {};

    useEffect(() => setOpen(false), [url]);
    useEffect(() => {
        document.body.dataset.site = site?.name ?? '';
        const onScroll = () => setScrolled(window.scrollY > 8);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, [site?.name]);

    const isActive = (href) => {
        try {
            const path = new URL(href, 'http://x').pathname;
            return path !== '/' && url.startsWith(path);
        } catch {
            return false;
        }
    };

    return (
        <div className="min-h-screen overflow-x-clip bg-white font-sans text-slate-700">
            <Head>
                {seo.description ? <meta name="description" content={seo.description} head-key="description" /> : null}
                {seo.keywords ? <meta name="keywords" content={seo.keywords} head-key="keywords" /> : null}
                <meta property="og:site_name" content={site?.name} />
                {seo.url ? <link rel="canonical" href={seo.url} head-key="canonical" /> : null}
                {seo.og_image ? <meta property="og:image" content={seo.og_image} head-key="og:image" /> : null}
                <meta name="twitter:card" content="summary_large_image" />
            </Head>

            <header className={cn('sticky top-0 z-40 transition-all duration-200', scrolled ? 'border-b border-slate-200/70 bg-white/85 backdrop-blur-lg' : 'bg-white')}>
                <nav className="mx-auto flex h-16 max-w-6xl items-center justify-between gap-6 px-4 sm:px-6 lg:px-8" aria-label="Main">
                    <div className="flex flex-1 items-center">
                        <Brand site={site} hideName={marketing?.hide_site_name} />
                    </div>

                    <div className="hidden items-center gap-1 lg:flex">
                        {header.map((item) => (
                            <SmartLink
                                key={item.label}
                                href={item.url ?? '#'}
                                className={cn('inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors', isActive(item.url) ? 'text-blue-600' : 'text-slate-600 hover:text-slate-900')}
                            >
                                {item.label}
                                {isNews(item) ? <span className="rounded-full bg-red-500 px-1.5 py-px text-[10px] leading-4 font-semibold text-white">New</span> : null}
                            </SmartLink>
                        ))}
                    </div>

                    <div className="hidden flex-1 items-center justify-end gap-2 lg:flex">
                        {auth?.user ? (
                            <Button href={route('dashboard')} size="sm" className="bg-blue-600 text-white hover:bg-blue-700">
                                Go to dashboard
                            </Button>
                        ) : (
                            <>
                                <Button href={route('register')} size="sm" className="bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">
                                    Sign up
                                </Button>
                                <Button href={route('login')} size="sm" className="bg-blue-600 text-white hover:bg-blue-700">
                                    Log in
                                </Button>
                            </>
                        )}
                    </div>

                    <button type="button" onClick={() => setOpen(true)} className="-m-2 rounded-lg p-2 text-slate-700 lg:hidden" aria-label="Open menu">
                        <MenuIcon className="size-6" />
                    </button>
                </nav>

                <Dialog open={open} onClose={setOpen} className="lg:hidden">
                    <div className="fixed inset-0 z-50 bg-slate-900/30" />
                    <DialogPanel className="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-white px-6 py-4 sm:max-w-sm sm:ring-1 sm:ring-slate-900/10">
                        <div className="flex items-center justify-between">
                            <Brand site={site} hideName={marketing?.hide_site_name} />
                            <button type="button" onClick={() => setOpen(false)} className="-m-2 rounded-lg p-2 text-slate-700" aria-label="Close menu">
                                <X className="size-6" />
                            </button>
                        </div>
                        <div className="mt-8 space-y-1">
                            {header.map((item) => (
                                <SmartLink key={item.label} href={item.url ?? '#'} className="flex items-center gap-2 rounded-lg px-3 py-2.5 text-base font-medium text-slate-800 hover:bg-slate-50">
                                    {item.label}
                                    {isNews(item) ? <span className="rounded-full bg-red-500 px-1.5 py-px text-[10px] leading-4 font-semibold text-white">New</span> : null}
                                </SmartLink>
                            ))}
                        </div>
                        <div className="mt-8 grid gap-2 border-t border-slate-100 pt-6">
                            {auth?.user ? (
                                <Button href={route('dashboard')} size="lg" className="bg-blue-600 text-white">
                                    Go to dashboard
                                </Button>
                            ) : (
                                <>
                                    <Button href={route('login')} size="lg" className="bg-blue-600 text-white hover:bg-blue-700">
                                        Log in
                                    </Button>
                                    <Button href={route('register')} size="lg" variant="secondary">
                                        Sign up
                                    </Button>
                                </>
                            )}
                        </div>
                    </DialogPanel>
                </Dialog>
            </header>

            <main>{children}</main>

            <Footer site={site} marketing={marketing} menus={menus} />
        </div>
    );
}

function Footer({ site, marketing, menus }) {
    const [email, setEmail] = useState('');
    const start = (e) => {
        e.preventDefault();
        router.visit(route('register', email ? { email } : {}));
    };

    const columns = [
        {
            title: 'Product',
            items: menus.footer_product?.length
                ? menus.footer_product
                : [
                      { label: 'Features', url: route('landing.features') },
                      { label: 'Pricing', url: route('landing.pricing') },
                      { label: 'FAQ', url: route('landing.faq') },
                  ],
        },
        {
            title: 'Company',
            items: menus.footer_company?.length
                ? menus.footer_company
                : [
                      { label: 'About', url: route('landing.about') },
                      { label: 'Blog', url: route('blog.index') },
                      { label: 'Contact', url: route('landing.contact') },
                  ],
        },
        {
            title: 'Get started',
            items: [
                { label: 'Create an account', url: route('register') },
                { label: 'Log in', url: route('login') },
                { label: 'Plans & pricing', url: route('landing.pricing') },
            ],
        },
        {
            title: 'Support',
            items: [
                { label: 'Help & FAQ', url: route('landing.faq') },
                { label: 'Contact support', url: route('landing.contact') },
                ...(menus.footer_legal ?? []),
            ],
        },
    ];

    return (
        <footer className="relative overflow-hidden border-t border-slate-100 bg-gradient-to-b from-white to-blue-50/60">
            <div className="mx-auto max-w-6xl px-4 pt-16 pb-10 sm:px-6 lg:px-8">
                <div className="grid gap-10 lg:grid-cols-[1fr_1.4fr]">
                    <div>
                        <Brand site={site} hideName={marketing?.hide_site_name} />
                        <p className="mt-4 max-w-xs text-sm leading-relaxed text-slate-500">{marketing?.footer_text}</p>
                    </div>
                    <div>
                        <h2 className="text-sm font-semibold text-slate-900">Start invoicing today</h2>
                        <p className="mt-1 text-sm text-slate-500">Enter your email to create a free account. No credit card required.</p>
                        <form onSubmit={start} className="mt-4 flex flex-col gap-2 sm:flex-row">
                            <label htmlFor="footer-email" className="sr-only">
                                Email address
                            </label>
                            <input
                                id="footer-email"
                                type="email"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                placeholder="Your email address"
                                autoComplete="email"
                                className="h-10 min-w-0 flex-1 rounded-lg border-0 bg-white px-3 text-sm text-slate-900 ring-1 ring-slate-200 ring-inset placeholder:text-slate-400 focus:ring-2 focus:ring-blue-600"
                            />
                            <button type="submit" className="inline-flex h-10 items-center justify-center gap-1.5 rounded-lg bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">
                                Get started <ArrowRight className="size-4" />
                            </button>
                        </form>
                    </div>
                </div>

                <div className="mt-14 grid grid-cols-2 gap-8 md:grid-cols-4">
                    {columns.map((c) => (
                        <FooterColumn key={c.title} title={c.title} items={c.items} />
                    ))}
                </div>

                <div className="mt-14 flex flex-col items-center justify-between gap-3 border-t border-slate-200/70 pt-8 text-xs text-slate-500 sm:flex-row">
                    <p>
                        © {new Date().getFullYear()} {site?.name}. All rights reserved.
                    </p>
                    <p>Invoices, quotes, recurring billing and online payments.</p>
                </div>
            </div>
        </footer>
    );
}

function Brand({ site, hideName }) {
    return (
        <Link href={route('landing')} className="flex items-center gap-2.5">
            {site?.logo ? (
                <img src={site.logo} alt={site?.name} className="h-8 w-auto" />
            ) : (
                <span className="flex size-8 items-center justify-center rounded-lg bg-blue-600 shadow-sm shadow-blue-600/30">
                    <FileText className="size-4 text-white" />
                </span>
            )}
            {!hideName ? <span className="text-base font-semibold tracking-tight text-slate-900">{site?.name}</span> : null}
        </Link>
    );
}

function FooterColumn({ title, items }) {
    return (
        <div>
            <h3 className="text-sm font-semibold text-slate-900">{title}</h3>
            <ul className="mt-4 space-y-3">
                {items.map((item) => (
                    <li key={item.label}>
                        <SmartLink href={item.url ?? '#'} className="text-sm text-slate-500 hover:text-blue-600">
                            {item.label}
                        </SmartLink>
                    </li>
                ))}
            </ul>
        </div>
    );
}
