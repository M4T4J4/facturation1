<?php

namespace App\Http\Controllers\FieldAgent;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeterReadingController extends Controller
{
    public function index(Request $request): View
    {
        return view('agent.readings.index', [
            'subscriptions' => $this->assignedSubscriptions($request->user()->id)
                ->with([
                    'user',
                    'waterMeter.readings' => fn ($query) => $query->latest('read_at'),
                ])
                ->where('status', 'active')
                ->whereHas('waterMeter', fn ($query) => $query->where('status', 'active'))
                ->latest()
                ->paginate(15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'exists:subscriptions,reference'],
            'current_index' => ['required', 'integer', 'min:0'],
            'read_at' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $subscription = $this->assignedSubscriptions($request->user()->id)
            ->with('waterMeter')
            ->where('reference', $validated['reference'])
            ->where('status', 'active')
            ->whereHas('waterMeter', fn ($query) => $query->where('status', 'active'))
            ->firstOrFail();
        $waterMeter = $subscription->waterMeter;
        $previousIndex = $waterMeter->readings()->latest('read_at')->value('current_index') ?? 0;

        if ($validated['current_index'] < $previousIndex) {
            return back()->withErrors(['current_index' => 'Le nouvel index ne peut pas être inférieur au précédent.']);
        }

        $subscription->meterReadings()->create([
            'water_meter_id' => $waterMeter->id,
            'recorded_by_user_id' => $request->user()->id,
            'previous_index' => $previousIndex,
            'current_index' => $validated['current_index'],
            'read_at' => $validated['read_at'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('status', 'reading-submitted');
    }

    private function assignedSubscriptions(int $agentId): Builder
    {
        return Subscription::query()
            ->whereHas('waterZone', fn (Builder $zoneQuery) => $zoneQuery
                ->where('is_active', true)
                ->whereHas('agents', fn (Builder $agentQuery) => $agentQuery->whereKey($agentId)));
    }
}
