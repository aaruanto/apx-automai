<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * In-app notifications, shared by admins, staff and customers.
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
                'link'    => $n->data['link'] ?? null,
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
            'link'    => $notification->data['link'] ?? null,
            'unread'  => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true, 'unread' => 0]);
    }
}
