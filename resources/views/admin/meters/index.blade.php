@extends('layouts.app')

@section('title', 'Gestion des compteurs')

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase text-hydro-coral">Parc technique</p>
            <h1 class="mt-2 text-3xl font-semibold text-hydro-ink">Gestion des compteurs</h1>
            <p class="mt-2 text-sm text-slate-600">Inventaire, état, affectation aux abonnements et historique des interventions.</p>
        </div>
        <a class="text-sm font-semibold text-hydro-teal underline underline-offset-4" href="{{ route('admin.water.dashboard') }}">Retour à la gestion de l’eau</a>
    </div>

    @if ($errors->any())
        <div class="mb-6 border-l-4 border-red-700 bg-white px-4 py-3 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
    @endif

    <section aria-labelledby="meter-alerts-title" class="mb-10">
        <div class="mb-4 flex items-end justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase text-hydro-coral">Surveillance</p>
                <h2 id="meter-alerts-title" class="mt-1 text-xl font-semibold text-hydro-ink">Alertes compteurs</h2>
            </div>
            <span class="text-sm text-slate-600">{{ $alerts === [] ? 'Aucune alerte' : count($alerts).' alerte(s)' }}</span>
        </div>
        <div class="divide-y divide-amber-200 border-y border-amber-200 bg-amber-50">
            @forelse ($alerts as $alert)
                <article class="flex flex-wrap items-center justify-between gap-3 p-4">
                    <div>
                        <p class="font-semibold text-amber-950">{{ $alert['meter']->serial_number }} · {{ $alert['meter']->subscription?->user?->name ?? 'Non affecté' }}</p>
                        <p class="mt-1 text-sm text-amber-900">{{ $alert['message'] }}</p>
                    </div>
                    <a class="text-sm font-semibold text-hydro-teal underline underline-offset-4" href="#meter-{{ $alert['meter']->id }}">Voir le compteur</a>
                </article>
            @empty
                <p class="p-4 text-sm text-slate-700">Aucune anomalie détectée parmi les compteurs affectés.</p>
            @endforelse
        </div>
        <p class="mt-2 text-xs text-slate-500">Une anomalie de consommation est signalée à partir de 20 m³ lorsque le relevé atteint au moins le double du précédent. Trois index consécutifs identiques signalent un compteur potentiellement bloqué.</p>
    </section>

    <section aria-labelledby="new-meter-title" class="mb-10 border border-hydro-ink/10 bg-white p-5 sm:p-7">
        <h2 id="new-meter-title" class="text-xl font-semibold text-hydro-ink">Enregistrer un compteur</h2>
        <form class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-5" method="POST" action="{{ route('admin.meters.store') }}">
            @csrf
            <label class="text-sm font-medium text-slate-700">Numéro de série
                <input class="mt-1 w-full border border-slate-300 px-3 py-2.5" name="serial_number" value="{{ old('serial_number') }}" maxlength="255" required>
            </label>
            <label class="text-sm font-medium text-slate-700">État
                <select class="mt-1 w-full border border-slate-300 bg-white px-3 py-2.5" name="status" required>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(old('status', 'active') === $status)>
                            @switch($status)
                                @case('active') En service @break
                                @case('defective') Défectueux @break
                                @case('blocked') Bloqué @break
                                @case('in_repair') En réparation @break
                                @case('retired') Retiré @break
                            @endswitch
                        </option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-medium text-slate-700">Date de pose
                <input class="mt-1 w-full border border-slate-300 px-3 py-2.5" name="installed_at" type="date" max="{{ now()->toDateString() }}" value="{{ old('installed_at') }}">
            </label>
            <label class="text-sm font-medium text-slate-700">Affecter à un abonnement
                <select class="mt-1 w-full border border-slate-300 bg-white px-3 py-2.5" name="subscription_id">
                    <option value="">Aucune affectation</option>
                    @foreach ($subscriptions as $subscription)
                        <option value="{{ $subscription->id }}" @selected(old('subscription_id') == $subscription->id)>
                            {{ $subscription->reference }} · {{ $subscription->user->name }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-medium text-slate-700">Note
                <input class="mt-1 w-full border border-slate-300 px-3 py-2.5" name="notes" value="{{ old('notes') }}" maxlength="2000">
            </label>
            <button class="w-fit bg-hydro-teal px-4 py-3 text-sm font-semibold text-white hover:bg-hydro-ink sm:col-span-2 xl:col-span-5" type="submit">Ajouter le compteur</button>
        </form>
    </section>

    <section aria-labelledby="meter-list-title">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase text-hydro-coral">Inventaire</p>
                <h2 id="meter-list-title" class="mt-1 text-xl font-semibold text-hydro-ink">Compteurs enregistrés</h2>
            </div>
            <span class="text-sm text-slate-600">{{ $meters->total() }} compteur(s)</span>
        </div>

        <div class="grid gap-5">
            @forelse ($meters as $meter)
                <article id="meter-{{ $meter->id }}" class="scroll-mt-6 border border-hydro-ink/10 bg-white p-5 sm:p-7">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-hydro-ink">{{ $meter->serial_number }}</h3>
                            <p class="mt-1 text-sm text-slate-600">
                                @if ($meter->subscription)
                                    {{ $meter->subscription->user->name }} · {{ $meter->subscription->reference }} · {{ $meter->subscription->service_address }}
                                @else
                                    Non affecté à un abonnement
                                @endif
                            </p>
                        </div>
                        <span @class([
                            'px-3 py-1 text-xs font-semibold',
                            'bg-emerald-50 text-emerald-800' => $meter->status === 'active',
                            'bg-red-50 text-red-800' => in_array($meter->status, ['defective', 'blocked'], true),
                            'bg-amber-50 text-amber-900' => $meter->status === 'in_repair',
                            'bg-slate-100 text-slate-700' => $meter->status === 'retired',
                        ])>
                            @switch($meter->status)
                                @case('active') En service @break
                                @case('defective') Défectueux @break
                                @case('blocked') Bloqué @break
                                @case('in_repair') En réparation @break
                                @case('retired') Retiré @break
                            @endswitch
                        </span>
                    </div>

                    <form class="mt-5 grid gap-3 border-t border-hydro-ink/10 pt-5 sm:grid-cols-2 xl:grid-cols-5" method="POST" action="{{ route('admin.meters.update', $meter) }}">
                        @csrf @method('PUT')
                        <label class="text-xs font-medium text-slate-600">Numéro de série
                            <input class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm" name="serial_number" value="{{ $meter->serial_number }}" maxlength="255" required>
                        </label>
                        <label class="text-xs font-medium text-slate-600">État
                            <select class="mt-1 w-full border border-slate-300 bg-white px-3 py-2 text-sm" name="status" required>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected($meter->status === $status)>
                                        @switch($status)
                                            @case('active') En service @break
                                            @case('defective') Défectueux @break
                                            @case('blocked') Bloqué @break
                                            @case('in_repair') En réparation @break
                                            @case('retired') Retiré @break
                                        @endswitch
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="text-xs font-medium text-slate-600">Date de pose
                            <input class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm" name="installed_at" type="date" max="{{ now()->toDateString() }}" value="{{ $meter->installed_at?->toDateString() }}">
                        </label>
                        <label class="text-xs font-medium text-slate-600 sm:col-span-2">Note
                            <input class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm" name="notes" value="{{ $meter->notes }}" maxlength="2000">
                        </label>
                        <button class="w-fit border border-hydro-teal px-3 py-2 text-sm font-semibold text-hydro-teal hover:bg-hydro-mist sm:col-span-2 xl:col-span-5" type="submit">Enregistrer les modifications</button>
                    </form>

                    <div class="mt-5 grid gap-6 border-t border-hydro-ink/10 pt-5 xl:grid-cols-2">
                        <div>
                            <h4 class="font-semibold text-hydro-ink">Affectation</h4>
                            <form class="mt-3 grid gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]" method="POST" action="{{ route('admin.meters.assignment', $meter) }}">
                                @csrf
                                <select class="border border-slate-300 bg-white px-3 py-2 text-sm" name="subscription_id" aria-label="Abonnement affecté">
                                    <option value="">Retirer de l’abonnement</option>
                                    @foreach ($subscriptions as $subscription)
                                        <option value="{{ $subscription->id }}" @selected($meter->subscription_id === $subscription->id)>
                                            {{ $subscription->reference }} · {{ $subscription->user->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <input class="border border-slate-300 px-3 py-2 text-sm" name="description" maxlength="2000" placeholder="Note d’installation ou de retrait">
                                <button class="bg-hydro-teal px-3 py-2 text-sm font-semibold text-white hover:bg-hydro-ink" type="submit">Affecter</button>
                            </form>
                        </div>

                        <div>
                            <h4 class="font-semibold text-hydro-ink">Enregistrer une intervention</h4>
                            <form class="mt-3 grid gap-3 sm:grid-cols-[minmax(0,0.7fr)_minmax(0,1fr)_auto]" method="POST" action="{{ route('admin.meters.events.store', $meter) }}">
                                @csrf
                                <select class="border border-slate-300 bg-white px-3 py-2 text-sm" name="event_type" aria-label="Type d’intervention" required>
                                    <option value="inspection">Contrôle</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="repair">Réparation</option>
                                </select>
                                <input class="border border-slate-300 px-3 py-2 text-sm" name="description" maxlength="2000" placeholder="Description de l’intervention" required>
                                <input class="border border-slate-300 px-3 py-2 text-sm sm:col-span-2" name="event_at" type="date" max="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" required aria-label="Date d’intervention">
                                <button class="w-fit border border-hydro-teal px-3 py-2 text-sm font-semibold text-hydro-teal hover:bg-hydro-mist sm:col-span-2" type="submit">Ajouter à l’historique</button>
                            </form>
                        </div>
                    </div>

                    <div class="mt-5 border-t border-hydro-ink/10 pt-5">
                        <h4 class="font-semibold text-hydro-ink">Historique</h4>
                        <ul class="mt-3 grid gap-2 text-sm text-slate-600 sm:grid-cols-2">
                            @forelse ($meter->events as $event)
                                <li class="border-l-2 border-hydro-teal/40 pl-3">
                                    <span class="font-medium text-hydro-ink">{{ $event->event_at->format('d/m/Y') }} ·
                                        @switch($event->event_type)
                                            @case('installation') Pose / affectation @break
                                            @case('replacement') Remplacement @break
                                            @case('removal') Retrait @break
                                            @case('registration') Enregistrement @break
                                            @case('status_change') Changement d’état @break
                                            @case('serial_update') Modification du numéro de série @break
                                            @case('inspection') Contrôle @break
                                            @case('maintenance') Maintenance @break
                                            @case('repair') Réparation @break
                                            @default {{ str_replace('_', ' ', ucfirst($event->event_type)) }}
                                        @endswitch
                                    </span>
                                    <span class="block">{{ $event->description }}</span>
                                    @if ($event->performer)<span class="block text-xs text-slate-500">Par {{ $event->performer->name }}</span>@endif
                                </li>
                            @empty
                                <li>Aucune intervention enregistrée.</li>
                            @endforelse
                        </ul>
                    </div>
                </article>
            @empty
                <p class="border-y border-hydro-ink/10 bg-white p-6 text-sm text-slate-600">Aucun compteur dans l’inventaire.</p>
            @endforelse
        </div>

        <div class="mt-6">{{ $meters->links() }}</div>
    </section>
@endsection
