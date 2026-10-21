<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WaterSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WaterSettingController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:120'],
            'currency' => ['required', 'string', 'max:12'],
            'price_per_m3' => ['required', 'integer', 'min:0'],
            'monthly_fee' => ['required', 'integer', 'min:0'],
            'connection_fee' => ['required', 'integer', 'min:0'],
            'meter_deposit' => ['required', 'integer', 'min:0'],
        ]);

        WaterSetting::current()->update($validated);

        return back()->with('status', 'settings-updated');
    }
}
