<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
{
    // Fetch all notifications for the user, paginated for performance
    $notifications = auth()->user()->notifications()->paginate(15);
    return view('notifications.index', compact('notifications'));
}

    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        // Redirect to the specific appointment if the URL exists
        return redirect($notification->data['url'] ?? route('dashboard'));
    }

    public function clearAll()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return back()->with('success', 'All notifications marked as read.');
    }
}