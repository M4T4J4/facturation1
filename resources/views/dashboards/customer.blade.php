@extends('layouts.app')

@section('title', 'Espace abonné')

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase text-hydro-coral">Espace abonné</p>
            <h1 class="mt-2 text-3xl font-semibold text-hydro-ink">Bonjour, {{ auth()->user()->name }}</h1>
            <p class="mt-2 text-sm text-slate-600">Suivez votre abonnement, vos relevés et vos factures.</p>
        </div>
        <a class="bg-hydro-teal px-4 py-3 text-sm font-semibold text-white hover:bg-hydro-ink" href="{{ route('client.requests.index') }}">Demander un raccordement</a>
    </div>

    <section aria-label="Résumé du compte" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="border border-hydro-ink/10 bg-white p-5"><p class="text-sm text-slate-600">Abonnements actifs</p><p class="mt-3 text-3xl font-semibold tabular-nums">{{ $subscriptions->where('status', 'active')->count() }}</p></div>
        <div class="border border-hydro-ink/10 bg-white p-5"><p class="text-sm text-slate-600">Factures à régler</p><p class="mt-3 text-3xl font-semibold tabular-nums">{{ $unpaidInvoices }}</p></div>
        <a class="border border-hydro-ink/10 bg-white p-5 hover:border-hydro-teal" href="{{ route('client.invoices.index') }}"><p class="text-sm text-slate-600">Tarifs appliqués</p><p class="mt-3 text-lg font-semibold">Détails sur vos factures</p></a>
        <a class="border border-hydro-ink/10 bg-white p-5 hover:border-hydro-teal" href="{{ route('client.invoices.index') }}"><p class="text-sm text-slate-600">Paiements et soldes</p><p class="mt-3 text-lg font-semibold">Consulter mes factures</p></a>
    </section>

    <section class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        <div class="border border-hydro-ink/10 bg-white p-6 sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-hydro-ink">Mes compteurs</h2>
                <a class="text-sm font-semibold text-hydro-teal underline underline-offset-4" href="{{ route('client.readings.index') }}">Envoyer un relevé</a>
            </div>
            @forelse ($subscriptions as $subscription)
                <div class="mt-5 border-t border-hydro-ink/10 pt-5">
                    <div class="flex flex-wrap justify-between gap-3"><p class="font-semibold">{{ $subscription->reference }}</p><span class="text-sm text-emerald-700">{{ $subscription->status === 'active' ? 'Actif' : 'Suspendu' }}</span></div>
                    <p class="mt-1 text-sm text-slate-600">{{ $subscription->service_address }}</p>
                    <p class="mt-3 text-sm">Compteur {{ $subscription->waterMeter?->serial_number ?: 'en cours d’attribution' }} · Dernier index {{ $subscription->waterMeter?->readings->first()?->current_index ?? 'non relevé' }} m³</p>
                    <p class="mt-1 text-sm text-slate-600">Le détail du tarif, des tranches et des taxes figure sur vos factures.</p>
                </div>
            @empty
                <p class="mt-5 border-t border-hydro-ink/10 pt-5 text-sm leading-6 text-slate-600">Aucun compteur actif pour le moment. Déposez une demande de raccordement pour commencer.</p>
            @endforelse
        </div>
        <aside class="flex flex-col justify-between gap-6 bg-hydro-ink p-6 text-white">
            <div>
                <p class="text-xs font-semibold uppercase text-hydro-lime">Mon compte</p>
                <p class="mt-3 break-words text-sm">{{ auth()->user()->email }}</p>
                <p class="mt-1 text-sm text-white/75">{{ auth()->user()->phone ?: 'Téléphone non renseigné' }}</p>
                @if ($latestRequest)
                    <p class="mt-6 text-xs font-semibold uppercase text-hydro-lime">Dernière demande</p>
                    <p class="mt-2 text-sm">{{ $latestRequest->service_address }}</p>
                    <p class="mt-1 text-sm text-white/75">Statut : {{ str_replace('_', ' ', ucfirst($latestRequest->status)) }}</p>
                @endif
            </div>
            <a class="text-sm font-semibold text-hydro-lime underline underline-offset-4" href="{{ route('profile.edit') }}">Modifier mon profil</a>
        </aside>
    </section>
@endsection
