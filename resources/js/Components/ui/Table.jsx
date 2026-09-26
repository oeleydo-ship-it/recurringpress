import { router } from '@inertiajs/react';
import { cn } from '@/lib/utils';

export function Table({ className, children }) {
    return (
        <div className={cn('overflow-x-auto scrollbar-thin', className)}>
            <table className="min-w-full divide-y divide-slate-200 text-sm">{children}</table>
        </div>
    );
}

export function THead({ children }) {
    return (
        <thead className="bg-slate-50/80">
            <tr>{children}</tr>
        </thead>
    );
}

export function TH({ className, align = 'left', children }) {
    return (
        <th scope="col" className={cn('px-4 py-3 text-xs font-semibold tracking-wide whitespace-nowrap text-slate-500 uppercase first:pl-5 last:pr-5', align === 'right' && 'text-right', align === 'center' && 'text-center', className)}>
            {children}
        </th>
    );
}

export function TBody({ children }) {
    return <tbody className="divide-y divide-slate-100 bg-white">{children}</tbody>;
}

/** Row; pass `href` to make the whole row navigate on click (links/buttons inside still work). */
export function TR({ href, className, children }) {
    const onClick = href
        ? (e) => {
              if (e.target.closest('a,button,input,select,label,[role=menu],[data-no-row-click]')) return;
              if (e.metaKey || e.ctrlKey) window.open(href, '_blank');
              else router.visit(href);
          }
        : undefined;
    return (
        <tr onClick={onClick} className={cn('transition-colors', href && 'cursor-pointer hover:bg-slate-50/80', className)}>
            {children}
        </tr>
    );
}

export function TD({ className, align = 'left', children }) {
    return <td className={cn('px-4 py-3.5 whitespace-nowrap text-slate-700 first:pl-5 last:pr-5', align === 'right' && 'text-right', align === 'center' && 'text-center', className)}>{children}</td>;
}
