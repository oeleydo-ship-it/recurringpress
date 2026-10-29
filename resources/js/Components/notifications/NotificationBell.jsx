import { Popover, PopoverButton, PopoverPanel } from '@headlessui/react';
import { Link, usePage } from '@inertiajs/react';
import { Bell, BellOff } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { postJson, requestJson } from '@/lib/http';
import { route } from '@/lib/route';
import NotificationItem from './NotificationItem';

const POLL_MS = 60_000;

/**
 * Top-bar bell. The unread count comes from the shared Inertia prop (fresh on every visit)
 * and is refreshed by light polling while the tab is visible; the list loads on open.
 */
export default function NotificationBell() {
    const shared = usePage().props.auth?.notifications?.unread ?? 0;
    const [unread, setUnread] = useState(shared);
    const [items, setItems] = useState(null);

    useEffect(() => setUnread(shared), [shared]);

    const refresh = useCallback(async () => {
        try {
            const data = await requestJson('GET', route('notifications.recent'));
            setUnread(data.unread);
            setItems(data.items);
        } catch {
            // Offline or signed out: keep the last known state.
        }
    }, []);

    useEffect(() => {
        const tick = () => document.visibilityState === 'visible' && refresh();
        const id = setInterval(tick, POLL_MS);
        document.addEventListener('visibilitychange', tick);
        return () => {
            clearInterval(id);
            document.removeEventListener('visibilitychange', tick);
        };
    }, [refresh]);

    const markRead = async (item) => {
        setItems((list) => list?.map((n) => (n.id === item.id ? { ...n, read: true } : n)));
        setUnread((n) => Math.max(0, n - 1));
        await postJson(route('notifications.read', item.id)).catch(refresh);
    };

    const markAll = async () => {
        setItems((list) => list?.map((n) => ({ ...n, read: true })));
        setUnread(0);
        await postJson(route('notifications.read-all')).catch(refresh);
    };

    return (
        <Popover className="relative">
            <PopoverButton
                onClick={refresh}
                className="relative flex size-9 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 hover:text-slate-800 focus-visible:outline-2 focus-visible:outline-brand-600 data-open:bg-slate-100 data-open:text-slate-800"
                aria-label={unread ? `Notifications, ${unread} unread` : 'Notifications'}
            >
                <Bell className="size-5" />
                {unread > 0 ? (
                    <span className="absolute -top-0.5 -right-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] leading-none font-semibold text-white ring-2 ring-white">
                        {unread > 99 ? '99+' : unread}
                    </span>
                ) : null}
            </PopoverButton>

            <PopoverPanel
                transition
                anchor="bottom end"
                className="z-50 w-[min(24rem,calc(100vw-2rem))] origin-top-right rounded-xl bg-white shadow-pop ring-1 ring-slate-900/5 transition duration-100 ease-out [--anchor-gap:8px] focus:outline-none data-closed:scale-95 data-closed:opacity-0"
            >
                {({ close }) => (
                    <>
                        <div className="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                            <span className="text-sm font-semibold text-slate-900">Notifications</span>
                            <button
                                type="button"
                                onClick={markAll}
                                disabled={!unread}
                                className="text-xs font-medium text-brand-600 hover:text-brand-700 disabled:cursor-default disabled:text-slate-300"
                            >
                                Mark all as read
                            </button>
                        </div>
                        <div className="max-h-[min(28rem,70vh)] overflow-y-auto p-1 scrollbar-thin">
                            {items === null ? (
                                <div className="space-y-2 p-3" aria-busy="true">
                                    {[0, 1, 2].map((i) => (
                                        <div key={i} className="h-12 animate-pulse rounded-lg bg-slate-100" />
                                    ))}
                                </div>
                            ) : items.length ? (
                                items.map((item) => <NotificationItem key={item.id} item={item} compact onMarkRead={markRead} onOpen={() => close()} />)
                            ) : (
                                <div className="flex flex-col items-center px-6 py-10 text-center">
                                    <BellOff className="mb-2 size-6 text-slate-300" />
                                    <p className="text-sm font-medium text-slate-700">You&apos;re all caught up</p>
                                    <p className="mt-0.5 text-xs text-slate-500">Payments, quotation replies and account changes show up here.</p>
                                </div>
                            )}
                        </div>
                        <Link
                            href={route('notifications.index')}
                            onClick={() => close()}
                            className="block rounded-b-xl border-t border-slate-100 px-4 py-2.5 text-center text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900"
                        >
                            View all notifications
                        </Link>
                    </>
                )}
            </PopoverPanel>
        </Popover>
    );
}
