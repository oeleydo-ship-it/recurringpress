import { Link } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { forwardRef } from 'react';
import { cn } from '@/lib/utils';

const variants = {
    primary:
        'bg-brand-600 text-brand-contrast shadow-xs hover:bg-brand-700 focus-visible:outline-brand-600 disabled:bg-brand-400',
    secondary:
        'bg-white text-slate-700 shadow-xs ring-1 ring-slate-300 ring-inset hover:bg-slate-50 hover:text-slate-900 focus-visible:outline-brand-600',
    soft: 'bg-brand-50 text-brand-700 hover:bg-brand-100 focus-visible:outline-brand-600',
    ghost: 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-brand-600',
    danger: 'bg-red-600 text-white shadow-xs hover:bg-red-700 focus-visible:outline-red-600 disabled:bg-red-400',
    'danger-soft': 'bg-red-50 text-red-700 hover:bg-red-100 focus-visible:outline-red-600',
    success: 'bg-emerald-600 text-white shadow-xs hover:bg-emerald-700 focus-visible:outline-emerald-600',
    link: 'text-brand-600 hover:text-brand-700 underline-offset-4 hover:underline px-0! py-0! h-auto!',
};

const sizes = {
    xs: 'h-7 gap-1 rounded-md px-2 text-xs',
    sm: 'h-8 gap-1.5 rounded-lg px-3 text-sm',
    md: 'h-9 gap-2 rounded-lg px-3.5 text-sm',
    lg: 'h-11 gap-2 rounded-xl px-5 text-base',
    icon: 'h-9 w-9 justify-center rounded-lg',
    'icon-sm': 'h-8 w-8 justify-center rounded-lg',
};

/**
 * Button that renders an Inertia <Link> when given `href` (use `external` for a plain <a>),
 * otherwise a <button>. `icon` / `iconRight` take lucide components.
 */
const Button = forwardRef(function Button(
    { variant = 'primary', size = 'md', href, external, method, as, loading = false, disabled, icon: Icon, iconRight: IconRight, className, children, type = 'button', ...props },
    ref,
) {
    const classes = cn(
        'inline-flex shrink-0 items-center justify-center font-medium whitespace-nowrap transition-colors select-none',
        'focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-60',
        variants[variant],
        sizes[size],
        className,
    );

    const content = (
        <>
            {loading ? <Loader2 className="size-4 animate-spin" /> : Icon ? <Icon className={cn('size-4', size === 'lg' && 'size-5')} /> : null}
            {children}
            {IconRight && !loading ? <IconRight className="size-4" /> : null}
        </>
    );

    if (href && external) {
        return (
            <a ref={ref} href={href} className={classes} {...props}>
                {content}
            </a>
        );
    }

    if (href) {
        return (
            <Link ref={ref} href={href} method={method} as={as ?? (method && method !== 'get' ? 'button' : undefined)} className={classes} {...props}>
                {content}
            </Link>
        );
    }

    return (
        <button ref={ref} type={type} disabled={disabled || loading} className={classes} {...props}>
            {content}
        </button>
    );
});

export default Button;
