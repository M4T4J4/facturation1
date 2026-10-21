<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\WaterMeter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MeterController extends Controller
{
    private const STATUSES = ['active', 'defective', 'blocked', 'in_repair', 'retired'];

    public function index(): View
    {
        $meters = WaterMeter::query()
            ->with([
                'subscription.user',
                'readings' => fn ($query) => $query->latest('read_at')->latest('id')->limit(3),
                'events' => fn ($query) => $query->with('performer')->latest('event_at')->latest('id'),
            ])
            ->latest()
            ->paginate(20);

        $subscriptions = Subscription::query()->with('user')->latest()->get();
        $monitoredMeters = WaterMeter::query()
            ->with([
                'subscription.user',
                'readings' => fn ($query) => $query->latest('read_at')->latest('id')->limit(3),
            ])
            ->get();
        $alerts = [];

        foreach ($monitoredMeters as $meter) {
            if (in_array($meter->status, ['defective', 'blocked', 'in_repair'], true)) {
                $alerts[] = [
                    'meter' => $meter,
                    'message' => match ($meter->status) {
                        'defective' => 'Compteur déclaré défectueux.',
                        'blocked' => 'Compteur déclaré bloqué.',
                        'in_repair' => 'Compteur en cours de réparation.',
                    },
                ];
            }

            if ($meter->subscription === null) {
                continue;
            }

            $readings = $meter->readings;

            if ($readings->count() >= 3 && $readings->take(3)->pluck('current_index')->unique()->count() === 1) {
                $alerts[] = [
                    'meter' => $meter,
                    'message' => 'Index identique sur trois relevés consécutifs : vérification du compteur conseillée.',
                ];
            }

            if ($readings->count() >= 2) {
                $latestConsumption = $readings[0]->current_index - $readings[0]->previous_index;
                $previousConsumption = $readings[1]->current_index - $readings[1]->previous_index;

                if ($latestConsumption >= 20 && $previousConsumption > 0 && $latestConsumption >= $previousConsumption * 2) {
                    $alerts[] = [
                        'meter' => $meter,
                        'message' => "Consommation récente ({$latestConsumption} m³) au moins deux fois supérieure au relevé précédent ({$previousConsumption} m³).",
                    ];
                }
            }
        }

        return view('admin.meters.index', [
            'meters' => $meters,
            'subscriptions' => $subscriptions,
            'alerts' => $alerts,
            'statuses' => self::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'serial_number' => ['required', 'string', 'max:255', 'unique:water_meters,serial_number'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'installed_at' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'subscription_id' => ['nullable', 'integer', 'exists:subscriptions,id'],
        ]);

        DB::transaction(function () use ($request, $validated): void {
            $subscriptionId = $validated['subscription_id'] ?? null;
            unset($validated['subscription_id']);
            $meter = WaterMeter::query()->create($validated);

            if ($subscriptionId !== null) {
                $this->assignMeter(
                    $meter,
                    Subscription::query()->lockForUpdate()->findOrFail($subscriptionId),
                    $this->actorId($request),
                    null,
                );

                return;
            }

            $this->recordEvent($meter, null, $this->actorId($request), 'registration', 'Compteur ajouté à l’inventaire.');
        });

        return back()->with('status', 'meter-created');
    }

    public function update(Request $request, WaterMeter $waterMeter): RedirectResponse
    {
        $validated = $request->validate([
            'serial_number' => ['required', 'string', 'max:255', Rule::unique('water_meters', 'serial_number')->ignore($waterMeter)],
            'status' => ['required', Rule::in(self::STATUSES)],
            'installed_at' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $validated, $waterMeter): void {
            $meter = WaterMeter::query()->lockForUpdate()->findOrFail($waterMeter->id);
            $previousStatus = $meter->status;
            $previousSerialNumber = $meter->serial_number;

            if ($validated['status'] === 'retired' && $meter->subscription !== null) {
                $this->assignMeter($meter, null, $this->actorId($request), 'Retrait du compteur de l’abonnement.');
                $meter->refresh();
            }

            $meter->update($validated);

            if ($meter->subscription !== null && $previousSerialNumber !== $meter->serial_number) {
                $meter->subscription->update(['meter_number' => $meter->serial_number]);
            }

            if ($previousSerialNumber !== $meter->serial_number) {
                $this->recordEvent(
                    $meter,
                    $meter->subscription_id,
                    $this->actorId($request),
                    'serial_update',
                    "Numéro de série modifié de {$previousSerialNumber} à {$meter->serial_number}.",
                );
            }

            if ($previousStatus !== $meter->status) {
                $this->recordEvent(
                    $meter,
                    $meter->subscription_id,
                    $this->actorId($request),
                    'status_change',
                    'État modifié : '.self::statusLabel($previousStatus).' → '.self::statusLabel($meter->status).'.',
                );
            }
        });

        return back()->with('status', 'meter-updated');
    }

    public function assign(Request $request, WaterMeter $waterMeter): RedirectResponse
    {
        $validated = $request->validate([
            'subscription_id' => ['nullable', 'integer', 'exists:subscriptions,id'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $validated, $waterMeter): void {
            $meter = WaterMeter::query()->lockForUpdate()->findOrFail($waterMeter->id);
            $subscription = isset($validated['subscription_id'])
                ? Subscription::query()->lockForUpdate()->findOrFail($validated['subscription_id'])
                : null;

            $this->assignMeter($meter, $subscription, $this->actorId($request), $validated['description'] ?? null);
        });

        return back()->with('status', 'meter-assigned');
    }

    public function storeEvent(Request $request, WaterMeter $waterMeter): RedirectResponse
    {
        $validated = $request->validate([
            'event_type' => ['required', Rule::in(['inspection', 'maintenance', 'repair'])],
            'description' => ['required', 'string', 'max:2000'],
            'event_at' => ['required', 'date', 'before_or_equal:today'],
        ]);

        DB::transaction(function () use ($request, $validated, $waterMeter): void {
            $meter = WaterMeter::query()->lockForUpdate()->findOrFail($waterMeter->id);

            if ($validated['event_type'] === 'repair') {
                if ($meter->status === 'retired') {
                    throw ValidationException::withMessages([
                        'event_type' => 'Un compteur retiré ne peut pas être réparé.',
                    ]);
                }

                $meter->update(['status' => 'active']);
            }

            $this->recordEvent(
                $meter,
                $meter->subscription_id,
                $this->actorId($request),
                $validated['event_type'],
                $validated['description'],
                $validated['event_at'],
            );
        });

        return back()->with('status', 'meter-event-recorded');
    }

    private function assignMeter(
        WaterMeter $meter,
        ?Subscription $subscription,
        ?int $userId,
        ?string $description,
    ): void {
        if ($subscription === null) {
            if ($meter->subscription_id === null) {
                return;
            }

            $previousSubscription = $meter->subscription;
            $meter->update(['subscription_id' => null]);
            $previousSubscription?->update(['meter_number' => null]);

            $this->recordEvent(
                $meter,
                $previousSubscription?->id,
                $userId,
                'removal',
                $description ?? 'Compteur retiré de son abonnement.',
            );

            return;
        }

        if ($meter->status === 'retired') {
            throw ValidationException::withMessages([
                'subscription_id' => 'Un compteur retiré ne peut pas être affecté.',
            ]);
        }

        if ($meter->subscription_id !== null && $meter->subscription_id !== $subscription->id) {
            throw ValidationException::withMessages([
                'subscription_id' => 'Désaffectez ce compteur de son abonnement actuel avant de le déplacer.',
            ]);
        }

        if ($meter->subscription_id === $subscription->id) {
            return;
        }

        $previousMeter = WaterMeter::query()
            ->where('subscription_id', $subscription->id)
            ->lockForUpdate()
            ->first();

        if ($previousMeter !== null) {
            $previousMeter->update(['subscription_id' => null]);
            $subscription->update(['meter_number' => null]);

            $this->recordEvent(
                $previousMeter,
                $subscription->id,
                $userId,
                'replacement',
                'Remplacé par le compteur '.$meter->serial_number.'.',
            );
        }

        $meter->update([
            'subscription_id' => $subscription->id,
            'installed_at' => $meter->installed_at ?? today(),
        ]);
        $subscription->update(['meter_number' => $meter->serial_number]);

        $this->recordEvent(
            $meter,
            $subscription->id,
            $userId,
            'installation',
            $description ?? 'Compteur affecté à l’abonnement.',
        );
    }

    private function recordEvent(
        WaterMeter $meter,
        ?int $subscriptionId,
        ?int $userId,
        string $eventType,
        string $description,
        ?string $eventAt = null,
    ): void {
        $meter->events()->create([
            'subscription_id' => $subscriptionId,
            'performed_by_user_id' => $userId,
            'event_type' => $eventType,
            'description' => $description,
            'event_at' => $eventAt ?? today(),
        ]);
    }

    private static function statusLabel(string $status): string
    {
        return match ($status) {
            'active' => 'En service',
            'defective' => 'Défectueux',
            'blocked' => 'Bloqué',
            'in_repair' => 'En réparation',
            'retired' => 'Retiré',
            default => $status,
        };
    }

    private function actorId(Request $request): ?int
    {
        return $request->user()->exists ? $request->user()->id : null;
    }
}
