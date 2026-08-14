<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Auth::user()->notifications()->orderByDesc('created_at')->paginate(20);
        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead(Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }
        $notification->update(['is_read' => true]);
        return response()->json(['status' => 'ok']);
    }

    public function markAllAsRead()
    {
        Auth::user()->notifications()->where('is_read', false)->update(['is_read' => true]);
        return response()->json(['status' => 'ok']);
    }

    public function getUnreadCount()
    {
        if (Auth::check()) {
            $count = Auth::user()->unreadNotifications()->count();
        } else {
            $count = 0;
        }
        return response()->json(['count' => $count]);
    }

    public function recent()
    {
        if (Auth::check()) {
            $rawNotifications = Auth::user()->notifications()
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();

            $unreadCount = Auth::user()->unreadNotifications()->count();
        } else {
            // For guest visitors, retrieve the latest broadcast announcements
            $rawNotifications = Notification::select('message', 'type', 'url', DB::raw('MAX(id) as id'), DB::raw('MAX(created_at) as created_at'))
                ->groupBy('message', 'type', 'url')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();

            $unreadCount = 0;
        }

        $notifications = $rawNotifications->map(function ($item) {
            $rawMessage = $item->message ?? '';
            $title = 'Pemberitahuan Faiilmov';
            $body = $rawMessage;

            // Parse 【Title】\nBody format if present
            if (preg_match('/^【(.*?)】\s*\n?(.*)$/s', $rawMessage, $matches)) {
                $title = trim($matches[1]);
                $body = trim($matches[2]);
            }

            return [
                'id'         => (int)$item->id,
                'type'       => $item->type ?: 'announcement',
                'title'      => $title,
                'body'       => $body,
                'message'    => $rawMessage,
                'url'        => $item->url ?: null,
                'is_read'    => (bool)($item->is_read ?? false),
                'time_ago'   => $item->created_at ? $item->created_at->diffForHumans() : 'Baru saja',
                'timestamp'  => $item->created_at ? $item->created_at->getTimestamp() : time(),
                'created_at' => $item->created_at ? $item->created_at->toIso8601String() : now()->toIso8601String(),
            ];
        });

        return response()->json([
            'unread_count'  => $unreadCount,
            'notifications' => $notifications,
            'is_auth'       => Auth::check(),
        ]);
    }
}
