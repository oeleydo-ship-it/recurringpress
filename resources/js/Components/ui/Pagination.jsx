import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';

/**
 * Renders a Laravel LengthAwarePaginator (serialized) — expects `links`, `from`, `to`, `total`.
 */
export default function Pagination({ paginator, className }) {
    if (!paginator || !paginator.links || paginator.last_page <= 1) {
        return paginator?.total ? (
            <div className={cn('border-t border-slate-100 px-5 py-3 text-sm text-slate-500', className)}>
                {paginator.total} {paginator.total === 1 ? 'result' : 'results'}
            </div>
        ) : null;
    }

    const links = paginator.links;
    const prev = links[0];
    const next = links[links.length - 1];
    const pages = links.slice(1, -1);

    return (
        <div className={cn('flex items-center justify-between gap-4 border-t border-slate-100 px-5 py-3', className)}>
            <p className="hidden text-sm text-slate-500 sm:block">
                Showing <span className="font-medium text-slate-700">{paginator.from}</span>–<span className="font-medium text-slate-700">{paginator.to}</span> of{' '}
                <span className="font-medium text-slate-700">{paginator.total}</span>
            </p>
            <nav className="flex items-center gap-1" aria-label="Pagination">
                <PageLink link={prev} aria-label="Previous page">
                    <ChevronLeft className="size-4" />
                </PageLink>
                {pages.map((link, i) =>
                    link.url === null ? (
                        <span key={i} className="px-2 text-sm text-slate-400">
                            …
                        </span>
                    ) : (
                        <PageLink key={i} link={link} className="hidden sm:inline-flex">
                            {link.label}
                        </PageLink>
                    ),
                )}
                <span className="px-2 text-sm text-slate-600 sm:hidden">
                    {paginator.current_page} / {paginator.last_page}
                </span>
                <PageLink link={next} aria-label="Next page">
                    <ChevronRight className="size-4" />
                </PageLink>
            </nav>
        </div>
    );
}

function PageLink({ link, className, children, ...props }) {
    const cls = cn(
        'inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-sm font-medium transition-colors',
        link.active ? 'bg-brand-600 text-brand-contrast' : 'text-slate-600 hover:bg-slate-100',
        className,
    );
    if (!link.url) {
        return (
            <span className={cn(cls, 'cursor-not-allowed opacity-40')} {...props}>
                {children}
            </span>
        );
    }
    return (
        <Link href={link.url} preserveScroll preserveState className={cls} {...props}>
            {children}
        </Link>
    );
}
