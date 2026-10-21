<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeterReadingController extends Controller
{
    public function index(Request $request): View
    {
        $subscriptions = $request->user()->subscriptions()
            ->where('status', 'active')
            ->whereHas('waterMeter', fn ($query) => $query->where('status', 'active'))
            ->with(['waterMeter', 'meterReadings.waterMeter', 'meterReadings.invoice'])
            ->get();

        return view('client.readings.index', ['subscriptions' => $subscriptions]);
    }

    public function store(Request $request): RedirectResponse
    {
        $subscription = $request->user()->subscriptions()
            ->with('waterMeter')
            ->where('status', 'active')
            ->whereHas('waterMeter', fn ($query) => $query->where('status', 'active'))
            ->first();

        if ($subscription === null) {
            return redirect()->route('client.requests.index')->withErrors(['reading' => 'Un abonnement actif avec un compteur en service est nécessaire pour envoyer un relevé.']);
        }

        $waterMeter = $subscription->waterMeter;
        $previousIndex = $waterMeter->readings()->latest('read_at')->value('current_index') ?? 0;
        $validated = $request->validate([
            'current_index' => ['required', 'integer', 'min:'.$previousIndex],
            'read_at' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $subscription->meterReadings()->create([
            ...$validated,
            'water_meter_id' => $waterMeter->id,
            'previous_index' => $previousIndex,
        ]);

        return back()->with('status', 'reading-submitted');
    }
}
