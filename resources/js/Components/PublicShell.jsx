import { Head, usePage } from '@inertiajs/react';
import { Download, Globe, Mail, Phone } from 'lucide-react';
import { useEffect } from 'react';
import { contrastText } from '@/lib/utils';

/** Branded frame for client-facing documents (public invoice / quotation links). */
export default function PublicShell({ title, company, pdfUrl, children }) {
    const { site } = usePage().props;
    const style = { '--brand': company.brand, '--brand-contrast': contrastText(company.brand) };

    useEffect(() => {
        Object.entries(style).forEach(([k, v]) => document.documentElement.style.setProperty(k, v));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [company.brand]);

    return (
        <div style={style} className="min-h-screen bg-slate-100 font-sans">
            <Head title={title}>
                <meta name="robots" content="noindex, nofollow" />
            </Head>
            <div className="h-1.5 bg-brand-600" />
            <header className="border-b border-slate-200 bg-white">
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
                    <div className="flex min-w-0 items-center gap-3">
                        {company.logo_url ? (
                            <img src={company.logo_url} alt={company.name} className="h-10 w-auto max-w-[160px] object-contain" />
                        ) : (
                            <span className="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-lg font-bold text-brand-contrast">{company.name.slice(0, 1)}</span>
                        )}
                        <div className="min-w-0">
                            <p className="truncate font-semibold text-slate-900">{company.name}</p>
                            {company.address ? <p className="hidden truncate text-xs text-slate-500 sm:block">{company.address}</p> : null}
                        </div>
                    </div>
                    {pdfUrl ? (
                        <a href={pdfUrl} className="inline-flex h-9 items-center gap-2 rounded-lg bg-white px-3.5 text-sm font-medium text-slate-700 shadow-xs ring-1 ring-slate-300 hover:bg-slate-50">
                            <Download className="size-4" /> <span className="hidden sm:inline">Download</span> PDF
                        </a>
                    ) : null}
                </div>
            </header>
            <main className="mx-auto max-w-6xl px-4 py-8 sm:px-6">{children}</main>
            <footer className="mx-auto max-w-6xl px-4 pb-10 sm:px-6">
                <div className="flex flex-wrap items-center justify-between gap-4 border-t border-slate-200 pt-6 text-sm text-slate-500">
                    <div className="flex flex-wrap gap-x-5 gap-y-2">
                        {company.email ? (
                            <a href={`mailto:${company.email}`} className="inline-flex items-center gap-1.5 hover:text-slate-800">
                                <Mail className="size-4" /> {company.email}
                            </a>
                        ) : null}
                        {company.phone ? (
                            <a href={`tel:${company.phone}`} className="inline-flex items-center gap-1.5 hover:text-slate-800">
                                <Phone className="size-4" /> {company.phone}
                            </a>
                        ) : null}
                        {company.website ? (
                            <a href={company.website} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-1.5 hover:text-slate-800">
                                <Globe className="size-4" /> {company.website.replace(/^https?:\/\//, '')}
                            </a>
                        ) : null}
                    </div>
                    <p className="text-xs">Powered by {site?.name ?? 'RecurringPress'}</p>
                </div>
            </footer>
        </div>
    );
}
