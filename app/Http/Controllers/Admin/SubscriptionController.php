<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:active,suspended,terminated'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $subscriptions = Subscription::query()
            ->with([
                'user',
                'waterMeter',
                'events' => fn ($query) => $query->latest('event_at')->limit(5),
            ])
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($validated['search'] ?? null, fn ($query, $search) => $query
                ->where(fn ($query) => $query
                    ->where('reference', 'like', '%'.$search.'%')
                    ->orWhere('service_address', 'like', '%'.$search.'%')
                    ->orWhereHas('user', fn ($query) => $query
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%'))))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.subscriptions.index', [
            'subscriptions' => $subscriptions,
            'customers' => User::query()
                ->where('role', UserRole::Customer->value)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function updateStatus(Request $request, Subscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:active,suspended,terminated'],
        ]);

        DB::transaction(function () use ($request, $subscription, $validated): void {
            $lockedSubscription = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);

            if ($lockedSubscription->status === 'terminated' && $validated['status'] !== 'terminated') {
                throw ValidationException::withMessages([
                    'status' => 'Un abonnement résilié ne peut pas être réactivé.',
                ]);
            }

            if ($lockedSubscription->status === $validated['status']) {
                return;
            }

            $oldStatus = $lockedSubscription->status;
            $lockedSubscription->update(['status' => $validated['status']]);
            $lockedSubscription->events()->create([
                'performed_by_user_id' => $request->user()->exists ? $request->user()->id : null,
                'event_type' => 'status_changed',
                'old_status' => $oldStatus,
                'new_status' => $validated['status'],
                'description' => 'Statut du contrat modifié par un administrateur.',
                'event_at' => now(),
            ]);
        });

        return back()->with('status', 'subscription-status-updated');
    }

    public function transferHolder(Request $request, Subscription $subscription): RedirectResponse
    {
        $request->merge([
            'new_customer_email' => Str::lower(trim((string) $request->input('new_customer_email'))),
            'new_customer_phone' => User::normalizePhone($request->input('new_customer_phone')),
        ]);
        $validated = $request->validate([
            'new_customer' => ['required', 'boolean'],
            'user_id' => ['exclude_if:new_customer,1', 'required', 'integer', 'exists:users,id'],
            'new_customer_name' => ['exclude_unless:new_customer,1', 'required', 'string', 'max:255'],
            'new_customer_email' => ['exclude_unless:new_customer,1', 'required', 'email', 'max:255', 'unique:users,email'],
            'new_customer_phone' => ['exclude_unless:new_customer,1', 'nullable', 'string', 'max:30', 'regex:/^\+?[0-9]+$/', 'unique:users,phone'],
            'new_customer_password' => ['exclude_unless:new_customer,1', 'required', 'confirmed', Password::min(8)],
        ]);

        DB::transaction(function () use ($request, $subscription, $validated): void {
            $lockedSubscription = Subscription::query()
                ->with('user')
                ->lockForUpdate()
                ->findOrFail($subscription->id);
            $this->ensureSubscriptionIsTransferable($lockedSubscription);

            if ($validated['new_customer']) {
                $newUser = User::create([
                    'name' => $validated['new_customer_name'],
                    'email' => $validated['new_customer_email'],
                    'phone' => $validated['new_customer_phone'] ?? null,
                    'password' => $validated['new_customer_password'],
                    'role' => UserRole::Customer,
                ]);
            } else {
                $newUser = User::query()
                    ->where('role', UserRole::Customer->value)
                    ->find($validated['user_id']);

                if ($newUser === null) {
                    throw ValidationException::withMessages([
                        'user_id' => 'Le compte sélectionné doit être un compte client.',
                    ]);
                }
            }

            if ($lockedSubscription->user_id === $newUser->id) {
                throw ValidationException::withMessages([
                    'user_id' => 'Ce client est déjà titulaire de l’abonnement.',
                ]);
            }

            $previousUser = $lockedSubscription->user;
            $lockedSubscription->update(['user_id' => $newUser->id]);
            $lockedSubscription->events()->create([
                'performed_by_user_id' => $request->user()->exists ? $request->user()->id : null,
                'event_type' => 'holder_changed',
                'previous_user_id' => $previousUser->id,
                'new_user_id' => $newUser->id,
                'old_user_name' => $previousUser->name,
                'new_user_name' => $newUser->name,
                'old_user_email' => $previousUser->email,
                'new_user_email' => $newUser->email,
                'description' => 'Titulaire du contrat modifié par un administrateur.',
                'event_at' => now(),
            ]);
        });

        return back()->with('status', 'subscription-holder-transferred');
    }

    public function updateOccupant(Request $request, Subscription $subscription): RedirectResponse
    {
        $request->merge(['occupant_phone' => User::normalizePhone($request->input('occupant_phone'))]);
        $validated = $request->validate([
            'occupant_name' => ['required', 'string', 'max:255'],
            'occupant_phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9]+$/'],
        ]);

        DB::transaction(function () use ($request, $subscription, $validated): void {
            $lockedSubscription = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);
            $this->ensureSubscriptionIsTransferable($lockedSubscription);
            $oldOccupantName = $lockedSubscription->occupant_name;
            $oldOccupantPhone = $lockedSubscription->occupant_phone;

            if ($oldOccupantName === $validated['occupant_name'] && $oldOccupantPhone === ($validated['occupant_phone'] ?? null)) {
                throw ValidationException::withMessages([
                    'occupant_name' => 'Les coordonnées correspondent déjà à l’occupant enregistré.',
                ]);
            }

            $lockedSubscription->update([
                'occupant_name' => $validated['occupant_name'],
                'occupant_phone' => $validated['occupant_phone'] ?? null,
            ]);
            $lockedSubscription->events()->create([
                'performed_by_user_id' => $request->user()->exists ? $request->user()->id : null,
                'event_type' => 'occupant_changed',
                'old_occupant_name' => $oldOccupantName,
                'new_occupant_name' => $validated['occupant_name'],
                'old_occupant_phone' => $oldOccupantPhone,
                'new_occupant_phone' => $validated['occupant_phone'] ?? null,
                'description' => 'Occupant du point de livraison modifié par un administrateur.',
                'event_at' => now(),
            ]);
        });

        return back()->with('status', 'subscription-occupant-updated');
    }

    private function ensureSubscriptionIsTransferable(Subscription $subscription): void
    {
        if ($subscription->status === 'terminated') {
            throw ValidationException::withMessages([
                'subscription' => 'Un abonnement résilié ne peut plus être modifié.',
            ]);
        }
    }
}
