<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

/**
 * What is waiting for the account signed in. Open to every role: a notice is
 * addressed to an account, so there is nothing here that is not already theirs.
 */
class NotificationController extends Controller
{
    static string $page_title = 'MOE | Notifications';

    private string $uri;

    public function __construct(Request $request)
    {
        $this->uri = $request->getRequestUri();
    }

    public function index()
    {
        return view('notifications/index', [
            'page_title' => self::$page_title,
            'active_page' => 'notifications',
            'uri_segments' => explode('/', $this->uri),
            'app_version' => config('app.app_version'),
            'notifications' => Notification::for(auth()->user())
                ->newestFirst()
                ->paginate(20),
        ]);
    }

    /** Follow a notice to whatever it is about, and mark it dealt with. */
    public function open(string $ulid)
    {
        $notification = Notification::for(auth()->user())
            ->where('ulid', $ulid)
            ->firstOrFail();

        $notification->markRead();

        return redirect()->to($notification->url ?: route('notifications'));
    }

    public function markAllRead()
    {
        Notification::for(auth()->user())->unread()->update(['read_at' => now()]);

        return redirect()->route('notifications')->with('status', 'All notices marked as read.');
    }
}
