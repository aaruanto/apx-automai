<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * In-app notifications, shared by the admin and customers.
 *
 * Every action reads from $request->user()->notifications, so a notification
 * can only ever be read or marked by the person it belongs to — there is no
 * id lookup that could reach somebody else's.
 */
class NotificationController extends Controller
{
    /** Full history, paginated. */
    public function index(Request $request)
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->paginate(20),
            'unreadCount'   => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /** The dropdown's contents, and the badge count. */
    public function feed(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'items'  => $user->notifications()->take(8)->get()->map(fn ($n) => [
                'id'      => $n->id,
                'title'   => $n->data['title'] ?? 'Notification',
                'body'    => $n->data['body'] ?? '',
                'icon'    => $n->data['icon'] ?? 'fa-bell',
                // Carries the booking's reference, so the list it opens can
                // mark the row this notification is about.
                'link'    => \App\Notifications\BookingNotification::linkFor($n->data),
                'unread'  => $n->read_at === null,
                'ago'     => $n->created_at->diffForHumans(null, true).' ago',
            ])->values(),
        ]);
    }

    /** Mark one read, then hand back where it points so the caller can follow. */
    public function read(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            // Same reference-carrying link the feed hands out, so following a
            // notification from the bell lands on the same marked row as
            // following it from the list.
            'link'    => \App\Notifications\BookingNotification::linkFor($notification->data),
            'unread'  => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true, 'unread' => 0]);
    }
}
