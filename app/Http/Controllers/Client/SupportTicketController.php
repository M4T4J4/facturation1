<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        return view('client.support.index', [
            'tickets' => $request->user()->supportTickets()->latest()->paginate(10),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:3000'],
        ]);

        $request->user()->supportTickets()->create([
            ...$validated,
            'subscription_id' => $request->user()->subscriptions()->where('status', 'active')->value('id'),
        ]);

        return back()->with('status', 'ticket-submitted');
    }
}
