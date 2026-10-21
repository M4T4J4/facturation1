<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ConnectionRequest;
use App\Models\WaterSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConnectionRequestController extends Controller
{
    public function index(Request $request): View
    {
        return view('client.requests.index', [
            'connectionRequests' => $request->user()->connectionRequests()->latest()->paginate(10),
            'settings' => WaterSetting::current(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'service_address' => ['required', 'string', 'max:1000'],
            'usage_type' => ['required', 'in:residential,business'],
        ]);

        $request->user()->connectionRequests()->create($validated);

        return redirect()->route('client.dashboard')->with('status', 'request-submitted');
    }

    public function accept(ConnectionRequest $connectionRequest, Request $request): RedirectResponse
    {
        abort_unless($connectionRequest->user_id === $request->user()->id, 403);
        abort_unless($connectionRequest->status === 'quoted', 422);
        $connectionRequest->update(['status' => 'approved']);

        return back()->with('status', 'quote-accepted');
    }
}
