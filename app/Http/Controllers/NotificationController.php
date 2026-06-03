<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    // محمي بـ auth على مستوى مجموعة المسارات — أي مستخدم يشوف إشعاراته فقط.

    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        $unreadCount = $request->user()->unreadNotifications()->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function markRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        // توجيه المستخدم لرابط الإشعار إن وُجد
        $url = $notification->data['items'][0]['url'] ?? null;
        if ($url) {
            return redirect()->to($url);
        }

        return back()->with('success', 'تم تعليم الإشعار كمقروء.');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'تم تعليم كل الإشعارات كمقروءة.');
    }
}
