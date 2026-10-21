@extends('layouts.app')

@section('title', 'Gestion eau')

@section('content')
    <div class="mb-8">
        <p class="text-xs font-bold uppercase text-hydro-coral">Espace gestionnaire</p>
        <h1 class="mt-2 text-3xl font-semibold text-hydro-ink">{{ $settings->company_name }}</h1>
        <p class="mt-2 text-sm text-slate-600">Traitement des demandes, relevés, factures et encaissements.</p>
    </div>

    <section aria-label="Indicateurs" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="border border-hydro-ink/10 bg-white p-5"><p class="text-sm text-slate-600">Dossiers à traiter</p><p class="mt-3 text-3xl font-semibold tabular-nums">{{ $requestCount }}</p></div>
        <div class="border border-hydro-ink/10 bg-white p-5"><p class="text-sm text-slate-600">Abonnements</p><p class="mt-3 text-3xl font-semibold tabular-nums">{{ $subscriptionCount }}</p></div>
        <div class="border border-hydro-ink/10 bg-white p-5"><p class="text-sm text-slate-600">Factures impayées</p><p class="mt-3 text-3xl font-semibold tabular-nums">{{ $unpaidInvoiceCount }}</p></div>
        <div class="border border-hydro-ink/10 bg-white p-5"><p class="text-sm text-slate-600">Paiements à vérifier</p><p class="mt-3 text-3xl font-semibold tabular-nums">{{ $pendingPaymentCount }}</p></div>
    </section>

    <section class="mt-10">
        <p class="text-xs font-bold uppercase text-hydro-coral">Facturation</p><h2 class="mb-4 mt-1 text-xl font-semibold text-hydro-ink">Relevés vérifiés sans facture</h2>
        <div class="divide-y divide-hydro-ink/10 border-y border-hydro-ink/10 bg-white">
            @forelse ($readingsToInvoice as $reading)
                <article class="flex flex-wrap items-center justify-between gap-4 p-4">
                    <div><p class="font-semibold">{{ $reading->subscription->user->name }} · {{ $reading->subscription->reference }}</p><p class="mt-1 text-sm text-slate-600">{{ $reading->waterMeter?->serial_number ?? 'Compteur historique' }} · {{ $reading->previous_index }} → {{ $reading->current_index }} m³ · {{ $reading->read_at->format('d/m/Y') }}</p></div>
                    <form method="POST" action="{{ route('admin.water.readings.invoice', $reading) }}">@csrf<button class="bg-hydro-teal px-3 py-2 text-sm font-semibold text-white hover:bg-hydro-ink" type="submit">Générer et envoyer la facture</button></form>
                </article>
            @empty
                <p class="p-5 text-sm text-slate-600">Aucun relevé vérifié en attente de facturation.</p>
            @endforelse
        </div>
    </section>

    <section class="mt-10">
        <p class="text-xs font-bold uppercase text-hydro-coral">Factures récentes</p><h2 class="mb-4 mt-1 text-xl font-semibold text-hydro-ink">Téléchargement et envoi</h2>
        <div class="divide-y divide-hydro-ink/10 border-y border-hydro-ink/10 bg-white">
            @forelse ($recentInvoices as $invoice)
                <article class="flex flex-wrap items-center justify-between gap-4 p-4">
                    <div><p class="font-semibold">{{ $invoice->subscription->user->name }} · {{ $invoice->number }}</p><p class="mt-1 text-sm text-slate-600">{{ $invoice->period->format('m/Y') }} · {{ number_format($invoice->amount, 0, ',', ' ') }} {{ $settings->currency }}</p></div>
                    <div class="flex flex-wrap gap-2"><a class="border border-hydro-teal px-3 py-2 text-sm font-semibold text-hydro-teal hover:bg-hydro-mist" href="{{ route('admin.water.invoices.download', $invoice) }}">Télécharger PDF</a><form method="POST" action="{{ route('admin.water.invoices.send', $invoice) }}">@csrf<button class="border border-hydro-ink/20 px-3 py-2 text-sm font-semibold text-hydro-ink hover:bg-hydro-mist" type="submit">Envoyer par email</button></form></div>
                </article>
            @empty
                <p class="p-5 text-sm text-slate-600">Aucune facture créée.</p>
            @endforelse
        </div>
    </section>

    <section class="mt-10">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3"><div><p class="text-xs font-bold uppercase text-hydro-coral">Raccordement</p><h2 class="mt-1 text-xl font-semibold text-hydro-ink">Demandes récentes</h2></div><div class="flex flex-wrap gap-4"><a class="text-sm font-semibold text-hydro-teal underline underline-offset-4" href="{{ route('admin.meters.index') }}">Gérer les compteurs</a><a class="text-sm font-semibold text-hydro-teal underline underline-offset-4" href="{{ route('admin.subscriptions.index') }}">Gérer les abonnements</a><a class="text-sm font-semibold text-hydro-teal underline underline-offset-4" href="{{ route('admin.zones.index') }}">Gérer les zones</a><a class="text-sm font-semibold text-hydro-teal underline underline-offset-4" href="{{ route('admin.tariffs.index') }}">Gérer les tarifs</a><a class="text-sm font-semibold text-hydro-teal underline underline-offset-4" href="{{ route('admin.users.index') }}">Gérer les comptes</a></div></div>
        <div class="divide-y divide-hydro-ink/10 border-y border-hydro-ink/10 bg-white">
            @forelse ($connectionRequests as $connectionRequest)
                <article class="grid gap-5 p-5 lg:grid-cols-[minmax(0,1fr)_minmax(280px,0.85fr)]">
                    <div><div class="flex flex-wrap justify-between gap-2"><h3 class="font-semibold text-hydro-ink">{{ $connectionRequest->user->name }}</h3><span class="text-xs font-semibold text-slate-600">{{ str_replace('_', ' ', ucfirst($connectionRequest->status)) }}</span></div><p class="mt-1 text-sm text-slate-600">{{ $connectionRequest->service_address }}</p><p class="mt-2 text-xs text-slate-500">{{ $connectionRequest->usage_type === 'business' ? 'Activité professionnelle' : 'Habitation' }} · {{ $connectionRequest->created_at->format('d/m/Y') }}</p>@if($connectionRequest->quote_amount)<p class="mt-3 text-sm">Devis : {{ number_format($connectionRequest->quote_amount, 0, ',', ' ') }} {{ $settings->currency }}</p>@endif</div>
                    @if ($connectionRequest->status === 'approved')
                        <form class="self-center" method="POST" action="{{ route('admin.water.requests.activate', $connectionRequest) }}">@csrf<button class="bg-hydro-teal px-3 py-2 text-sm font-semibold text-white hover:bg-hydro-ink" type="submit">Créer l’abonnement et le compteur</button></form>
                    @elseif ($connectionRequest->status !== 'connected' && $connectionRequest->status !== 'rejected')
                        <form class="grid gap-3 sm:grid-cols-[1fr_1fr]" method="POST" action="{{ route('admin.water.requests.update', $connectionRequest) }}">
                            @csrf @method('PATCH')
                            <label class="text-xs font-medium text-slate-600">Étape<select class="mt-1 w-full border border-slate-300 bg-white px-2 py-2 text-sm" name="status"><option value="under_review" @selected($connectionRequest->status === 'under_review')>À l’étude</option><option value="quoted" @selected($connectionRequest->status === 'quoted')>Devis envoyé</option><option value="rejected">Refusée</option></select></label>
                            <label class="text-xs font-medium text-slate-600">Montant du devis ({{ $settings->currency }})<input class="mt-1 w-full border border-slate-300 px-2 py-2 text-sm" name="quote_amount" type="number" min="1" value="{{ $connectionRequest->quote_amount }}"></label>
                            <label class="text-xs font-medium text-slate-600 sm:col-span-2">Note pour l’abonné<textarea class="mt-1 w-full border border-slate-300 px-2 py-2 text-sm" name="admin_note" rows="2">{{ $connectionRequest->admin_note }}</textarea></label>
                            <button class="w-fit border border-hydro-teal px-3 py-2 text-sm font-semibold text-hydro-teal hover:bg-hydro-mist sm:col-span-2" type="submit">Enregistrer le suivi</button>
                        </form>
                    @else
                        <p class="self-center text-sm text-emerald-800">Raccordement terminé</p>
                    @endif
                </article>
            @empty
                <p class="p-6 text-sm text-slate-600">Aucune demande récente.</p>
            @endforelse
        </div>
    </section>

    <section class="mt-10 grid gap-8 xl:grid-cols-2">
        <div>
            <p class="text-xs font-bold uppercase text-hydro-coral">Compteurs</p><h2 class="mb-4 mt-1 text-xl font-semibold text-hydro-ink">Relevés à vérifier</h2>
            <div class="divide-y divide-hydro-ink/10 border-y border-hydro-ink/10 bg-white">
                @forelse ($readings as $reading)
                    <article class="flex flex-wrap items-center justify-between gap-4 p-4"><div><p class="font-semibold">{{ $reading->subscription->user->name }} · {{ $reading->subscription->reference }}</p><p class="mt-1 text-sm text-slate-600">{{ $reading->waterMeter?->serial_number ?? 'Compteur historique' }} · {{ $reading->previous_index }} → {{ $reading->current_index }} m³ · {{ $reading->read_at->format('d/m/Y') }}</p></div><form method="POST" action="{{ route('admin.water.readings.verify', $reading) }}">@csrf @method('PATCH')<button class="border border-hydro-teal px-3 py-2 text-sm font-semibold text-hydro-teal hover:bg-hydro-mist" type="submit">Valider le relevé</button></form></article>
                @empty
                    <p class="p-5 text-sm text-slate-600">Aucun relevé en attente.</p>
                @endforelse
            </div>
        </div>
        <div>
            <p class="text-xs font-bold uppercase text-hydro-coral">Encaissements</p><h2 class="mb-4 mt-1 text-xl font-semibold text-hydro-ink">Paiements à vérifier</h2>
            <div class="divide-y divide-hydro-ink/10 border-y border-hydro-ink/10 bg-white">
                @forelse ($payments as $payment)
                    <article class="flex flex-wrap items-center justify-between gap-4 p-4"><div><p class="font-semibold">{{ $payment->invoice->subscription->user->name }} · {{ $payment->invoice->number }}</p><p class="mt-1 text-sm text-slate-600">{{ number_format($payment->amount, 0, ',', ' ') }} {{ $settings->currency }} · {{ str_replace('_', ' ', $payment->channel) }}@if($payment->reference) · Réf. {{ $payment->reference }}@endif</p></div><div class="flex gap-2"><form method="POST" action="{{ route('admin.water.payments.update', $payment) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="confirmed"><button class="border border-emerald-700 px-3 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50" type="submit">Confirmer</button></form><form method="POST" action="{{ route('admin.water.payments.update', $payment) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="rejected"><button class="border border-red-700 px-3 py-2 text-sm font-semibold text-red-800 hover:bg-red-50" type="submit">Refuser</button></form></div></article>
                @empty
                    <p class="p-5 text-sm text-slate-600">Aucun paiement en attente.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="mt-10">
        <p class="text-xs font-bold uppercase text-hydro-coral">Service client</p><h2 class="mb-4 mt-1 text-xl font-semibold text-hydro-ink">Tickets ouverts</h2>
        <div class="divide-y divide-hydro-ink/10 border-y border-hydro-ink/10 bg-white">
            @forelse ($tickets as $ticket)
                <article class="grid gap-4 p-5 lg:grid-cols-[minmax(0,1fr)_220px]">
                    <div><div class="flex flex-wrap justify-between gap-2"><h3 class="font-semibold">{{ $ticket->subject }} · {{ $ticket->user->name }}</h3><span class="text-xs text-slate-500">{{ $ticket->status === 'open' ? 'Ouvert' : 'En cours' }}</span></div><p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $ticket->description }}</p><p class="mt-2 text-xs text-slate-500">{{ $ticket->created_at->format('d/m/Y') }}</p></div>
                    <form class="flex items-center gap-2" method="POST" action="{{ route('admin.water.tickets.update', $ticket) }}">@csrf @method('PATCH')<select class="min-w-0 flex-1 border border-slate-300 bg-white px-2 py-2 text-sm" name="status"><option value="in_progress" @selected($ticket->status === 'in_progress')>En cours</option><option value="resolved">Résolu</option></select><button class="border border-hydro-teal px-3 py-2 text-sm font-semibold text-hydro-teal hover:bg-hydro-mist" type="submit">Mettre à jour</button></form>
                </article>
            @empty
                <p class="p-5 text-sm text-slate-600">Aucun ticket ouvert.</p>
            @endforelse
        </div>
    </section>

    <section class="mt-10 border-t border-hydro-ink/15 pt-8">
        <p class="text-xs font-bold uppercase text-hydro-coral">Paramètres</p><h2 class="mb-5 mt-1 text-xl font-semibold text-hydro-ink">Entreprise et tarifs de démonstration</h2>
        <form class="grid gap-4 border-y border-hydro-ink/10 bg-white p-5 sm:grid-cols-2 xl:grid-cols-3" method="POST" action="{{ route('admin.water.settings.update') }}">
            @csrf @method('PUT')
            <label class="text-sm font-medium">Nom de l’entreprise<input class="mt-1 w-full border border-slate-300 px-3 py-2.5" name="company_name" value="{{ $settings->company_name }}" required></label>
            <label class="text-sm font-medium">Devise<input class="mt-1 w-full border border-slate-300 px-3 py-2.5" name="currency" value="{{ $settings->currency }}" required></label>
            <label class="text-sm font-medium">Prix par m³<input class="mt-1 w-full border border-slate-300 px-3 py-2.5" name="price_per_m3" type="number" min="0" value="{{ $settings->price_per_m3 }}" required></label>
            <label class="text-sm font-medium">Forfait mensuel<input class="mt-1 w-full border border-slate-300 px-3 py-2.5" name="monthly_fee" type="number" min="0" value="{{ $settings->monthly_fee }}" required></label>
            <label class="text-sm font-medium">Frais indicatifs de raccordement<input class="mt-1 w-full border border-slate-300 px-3 py-2.5" name="connection_fee" type="number" min="0" value="{{ $settings->connection_fee }}" required></label>
            <label class="text-sm font-medium">Dépôt compteur<input class="mt-1 w-full border border-slate-300 px-3 py-2.5" name="meter_deposit" type="number" min="0" value="{{ $settings->meter_deposit }}" required></label>
            <button class="w-fit bg-hydro-ink px-4 py-3 text-sm font-semibold text-white hover:bg-hydro-teal sm:col-span-2" type="submit">Enregistrer les paramètres</button>
        </form>
        <p class="mt-3 text-xs text-slate-500">Valeurs initiales de démonstration. Aucun tarif CAMWATER n’est appliqué.</p>
    </section>
@endsection