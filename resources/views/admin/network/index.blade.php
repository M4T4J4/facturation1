@extends('layouts.app')

@section('title', 'Réseau & maintenance')

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase text-hydro-coral">Gestion technique</p>
            <h1 class="mt-2 text-3xl font-semibold text-hydro-ink">Réseau, fuites et maintenance</h1>
            <p class="mt-2 text-sm text-slate-600">Suivez les conduites, réservoirs, pompes et interventions du réseau.</p>
        </div>
        <a class="text-sm font-semibold text-hydro-teal underline underline-offset-4" href="{{ route('admin.water.dashboard') }}">Retour à la gestion de l’eau</a>
    </div>

    <section class="mb-8 grid gap-6 xl:grid-cols-2">
        <div class="border border-hydro-ink/10 bg-white p-5 sm:p-6">
            <h2 class="text-xl font-semibold text-hydro-ink">Créer un équipement réseau</h2>
            <form class="mt-5 grid gap-4 sm:grid-cols-2" method="POST" action="{{ route('admin.network.assets.store') }}">
                @csrf
                <label class="text-sm text-slate-700">Nom<input class="mt-1 w-full border border-slate-300 px-3 py-2" name="name" required></label>
                <label class="text-sm text-slate-700">Type<input class="mt-1 w-full border border-slate-300 px-3 py-2" name="type" placeholder="Pompe, réservoir, conduite..." required></label>
                <label class="text-sm text-slate-700">Localisation<input class="mt-1 w-full border border-slate-300 px-3 py-2" name="location" required></label>
                <label class="text-sm text-slate-700">État<select class="mt-1 w-full border border-slate-300 bg-white px-3 py-2" name="status"><option value="operational">Opérationnel</option><option value="warning">À surveiller</option><option value="offline">Hors service</option></select></label>
                <label class="text-sm text-slate-700">Capacité (m³)<input class="mt-1 w-full border border-slate-300 px-3 py-2" name="capacity" type="number" min="0"></label>
                <label class="text-sm text-slate-700">Dernière inspection<input class="mt-1 w-full border border-slate-300 px-3 py-2" name="last_inspection_at" type="date"></label>
                <label class="sm:col-span-2 text-sm text-slate-700">Notes<textarea class="mt-1 w-full border border-slate-300 px-3 py-2" name="notes" rows="3"></textarea></label>
                <button class="w-fit bg-hydro-teal px-4 py-3 text-sm font-semibold text-white hover:bg-hydro-ink sm:col-span-2" type="submit">Enregistrer l’équipement</button>
            </form>
        </div>

        <div class="border border-hydro-ink/10 bg-white p-5 sm:p-6">
            <h2 class="text-xl font-semibold text-hydro-ink">Déclarer une anomalie</h2>
            <form class="mt-5 grid gap-4 sm:grid-cols-2" method="POST" action="{{ route('admin.network.incidents.store') }}">
                @csrf
                <label class="text-sm text-slate-700 sm:col-span-2">Équipement
                    <select class="mt-1 w-full border border-slate-300 bg-white px-3 py-2" name="water_network_asset_id" required>
                        <option value="">Sélectionner</option>
                        @foreach ($assets as $asset)
                            <option value="{{ $asset->id }}">{{ $asset->name }} · {{ $asset->type }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm text-slate-700 sm:col-span-2">Titre<input class="mt-1 w-full border border-slate-300 px-3 py-2" name="title" required></label>
                <label class="text-sm text-slate-700">Type<input class="mt-1 w-full border border-slate-300 px-3 py-2" name="kind" placeholder="Fuite, panne..." required></label>
                <label class="text-sm text-slate-700">Gravité<select class="mt-1 w-full border border-slate-300 bg-white px-3 py-2" name="severity"><option value="low">Faible</option><option value="medium" selected>Moyenne</option><option value="high">Élevée</option><option value="critical">Critique</option></select></label>
                <label class="text-sm text-slate-700">Statut<select class="mt-1 w-full border border-slate-300 bg-white px-3 py-2" name="status"><option value="open">Ouvert</option><option value="in_progress">En cours</option><option value="resolved">Résolu</option></select></label>
                <label class="text-sm text-slate-700">Assigné à<select class="mt-1 w-full border border-slate-300 bg-white px-3 py-2" name="assigned_to_user_id"><option value="">Non affecté</option>@foreach ($agents as $agent)<option value="{{ $agent->id }}">{{ $agent->name }}</option>@endforeach</select></label>
                <label class="text-sm text-slate-700">Date de signalement<input class="mt-1 w-full border border-slate-300 px-3 py-2" name="reported_at" type="date" value="{{ now()->toDateString() }}"></label>
                <label class="sm:col-span-2 text-sm text-slate-700">Description<textarea class="mt-1 w-full border border-slate-300 px-3 py-2" name="description" rows="3"></textarea></label>
                <button class="w-fit bg-hydro-ink px-4 py-3 text-sm font-semibold text-white hover:bg-hydro-teal sm:col-span-2" type="submit">Enregistrer l’incident</button>
            </form>
        </div>
    </section>

    <section class="mb-8 grid gap-8 xl:grid-cols-2">
        <div class="border border-hydro-ink/10 bg-white p-5 sm:p-6">
            <h2 class="text-xl font-semibold text-hydro-ink">Équipements réseau</h2>
            <div class="mt-5 space-y-4">
                @forelse ($assets as $asset)
                    <article class="border border-hydro-ink/10 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h3 class="font-semibold text-hydro-ink">{{ $asset->name }}</h3>
                            <span class="text-xs font-semibold text-slate-600">{{ ucfirst($asset->status) }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-600">{{ $asset->type }} · {{ $asset->location }}</p>
                        @if ($asset->capacity)
                            <p class="mt-2 text-sm text-slate-600">Capacité : {{ number_format($asset->capacity, 0, ',', ' ') }} m³</p>
                        @endif
                        @if ($asset->notes)
                            <p class="mt-2 text-sm text-slate-600">{{ $asset->notes }}</p>
                        @endif
                    </article>
                @empty
                    <p class="text-sm text-slate-600">Aucun équipement enregistré.</p>
                @endforelse
            </div>
        </div>

        <div class="border border-hydro-ink/10 bg-white p-5 sm:p-6">
            <h2 class="text-xl font-semibold text-hydro-ink">Incidents récents</h2>
            <div class="mt-5 space-y-4">
                @forelse ($incidents as $incident)
                    <article class="border border-hydro-ink/10 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h3 class="font-semibold text-hydro-ink">{{ $incident->title }}</h3>
                            <span class="text-xs font-semibold text-hydro-coral">{{ ucfirst(str_replace('_', ' ', $incident->status)) }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-600">{{ $incident->asset->name ?? 'Équipement' }} · {{ $incident->kind }}</p>
                        @if ($incident->description)
                            <p class="mt-2 text-sm text-slate-600">{{ $incident->description }}</p>
                        @endif
                    </article>
                @empty
                    <p class="text-sm text-slate-600">Aucun incident récent.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="grid gap-8 xl:grid-cols-2">
        <div class="border border-hydro-ink/10 bg-white p-5 sm:p-6">
            <h2 class="text-xl font-semibold text-hydro-ink">Missions terrain</h2>
            <form class="mt-5 grid gap-4 sm:grid-cols-2" method="POST" action="{{ route('admin.network.missions.store') }}">
                @csrf
                <label class="text-sm text-slate-700 sm:col-span-2">Titre<input class="mt-1 w-full border border-slate-300 px-3 py-2" name="title" required></label>
                <label class="text-sm text-slate-700">Zone<select class="mt-1 w-full border border-slate-300 bg-white px-3 py-2" name="water_zone_id"><option value="">Aucune zone</option>@foreach ($zones as $zone)<option value="{{ $zone->id }}">{{ $zone->name }}</option>@endforeach</select></label>
                <label class="text-sm text-slate-700">Agent<select class="mt-1 w-full border border-slate-300 bg-white px-3 py-2" name="assigned_to_user_id"><option value="">Non assigné</option>@foreach ($agents as $agent)<option value="{{ $agent->id }}">{{ $agent->name }}</option>@endforeach</select></label>
                <label class="text-sm text-slate-700">Statut<select class="mt-1 w-full border border-slate-300 bg-white px-3 py-2" name="status"><option value="planned">Planifiée</option><option value="in_progress">En cours</option><option value="completed">Terminée</option></select></label>
                <label class="text-sm text-slate-700">Date prévue<input class="mt-1 w-full border border-slate-300 px-3 py-2" name="scheduled_for" type="date" value="{{ now()->toDateString() }}"></label>
                <label class="sm:col-span-2 text-sm text-slate-700">Notes<textarea class="mt-1 w-full border border-slate-300 px-3 py-2" name="notes" rows="3"></textarea></label>
                <button class="w-fit bg-hydro-teal px-4 py-3 text-sm font-semibold text-white hover:bg-hydro-ink sm:col-span-2" type="submit">Planifier la mission</button>
            </form>
        </div>

        <div class="border border-hydro-ink/10 bg-white p-5 sm:p-6">
            <h2 class="text-xl font-semibold text-hydro-ink">Notifications système</h2>
            <div class="mt-5 space-y-4">
                @forelse ($notifications as $notification)
                    <article class="border border-hydro-ink/10 p-4">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="font-semibold text-hydro-ink">{{ $notification->title }}</h3>
                            <span class="text-xs font-semibold text-slate-500">{{ $notification->type }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-600">{{ $notification->message }}</p>
                    </article>
                @empty
                    <p class="text-sm text-slate-600">Aucune notification.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection
