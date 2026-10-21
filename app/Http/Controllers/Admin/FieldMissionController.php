<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FieldMission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FieldMissionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'water_zone_id' => ['nullable', 'integer', 'exists:water_zones,id'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['required', 'string', 'max:40'],
            'scheduled_for' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        FieldMission::query()->create($validated);

        return back()->with('status', 'field-mission-created');
    }

    public function update(Request $request, FieldMission $fieldMission): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'max:40'],
            'completed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $fieldMission->update($validated);

        return back()->with('status', 'field-mission-updated');
    }
}
