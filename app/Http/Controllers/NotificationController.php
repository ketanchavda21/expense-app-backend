<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get unread notifications for the authenticated user.
     */
    public function index(Request $request)
    {
        $notifications = \App\Models\Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->latest()
            ->limit(50)
            ->get();

        $unreadCount = $notifications->count();

        return response()->json([
            'status' => true,
            'data' => [
                'notifications' => $notifications,
                'unread_count' => $unreadCount
            ]
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = \App\Models\Notification::where('user_id', $request->user()->id)->find($id);

        if (!$notification) {
            return response()->json([
                'status' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->update(['is_read' => true]);

        return response()->json([
            'status' => true,
            'message' => 'Notification marked as read',
            'data' => $notification
        ]);
    }

    /**
     * Mark all notifications as read for the authenticated user.
     */
    public function markAllAsRead(Request $request)
    {
        \App\Models\Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'status' => true,
            'message' => 'All notifications marked as read'
        ]);
    }
}
