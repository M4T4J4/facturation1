@extends('layouts.app')

@section('title', 'Abonnements')

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase text-hydro-coral">Administration</p>
            <h1 class="mt-2 text-3xl font-semibold text-hydro-ink">Abonnements</h1>
            <p class="mt-2 text-sm text-slate-600">Contrats, titulaire, occupant, statut et historique de chaque branchement.</p>
        </div>
        <a class="text-sm font-semibold text-hydro-teal underline underline-offset-4" href="{{ route('admin.water.dashboard') }}">Retour à la gestion de l’eau</a>
    </div>

    <section class="mb-8 rounded-sm border border-hydro-ink/10 bg-white p-5">
        <form class="grid gap-4 md:grid-cols-[1fr_220px]" method="GET" action="{{ route('admin.subscriptions.index') }}">
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700" for="search">Recherche</label>
                <input class="w-full border border-slate-300 bg-white px-3 py-2.5 text-sm" id="search" name="search" type="text" value="{{ request('search') }}" placeholder="Référence, adresse, client...">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700" for="status">Statut</label>
                <select class="w-full border border-slate-300 bg-white px-3 py-2.5 text-sm" id="status" name="status">
                    <option value="">Tous</option>
                    <option value="active" @selected(request('status') === 'active')>Actif</option>
                    <option value="suspended" @selected(request('status') === 'suspended')>Suspendu</option>
                    <option value="terminated" @selected(request('status') === 'terminated')>Résilié</option>
                </select>
            </div>
        </form>
    </section>

    <div class="space-y-6">
        @forelse ($subscriptions as $subscription)
            <article class="border border-hydro-ink/10 bg-white p-5 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-hydro-coral">{{ $subscription->reference }}</p>
                        <h2 class="mt-2 text-xl font-semibold text-hydro-ink">{{ $subscription->user->name }}</h2>
                    </div>
                    <span class="rounded-full border border-hydro-ink/10 bg-hydro-mist px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-hydro-ink">
                        {{ match ($subscription->status) {
                            'active' => 'Actif',
                            'suspended' => 'Suspendu',
                            'terminated' => 'Résilié',
                            default => ucfirst($subscription->status),
                        } }}
                    </span>
                </div>

                <div class="mt-5 grid gap-5 lg:grid-cols-3">
                    <div class="space-y-2 text-sm text-slate-700">
                        <p><span class="font-semibold text-hydro-ink">Adresse :</span> {{ $subscription->service_address }}</p>
                        <p><span class="font-semibold text-hydro-ink">Client :</span> {{ $subscription->user->email }}</p>
                        <p><span class="font-semibold text-hydro-ink">Occupant :</span> {{ $subscription->occupant_name ?: '—' }} {{ $subscription->occupant_phone ? '('.$subscription->occupant_phone.')' : '' }}</p>
                        <p><span class="font-semibold text-hydro-ink">Compteur :</span> {{ $subscription->waterMeter?->serial_number ?: 'Aucun compteur' }}</p>
                    </div>

                    <div class="space-y-3">
                        <form method="POST" action="{{ route('admin.subscriptions.status.update', $subscription) }}">
                            @csrf
                            @method('PATCH')
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="status-{{ $subscription->id }}">Changer le statut</label>
                            <div class="flex gap-2">
                                <select class="w-full border border-slate-300 bg-white px-3 py-2 text-sm" id="status-{{ $subscription->id }}" name="status">
                                    <option value="active" @selected($subscription->status === 'active')>Actif</option>
                                    <option value="suspended" @selected($subscription->status === 'suspended')>Suspendu</option>
                                    <option value="terminated" @selected($subscription->status === 'terminated')>Résilié</option>
                                </select>
                                <button class="border border-hydro-teal px-3 py-2 text-sm font-semibold text-hydro-teal hover:bg-hydro-mist" type="submit">Valider</button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('admin.subscriptions.occupant.update', $subscription) }}">
                            @csrf
                            @method('PATCH')
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700" for="occupant_name_{{ $subscription->id }}">Nom occupant</label>
                                    <input class="w-full border border-slate-300 bg-white px-3 py-2 text-sm" id="occupant_name_{{ $subscription->id }}" name="occupant_name" type="text" value="{{ old('occupant_name', $subscription->occupant_name) }}" required>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700" for="occupant_phone_{{ $subscription->id }}">Téléphone</label>
                                    <input class="w-full border border-slate-300 bg-white px-3 py-2 text-sm" id="occupant_phone_{{ $subscription->id }}" name="occupant_phone" type="text" value="{{ old('occupant_phone', $subscription->occupant_phone) }}">
                                </div>
                            </div>
                            <div class="mt-3">
                                <button class="border border-hydro-teal px-3 py-2 text-sm font-semibold text-hydro-teal hover:bg-hydro-mist" type="submit">Mettre à jour l’occupant</button>
                            </div>
                        </form>
                    </div>

                    <div>
                        <p class="mb-2 text-sm font-semibold text-hydro-ink">Transfert de titulaire</p>
                        <form class="space-y-3" method="POST" action="{{ route('admin.subscriptions.holder.transfer', $subscription) }}">
                            @csrf
                            @method('PATCH')
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input name="new_customer" type="radio" value="0" checked>
                                Assigner à un client existant
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input name="new_customer" type="radio" value="1">
                                Créer un nouveau client
                            </label>

                            <select class="w-full border border-slate-300 bg-white px-3 py-2 text-sm" name="user_id">
                                <option value="">Sélectionner un client existant</option>
                                @foreach ($customers as $customer)
                                    @if ($customer->id !== $subscription->user_id)
                                        <option value="{{ $customer->id }}">{{ $customer->name }} · {{ $customer->email }}</option>
                                    @endif
                                @endforeach
                            </select>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <input class="w-full border border-slate-300 bg-white px-3 py-2 text-sm" name="new_customer_name" placeholder="Nom du nouveau client" type="text">
                                <input class="w-full border border-slate-300 bg-white px-3 py-2 text-sm" name="new_customer_email" placeholder="Email du nouveau client" type="email">
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <input class="w-full border border-slate-300 bg-white px-3 py-2 text-sm" name="new_customer_phone" placeholder="Téléphone" type="text">
                                <input class="w-full border border-slate-300 bg-white px-3 py-2 text-sm" name="new_customer_password" placeholder="Mot de passe" type="password">
                            </div>
                            <input class="w-full border border-slate-300 bg-white px-3 py-2 text-sm" name="new_customer_password_confirmation" placeholder="Confirmer le mot de passe" type="password">
                            <button class="border border-hydro-teal px-3 py-2 text-sm font-semibold text-hydro-teal hover:bg-hydro-mist" type="submit">Transférer le titulaire</button>
                        </form>
                    </div>
                </div>

                @if ($subscription->events->isNotEmpty())
                    <div class="mt-6 border-t border-hydro-ink/10 pt-5">
                        <p class="mb-3 text-sm font-semibold text-hydro-ink">Historique</p>
                        <ul class="space-y-2 text-sm text-slate-600">
                            @foreach ($subscription->events as $event)
                                <li class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-2 last:border-b-0">
                                    <span>{{ $event->description ?: ucfirst(str_replace('_', ' ', $event->event_type)) }}</span>
                                    <span class="text-xs text-slate-500">{{ $event->event_at?->format('d/m/Y H:i') ?? '—' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </article>
        @empty
            <div class="border border-hydro-ink/10 bg-white p-8 text-center text-sm text-slate-600">
                Aucun abonnement trouvé.
            </div>
        @endforelse
    </div>

    @if ($subscriptions->hasPages())
        <div class="mt-6">{{ $subscriptions->links() }}</div>
    @endif
@endsection
