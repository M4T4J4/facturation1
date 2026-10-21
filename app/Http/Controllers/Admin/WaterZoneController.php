<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WaterSetting;
use App\Models\WaterZone;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WaterZoneController extends Controller
{
    public function index(): View
    {
        $zones = WaterZone::query()
            ->with(['agents' => fn ($query) => $query->orderBy('name')])
            ->withCount([
                'subscriptions',
                'subscriptions as active_subscriptions_count' => fn ($query) => $query->where('status', 'active'),
            ])
            ->orderBy('district')
            ->orderBy('name')
            ->paginate(15);
        $monthlyStats = Invoice::query()
            ->whereBetween('invoices.period', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->whereNotNull('invoices.water_zone_id')
            ->selectRaw('invoices.water_zone_id, SUM(invoices.consumption_m3) as consumption_m3, SUM(invoices.amount) as billed_amount')
            ->groupBy('invoices.water_zone_id')
            ->get()
            ->keyBy('water_zone_id');

        return view('admin.zones.index', [
            'zones' => $zones,
            'settings' => WaterSetting::current(),
            'agents' => User::query()->where('role', UserRole::FieldAgent->value)->orderBy('name')->get(['id', 'name', 'email']),
            'subscriptions' => Subscription::query()->with('user')->where('status', '!=', 'terminated')->orderBy('reference')->get(),
            'monthlyStats' => $monthlyStats,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:water_zones,code'],
            'district' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        WaterZone::query()->create($validated);

        return back()->with('status', 'water-zone-created');
    }

    public function update(Request $request, WaterZone $waterZone): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('water_zones', 'code')->ignore($waterZone)],
            'district' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ]);

        $waterZone->update($validated);

        return back()->with('status', 'water-zone-updated');
    }

    public function assignSubscription(Request $request, WaterZone $waterZone): RedirectResponse
    {
        abort_unless($waterZone->is_active, 422, 'Une zone inactive ne peut pas recevoir de nouvel abonnement.');

        $validated = $request->validate([
            'subscription_id' => ['required', 'integer', 'exists:subscriptions,id'],
        ]);

        DB::transaction(function () use ($request, $waterZone, $validated): void {
            $subscription = Subscription::query()
                ->lockForUpdate()
                ->findOrFail($validated['subscription_id']);

            if ($subscription->status === 'terminated') {
                throw ValidationException::withMessages([
                    'subscription_id' => 'Un abonnement résilié ne peut pas être affecté à une zone.',
                ]);
            }

            if ($subscription->water_zone_id === $waterZone->id) {
                return;
            }

            $previousZone = $subscription->waterZone()->value('name');
            $subscription->update(['water_zone_id' => $waterZone->id]);
            $subscription->events()->create([
                'performed_by_user_id' => $request->user()->exists ? $request->user()->id : null,
                'event_type' => 'zone_changed',
                'description' => 'Zone de distribution modifiée : '.($previousZone ?? 'aucune zone').' → '.$waterZone->name.'.',
                'event_at' => now(),
            ]);
        });

        return back()->with('status', 'subscription-zone-assigned');
    }

    public function assignAgents(Request $request, WaterZone $waterZone): RedirectResponse
    {
        $validated = $request->validate([
            'agent_ids' => ['nullable', 'array'],
            'agent_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);
        $agentIds = $validated['agent_ids'] ?? [];
        $validAgentCount = User::query()
            ->where('role', UserRole::FieldAgent->value)
            ->whereKey($agentIds)
            ->count();

        if ($validAgentCount !== count($agentIds)) {
            throw ValidationException::withMessages([
                'agent_ids' => 'Seuls les comptes agents de terrain peuvent être affectés à une zone.',
            ]);
        }

        DB::transaction(fn () => $waterZone->agents()->sync($agentIds));

        return back()->with('status', 'water-zone-agents-updated');
    }
}
