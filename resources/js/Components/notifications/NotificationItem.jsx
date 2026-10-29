import { Link } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { route } from '@/lib/route';
import { cn, relativeTime } from '@/lib/utils';
import { notificationStyle } from './types';

/** One notification row; opening it marks it read and goes to the related record. */
export default function NotificationItem({ item, onMarkRead, onOpen, compact = false }) {
    const { icon: Icon, tone } = notificationStyle(item.type);
    return (
        <div className={cn('group relative flex gap-3 rounded-lg transition-colors hover:bg-slate-50', compact ? 'px-3 py-2.5' : 'px-4 py-3.5')}>
            <span className={cn('flex size-8 shrink-0 items-center justify-center rounded-full', tone)}>
                <Icon className="size-4" />
            </span>
            <Link href={route('notifications.open', item.id)} onClick={onOpen} className="min-w-0 flex-1 focus:outline-none">
                <span className="absolute inset-0" aria-hidden="true" />
                <span className={cn('block text-sm', item.read ? 'text-slate-600' : 'font-medium text-slate-900')}>{item.title}</span>
                {item.body ? <span className={cn('mt-0.5 block text-xs text-slate-500', compact && 'line-clamp-2')}>{item.body}</span> : null}
                <span className="mt-1 block text-[11px] text-slate-400">{relativeTime(item.created_at)}</span>
            </Link>
            {!item.read ? (
                <button
                    type="button"
                    onClick={() => onMarkRead(item)}
                    className="relative z-10 flex size-6 shrink-0 items-center justify-center self-start rounded-full text-transparent group-hover:text-slate-400 hover:bg-slate-200 hover:!text-slate-700 focus-visible:text-slate-600"
                    title="Mark as read"
                    aria-label={`Mark "${item.title}" as read`}
                >
                    <span className="absolute size-2 rounded-full bg-brand-500 group-hover:opacity-0" aria-hidden="true" />
                    <Check className="size-3.5" />
                </button>
            ) : null}
        </div>
    );
}
