<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SystemNotificationController extends Controller
{
    public function markAsRead(SystemNotification $systemNotification): RedirectResponse
    {
        $systemNotification->update(['is_read' => true]);

        return back();
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
            'type' => ['required', 'string', 'max:40'],
        ]);

        SystemNotification::query()->create($validated);

        return back()->with('status', 'notification-sent');
    }
}
