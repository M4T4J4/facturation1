<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function update(Request $request, SupportTicket $supportTicket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:in_progress,resolved'],
        ]);

        $supportTicket->update($validated);

        return back()->with('status', 'ticket-updated');
    }
}
