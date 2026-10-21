<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceIncident;
use App\Models\SystemNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MaintenanceIncidentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'water_network_asset_id' => ['required', 'integer', 'exists:water_network_assets,id'],
            'title' => ['required', 'string', 'max:255'],
            'kind' => ['required', 'string', 'max:80'],
            'severity' => ['required', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:2000'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['required', 'string', 'max:40'],
            'reported_at' => ['nullable', 'date'],
        ]);

        $incident = MaintenanceIncident::query()->create($validated);

        SystemNotification::query()->create([
            'user_id' => $request->user()->id,
            'title' => 'Incident réseau signalé',
            'message' => $validated['title'],
            'type' => 'warning',
            'related_model' => MaintenanceIncident::class,
            'related_id' => $incident->id,
        ]);

        return back()->with('status', 'incident-created');
    }

    public function update(Request $request, MaintenanceIncident $maintenanceIncident): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'max:40'],
            'resolved_at' => ['nullable', 'date'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $maintenanceIncident->update($validated);

        return back()->with('status', 'incident-updated');
    }
}
