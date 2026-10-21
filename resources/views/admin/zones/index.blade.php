@extends('layouts.app')

@section('title', 'Zones de distribution')

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase text-hydro-coral">Réseau de distribution</p>
            <h1 class="mt-2 text-3xl font-semibold text-hydro-ink">Zones et secteurs</h1>
            <p class="mt-2 text-sm text-slate-600">Affectez les abonnements et agents de relève, puis suivez la consommation mensuelle par zone.</p>
        </div>
        <a class="text-sm font-semibold text-hydro-teal underline underline-offset-4" href="{{ route('admin.water.dashboard') }}">Retour à la gestion de l’eau</a>
    </div>

    @if ($errors->any())
        <div class="mb-6 border-l-4 border-red-700 bg-white px-4 py-3 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="mb-8 border border-hydro-ink/10 bg-white p-5 sm:p-7">
        <h2 class="text-xl font-semibold text-hydro-ink">Créer une zone</h2>
        <form class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" method="POST" action="{{ route('admin.zones.store') }}">
            @csrf
            <label class="text-sm font-medium text-slate-700">Nom du quartier / secteur
                <input class="mt-1 w-full border border-slate-300 px-3 py-2.5" name="name" value="{{ old('name') }}" maxlength="255" required>
            </label>
            <label class="text-sm font-medium text-slate-700">Code
                <input class="mt-1 w-full border border-slate-300 px-3 py-2.5" name="code" value="{{ old('code') }}" maxlength="50" required>
            </label>
            <label class="text-sm font-medium text-slate-700">Ville / district
                <input class="mt-1 w-full border border-slate-300 px-3 py-2.5" name="district" value="{{ old('district') }}" maxlength="255">
            </label>
            <label class="text-sm font-medium text-slate-700">Description
                <input class="mt-1 w-full border border-slate-300 px-3 py-2.5" name="description" value="{{ old('description') }}" maxlength="2000">
            </label>
            <button class="w-fit bg-hydro-teal px-4 py-3 text-sm font-semibold text-white hover:bg-hydro-ink sm:col-span-2 xl:col-span-4" type="submit">Créer la zone</button>
        </form>
    </section>

    <section class="space-y-6">
        @forelse ($zones as $zone)
            @php($zoneStats = $monthlyStats->get($zone->id))
            <article class="border border-hydro-ink/10 bg-white p-5 sm:p-7">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase text-hydro-coral">{{ $zone->code }} · {{ $zone->district ?: 'District non précisé' }}</p>
                        <h2 class="mt-2 text-xl font-semibold text-hydro-ink">{{ $zone->name }}</h2>
                        @if ($zone->description)
                            <p class="mt-1 text-sm text-slate-600">{{ $zone->description }}</p>
                        @endif
                    </div>
                    <span class="bg-hydro-mist px-3 py-1 text-xs font-semibold text-hydro-ink">{{ $zone->is_active ? 'Zone active' : 'Zone inactive' }}</span>
                </div>

                <dl class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="border border-hydro-ink/10 p-4"><dt class="text-xs text-slate-600">Abonnements actifs</dt><dd class="mt-1 text-xl font-semibold tabular-nums">{{ $zone->active_subscriptions_count }}</dd></div>
                    <div class="border border-hydro-ink/10 p-4"><dt class="text-xs text-slate-600">Abonnements total</dt><dd class="mt-1 text-xl font-semibold tabular-nums">{{ $zone->subscriptions_count }}</dd></div>
                    <div class="border border-hydro-ink/10 p-4"><dt class="text-xs text-slate-600">Consommation facturée ce mois</dt><dd class="mt-1 text-xl font-semibold tabular-nums">{{ number_format((int) ($zoneStats?->consumption_m3 ?? 0), 0, ',', ' ') }} m³</dd></div>
                    <div class="border border-hydro-ink/10 p-4"><dt class="text-xs text-slate-600">Montant facturé ce mois</dt><dd class="mt-1 text-xl font-semibold tabular-nums">{{ number_format((int) ($zoneStats?->billed_amount ?? 0), 0, ',', ' ') }} {{ $settings->currency }}</dd></div>
                </dl>

                <div class="mt-6 grid gap-6 xl:grid-cols-3">
                    <form class="grid content-start gap-3" method="POST" action="{{ route('admin.zones.update', $zone) }}">
                        @csrf
                        @method('PUT')
                        <p class="text-sm font-semibold text-hydro-ink">Paramètres de zone</p>
                        <label class="text-sm text-slate-700">Nom<input class="mt-1 w-full border border-slate-300 px-3 py-2" name="name" value="{{ $zone->name }}" required></label>
                        <label class="text-sm text-slate-700">Code<input class="mt-1 w-full border border-slate-300 px-3 py-2" name="code" value="{{ $zone->code }}" required></label>
                        <label class="text-sm text-slate-700">District<input class="mt-1 w-full border border-slate-300 px-3 py-2" name="district" value="{{ $zone->district }}"></label>
                        <label class="text-sm text-slate-700">Description<textarea class="mt-1 w-full border border-slate-300 px-3 py-2" name="description" rows="2">{{ $zone->description }}</textarea></label>
                        <input name="is_active" type="hidden" value="0">
                        <label class="flex items-center gap-2 text-sm text-slate-700"><input name="is_active" type="checkbox" value="1" @checked($zone->is_active)> Zone active</label>
                        <button class="w-fit border border-hydro-teal px-3 py-2 text-sm font-semibold text-hydro-teal hover:bg-hydro-mist" type="submit">Enregistrer</button>
                    </form>

                    <form class="grid content-start gap-3" method="POST" action="{{ route('admin.zones.agents.sync', $zone) }}">
                        @csrf
                        @method('PUT')
                        <p class="text-sm font-semibold text-hydro-ink">Agents de relève</p>
                        @forelse ($agents as $agent)
                            <label class="flex items-start gap-2 text-sm text-slate-700">
                                <input class="mt-1" name="agent_ids[]" type="checkbox" value="{{ $agent->id }}" @checked($zone->agents->contains($agent->id))>
                                <span>{{ $agent->name }} <span class="block text-xs text-slate-500">{{ $agent->email }}</span></span>
                            </label>
                        @empty
                            <p class="text-sm text-slate-600">Aucun compte agent de terrain.</p>
                        @endforelse
                        <button class="w-fit border border-hydro-teal px-3 py-2 text-sm font-semibold text-hydro-teal hover:bg-hydro-mist" type="submit">Enregistrer les agents</button>
                    </form>

                    <form class="grid content-start gap-3" method="POST" action="{{ route('admin.zones.subscriptions.assign', $zone) }}">
                        @csrf
                        <p class="text-sm font-semibold text-hydro-ink">Affecter un abonnement</p>
                        <label class="text-sm text-slate-700" for="subscription-{{ $zone->id }}">Client et branchement
                            <select class="mt-1 w-full border border-slate-300 bg-white px-3 py-2" id="subscription-{{ $zone->id }}" name="subscription_id" required>
                                <option value="">Sélectionner un abonnement</option>
                                @foreach ($subscriptions as $subscription)
                                    <option value="{{ $subscription->id }}">{{ $subscription->reference }} · {{ $subscription->user->name }} · {{ $subscription->service_address }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button class="w-fit border border-hydro-teal px-3 py-2 text-sm font-semibold text-hydro-teal hover:bg-hydro-mist" type="submit">Affecter à cette zone</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="border border-hydro-ink/10 bg-white p-8 text-center text-sm text-slate-600">Aucune zone de distribution n’a encore été créée.</div>
        @endforelse
    </section>

    @if ($zones->hasPages())
        <div class="mt-6">{{ $zones->links() }}</div>
    @endif
@endsection
