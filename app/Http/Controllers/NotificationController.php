<?php

namespace App\Http\Controllers;

use App\Models\StudentNotification;
use App\Services\StudentNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isStudent(), 403);
        StudentNotificationService::syncDeadlineReminders($user);

        $notifications = StudentNotification::where('user_id', $user->id)
            ->latest()
            ->paginate(30);

        return view('student.notifications', compact('notifications'));
    }

    public function read(Request $request, StudentNotification $notification): RedirectResponse
    {
        abort_unless($request->user()->isStudent() && $notification->user_id === $request->user()->id, 403);
        if (!$notification->read_at) $notification->update(['read_at' => now()]);
        return $notification->url ? redirect($notification->url) : back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isStudent(), 403);
        StudentNotification::where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);
        return back()->with('success', 'Все уведомления отмечены как прочитанные.');
    }
}
