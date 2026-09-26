import { cn } from '@/lib/utils';

export function Card({ className, children, ...props }) {
    return (
        <div className={cn('rounded-xl bg-white shadow-card ring-1 ring-slate-900/5', className)} {...props}>
            {children}
        </div>
    );
}

export function CardHeader({ title, description, actions, icon: Icon, className, children }) {
    return (
        <div className={cn('flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4', className)}>
            <div className="flex min-w-0 items-start gap-3">
                {Icon ? (
                    <span className="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                        <Icon className="size-4" />
                    </span>
                ) : null}
                <div className="min-w-0">
                    {title ? <h3 className="text-sm font-semibold text-slate-900">{title}</h3> : null}
                    {description ? <p className="mt-0.5 text-sm text-slate-500">{description}</p> : null}
                    {children}
                </div>
            </div>
            {actions ? <div className="flex shrink-0 items-center gap-2">{actions}</div> : null}
        </div>
    );
}

export function CardBody({ className, children }) {
    return <div className={cn('px-5 py-5', className)}>{children}</div>;
}

export function CardFooter({ className, children }) {
    return <div className={cn('flex items-center justify-end gap-3 rounded-b-xl border-t border-slate-100 bg-slate-50/60 px-5 py-3', className)}>{children}</div>;
}

/** Label / value pair for detail views. */
export function DescriptionItem({ label, children, className }) {
    return (
        <div className={className}>
            <dt className="text-xs font-medium tracking-wide text-slate-500 uppercase">{label}</dt>
            <dd className="mt-1 text-sm text-slate-900">{children ?? <span className="text-slate-400">—</span>}</dd>
        </div>
    );
}
