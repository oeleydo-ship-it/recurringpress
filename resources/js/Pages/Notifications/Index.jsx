import { router } from '@inertiajs/react';
import { Bell, CheckCheck } from 'lucide-react';
import NotificationItem from '@/Components/notifications/NotificationItem';
import { Button, Card, EmptyState, LinkTabs, PageHeader, Pagination } from '@/Components/ui';
import { route } from '@/lib/route';

export default function NotificationsIndex({ notifications, filter }) {
    const hasUnread = notifications.data.some((n) => !n.read) || filter === 'unread';
    const reload = { preserveScroll: true, only: ['notifications', 'auth'] };

    return (
        <>
            <PageHeader
                title="Notifications"
                description="Payments, quotation replies, invoice activity and account changes for your company."
                actions={
                    <Button variant="secondary" icon={CheckCheck} disabled={!hasUnread} onClick={() => router.post(route('notifications.read-all'), {}, reload)}>
                        Mark all as read
                    </Button>
                }
            />

            <LinkTabs
                className="mb-4"
                tabs={[
                    { label: 'All', href: route('notifications.index'), active: filter === 'all' },
                    { label: 'Unread', href: route('notifications.index', { filter: 'unread' }), active: filter === 'unread' },
                ]}
            />

            <Card>
                {notifications.data.length ? (
                    <>
                        <div className="divide-y divide-slate-100 p-1">
                            {notifications.data.map((item) => (
                                <NotificationItem key={item.id} item={item} onMarkRead={(n) => router.post(route('notifications.read', n.id), {}, reload)} />
                            ))}
                        </div>
                        <Pagination paginator={notifications} />
                    </>
                ) : (
                    <EmptyState
                        icon={Bell}
                        title={filter === 'unread' ? 'No unread notifications' : 'No notifications yet'}
                        description="When clients pay, view or respond to your documents, or someone changes your account, it shows up here."
                    />
                )}
            </Card>
        </>
    );
}
