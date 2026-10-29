<?php

namespace App\Http\Controllers;

use App\Models\TenantNotification;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * The tenant top-bar bell: recent list (JSON, polled), the full list page, and read state.
 * Every query goes through TenantNotification::forUser(), so a user can only ever touch
 * their own rows for their current company.
 */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->input('filter') === 'unread' ? 'unread' : 'all';

        $notifications = TenantNotification::forUser($request->user())
            ->when($filter === 'unread', fn ($q) => $q->unread())
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (TenantNotification $n) => $n->toPayload());

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'filter' => $filter,
        ]);
    }

    /** Latest notifications + unread count for the bell dropdown. */
    public function recent(Request $request)
    {
        $base = TenantNotification::forUser($request->user());

        return response()->json([
            'unread' => (clone $base)->unread()->count(),
            'items' => (clone $base)->latest('id')->limit(10)->get()->map->toPayload()->values(),
        ]);
    }

    /** Mark read and go to the related record. */
    public function open(Request $request, int $notification)
    {
        $row = TenantNotification::forUser($request->user())->findOrFail($notification);

        if (! $row->read_at) {
            $row->forceFill(['read_at' => now()])->save();
        }

        return redirect($row->url && str_starts_with($row->url, '/') ? $row->url : route('notifications.index'));
    }

    public function markRead(Request $request, int $notification)
    {
        TenantNotification::forUser($request->user())->whereKey($notification)->unread()->update(['read_at' => now()]);

        return $request->wantsJson() ? response()->json(['ok' => true]) : back();
    }

    public function markAllRead(Request $request)
    {
        TenantNotification::forUser($request->user())->unread()->update(['read_at' => now()]);

        return $request->wantsJson() ? response()->json(['ok' => true]) : back()->with('success', 'All notifications marked as read.');
    }
}
