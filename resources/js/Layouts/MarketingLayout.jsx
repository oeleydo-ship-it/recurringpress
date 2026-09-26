import { Dialog, DialogPanel } from '@headlessui/react';
import { Head, Link, usePage } from '@inertiajs/react';
import { FileText, Menu as MenuIcon, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import SmartLink from '@/Components/SmartLink';
import { Button } from '@/Components/ui';
import { route } from '@/lib/route';
import { cn } from '@/lib/utils';

const fallbackHeader = () => [
    { label: 'Features', url: route('landing.features') },
    { label: 'Pricing', url: route('landing.pricing') },
    { label: 'About', url: route('landing.about') },
    { label: 'Blog', url: route('blog.index') },
    { label: 'Contact', url: route('landing.contact') },
];

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
        <div className="min-h-screen bg-white font-sans text-slate-800">
            <Head>
                {seo.description ? <meta name="description" content={seo.description} head-key="description" /> : null}
                {seo.keywords ? <meta name="keywords" content={seo.keywords} head-key="keywords" /> : null}
                <meta property="og:site_name" content={site?.name} />
                {seo.url ? <link rel="canonical" href={seo.url} head-key="canonical" /> : null}
                {seo.og_image ? <meta property="og:image" content={seo.og_image} head-key="og:image" /> : null}
                <meta name="twitter:card" content="summary_large_image" />
            </Head>

            <header className={cn('sticky top-0 z-40 transition-all duration-200', scrolled ? 'border-b border-slate-200/70 bg-white/85 backdrop-blur-lg' : 'bg-transparent')}>
                <nav className="mx-auto flex h-16 max-w-7xl items-center justify-between gap-6 px-4 sm:px-6 lg:px-8" aria-label="Main">
                    <Brand site={site} hideName={marketing?.hide_site_name} />

                    <div className="hidden items-center gap-1 lg:flex">
                        {header.map((item) => (
                            <SmartLink
                                key={item.label}
                                href={item.url ?? '#'}
                                className={cn('rounded-lg px-3 py-2 text-sm font-medium transition-colors', isActive(item.url) ? 'text-indigo-600' : 'text-slate-600 hover:text-slate-900')}
                            >
                                {item.label}
                            </SmartLink>
                        ))}
                    </div>

                    <div className="hidden items-center gap-2 lg:flex">
                        {auth?.user ? (
                            <Button href={route('dashboard')} className="bg-indigo-600 text-white hover:bg-indigo-700">
                                Go to dashboard
                            </Button>
                        ) : (
                            <>
                                <Button href={route('login')} variant="ghost">
                                    Log in
                                </Button>
                                <Button href={route('register')} className="bg-indigo-600 text-white hover:bg-indigo-700">
                                    Start free
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
                                <SmartLink key={item.label} href={item.url ?? '#'} className="block rounded-lg px-3 py-2.5 text-base font-medium text-slate-800 hover:bg-slate-50">
                                    {item.label}
                                </SmartLink>
                            ))}
                        </div>
                        <div className="mt-8 grid gap-2 border-t border-slate-100 pt-6">
                            {auth?.user ? (
                                <Button href={route('dashboard')} size="lg" className="bg-indigo-600 text-white">
                                    Go to dashboard
                                </Button>
                            ) : (
                                <>
                                    <Button href={route('register')} size="lg" className="bg-indigo-600 text-white">
                                        Start free
                                    </Button>
                                    <Button href={route('login')} size="lg" variant="secondary">
                                        Log in
                                    </Button>
                                </>
                            )}
                        </div>
                    </DialogPanel>
                </Dialog>
            </header>

            <main>{children}</main>

            <footer className="border-t border-slate-200 bg-slate-50">
                <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                    <div className="grid gap-10 md:grid-cols-4">
                        <div className="md:col-span-2">
                            <Brand site={site} hideName={marketing?.hide_site_name} />
                            <p className="mt-4 max-w-sm text-sm leading-relaxed text-slate-500">{marketing?.footer_text}</p>
                        </div>
                        <FooterColumn
                            title="Product"
                            items={
                                menus.footer_product?.length
                                    ? menus.footer_product
                                    : [
                                          { label: 'Features', url: route('landing.features') },
                                          { label: 'Pricing', url: route('landing.pricing') },
                                          { label: 'FAQ', url: route('landing.faq') },
                                      ]
                            }
                        />
                        <FooterColumn
                            title="Company"
                            items={
                                menus.footer_company?.length
                                    ? menus.footer_company
                                    : [
                                          { label: 'About', url: route('landing.about') },
                                          { label: 'Blog', url: route('blog.index') },
                                          { label: 'Contact', url: route('landing.contact') },
                                      ]
                            }
                        />
                    </div>
                    <div className="mt-12 flex flex-col-reverse items-center justify-between gap-4 border-t border-slate-200 pt-8 text-sm text-slate-500 sm:flex-row">
                        <p>
                            © {new Date().getFullYear()} {site?.name}. All rights reserved.
                        </p>
                        <div className="flex flex-wrap gap-x-6 gap-y-2">
                            {(menus.footer_legal ?? []).map((item) => (
                                <SmartLink key={item.label} href={item.url ?? '#'} className="hover:text-slate-800">
                                    {item.label}
                                </SmartLink>
                            ))}
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
}

function Brand({ site, hideName }) {
    return (
        <Link href={route('landing')} className="flex items-center gap-2.5">
            {site?.logo ? (
                <img src={site.logo} alt={site?.name} className="h-8 w-auto" />
            ) : (
                <span className="flex size-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-sky-500 shadow-sm">
                    <FileText className="size-4 text-white" />
                </span>
            )}
            {!hideName ? <span className="text-lg font-semibold tracking-tight text-slate-900">{site?.name}</span> : null}
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
                        <SmartLink href={item.url} className="text-sm text-slate-500 hover:text-slate-900">
                            {item.label}
                        </SmartLink>
                    </li>
                ))}
            </ul>
        </div>
    );
}
