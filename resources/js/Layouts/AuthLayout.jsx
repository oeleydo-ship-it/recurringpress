import { Link, usePage } from '@inertiajs/react';
import { CheckCircle2, FileText, ShieldCheck, Zap } from 'lucide-react';
import { useEffect } from 'react';
import { route } from '@/lib/route';

/**
 * Split-screen layout for login / register / password flows (tenant and superadmin).
 * Pages provide their own heading via <AuthHeading>.
 */
export default function AuthLayout({ children }) {
    const { site } = usePage().props;
    const { component } = usePage();
    const isAdmin = component.startsWith('Superadmin/');

    useEffect(() => {
        document.body.dataset.site = site?.name ?? '';
    }, [site?.name]);

    return (
        <div className="grid min-h-screen font-sans lg:grid-cols-[1fr_minmax(0,560px)] xl:grid-cols-[1fr_minmax(0,640px)]">
            {/* Brand panel */}
            <aside className="relative hidden overflow-hidden bg-slate-950 lg:flex lg:flex-col lg:justify-between lg:p-12">
                <div
                    className="pointer-events-none absolute inset-0 opacity-70"
                    style={{
                        background:
                            'radial-gradient(60% 50% at 20% 10%, rgb(99 102 241 / 0.45), transparent 70%), radial-gradient(50% 50% at 90% 90%, rgb(14 165 233 / 0.35), transparent 70%)',
                    }}
                />
                <div
                    className="pointer-events-none absolute inset-0 opacity-[0.07]"
                    style={{ backgroundImage: 'linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px)', backgroundSize: '44px 44px' }}
                />

                <Link href={isAdmin ? route('superadmin.login') : route('landing')} className="relative flex items-center gap-3">
                    {site?.logo ? (
                        <img src={site.logo} alt={site.name} className="h-9 w-auto brightness-0 invert" />
                    ) : (
                        <>
                            <span className="flex size-10 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/20 backdrop-blur">
                                <FileText className="size-5 text-white" />
                            </span>
                            <span className="text-lg font-semibold text-white">{site?.name}</span>
                        </>
                    )}
                </Link>

                <div className="relative max-w-md">
                    {isAdmin ? (
                        <>
                            <h2 className="text-3xl font-semibold tracking-tight text-white">Platform administration</h2>
                            <p className="mt-4 text-base text-slate-300">Manage tenants, plans, payouts, content and system health from one place.</p>
                        </>
                    ) : (
                        <>
                            <h2 className="text-3xl leading-tight font-semibold tracking-tight text-white">Get paid faster with invoices your clients love.</h2>
                            <ul className="mt-8 space-y-4">
                                {[
                                    [Zap, 'Create and send a professional invoice in under a minute'],
                                    [CheckCircle2, 'Online card payments, receipts and reminders built in'],
                                    [ShieldCheck, 'Bank-grade security with automatic backups'],
                                ].map(([Icon, text]) => (
                                    <li key={text} className="flex items-start gap-3 text-slate-300">
                                        <span className="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/15">
                                            <Icon className="size-3.5 text-sky-300" />
                                        </span>
                                        {text}
                                    </li>
                                ))}
                            </ul>
                        </>
                    )}
                </div>

                <p className="relative text-sm text-slate-500">
                    © {new Date().getFullYear()} {site?.name}
                </p>
            </aside>

            {/* Form panel */}
            <main className="flex flex-col justify-center bg-white px-6 py-12 sm:px-12 xl:px-20">
                <div className="mx-auto w-full max-w-sm">
                    <Link href={isAdmin ? route('superadmin.login') : route('landing')} className="mb-10 flex items-center gap-2.5 lg:hidden">
                        <span className="flex size-9 items-center justify-center rounded-xl bg-indigo-600">
                            <FileText className="size-4 text-white" />
                        </span>
                        <span className="text-base font-semibold text-slate-900">{site?.name}</span>
                    </Link>
                    <div className="animate-slide-up">{children}</div>
                </div>
            </main>
        </div>
    );
}

export function AuthHeading({ title, description }) {
    return (
        <div className="mb-8">
            <h1 className="text-2xl font-semibold tracking-tight text-slate-900">{title}</h1>
            {description ? <p className="mt-2 text-sm text-slate-500">{description}</p> : null}
        </div>
    );
}
