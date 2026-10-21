<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConnectionRequest;
use App\Models\WaterSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConnectionRequestController extends Controller
{
    public function update(Request $request, ConnectionRequest $connectionRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:under_review,quoted,rejected'],
            'quote_amount' => ['required_if:status,quoted', 'nullable', 'integer', 'min:1'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $connectionRequest->update($validated);

        return back()->with('status', 'request-updated');
    }

    public function activate(ConnectionRequest $connectionRequest, Request $request): RedirectResponse
    {
        abort_unless($connectionRequest->status === 'approved', 422);

        $settings = WaterSetting::current();

        DB::transaction(function () use ($connectionRequest, $request, $settings): void {
            $subscription = $connectionRequest->subscription()->firstOrCreate([], [
                'user_id' => $connectionRequest->user_id,
                'reference' => 'EV-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
                'service_address' => $connectionRequest->service_address,
                'meter_number' => 'CP-'.Str::upper(Str::random(5)),
                'status' => 'active',
                'monthly_fee' => $settings->monthly_fee,
                'price_per_m3' => $settings->price_per_m3,
            ]);

            if ($subscription->meter_number === null) {
                $subscription->update(['meter_number' => 'CP-'.Str::upper(Str::random(5))]);
                $subscription->refresh();
            }

            $meter = $subscription->waterMeter()->firstOrCreate([], [
                'serial_number' => $subscription->meter_number,
                'status' => 'active',
                'installed_at' => today(),
            ]);

            if ($meter->wasRecentlyCreated) {
                $meter->events()->create([
                    'subscription_id' => $subscription->id,
                    'performed_by_user_id' => $request->user()->exists ? $request->user()->id : null,
                    'event_type' => 'installation',
                    'description' => 'Compteur créé lors de l’activation de l’abonnement.',
                    'event_at' => today(),
                ]);
            }

            $connectionRequest->update(['status' => 'connected']);
        });

        return back()->with('status', 'subscription-activated');
    }
}
