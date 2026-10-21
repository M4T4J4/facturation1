<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FieldMission;
use App\Models\MaintenanceIncident;
use App\Models\SystemNotification;
use App\Models\User;
use App\Models\WaterNetworkAsset;
use App\Models\WaterZone;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NetworkAssetController extends Controller
{
    public function index(): View
    {
        return view('admin.network.index', [
            'assets' => WaterNetworkAsset::query()->with('incidents')->latest()->paginate(10),
            'incidents' => MaintenanceIncident::query()->with(['asset', 'assignee'])->latest('reported_at')->limit(8)->get(),
            'missions' => FieldMission::query()->with(['zone', 'assignee'])->latest('scheduled_for')->limit(8)->get(),
            'notifications' => SystemNotification::query()->with('user')->latest()->limit(8)->get(),
            'agents' => User::query()->where('role', UserRole::FieldAgent->value)->orderBy('name')->get(['id', 'name', 'email']),
            'zones' => WaterZone::query()->orderBy('district')->orderBy('name')->get(['id', 'name', 'code', 'district']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:120'],
            'location' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:80'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'last_inspection_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        WaterNetworkAsset::query()->create($validated);

        return back()->with('status', 'network-asset-created');
    }

    public function update(Request $request, WaterNetworkAsset $waterNetworkAsset): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:120'],
            'location' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:80'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'last_inspection_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $waterNetworkAsset->update($validated);

        return back()->with('status', 'network-asset-updated');
    }
}
