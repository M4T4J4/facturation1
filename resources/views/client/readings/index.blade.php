@extends('layouts.app')

@section('title', 'Mes relevés')

@section('content')
    <div class="mb-8">
        <p class="text-xs font-bold uppercase text-hydro-coral">Espace abonné</p>
        <h1 class="mt-2 text-3xl font-semibold text-hydro-ink">Relevé du compteur</h1>
        <p class="mt-2 text-sm text-slate-600">Envoyez l’index affiché sur votre compteur. Il sera vérifié avant facturation.</p>
    </div>

    <section class="grid gap-8 lg:grid-cols-[minmax(280px,0.75fr)_minmax(0,1.25fr)]">
        <form class="h-fit border border-hydro-ink/10 bg-white p-6 sm:p-8" method="POST" action="{{ route('client.readings.store') }}">
            @csrf
            <h2 class="text-lg font-semibold text-hydro-ink">Transmettre un index</h2>
            @if ($subscriptions->isNotEmpty())
                <p class="mt-2 text-sm text-slate-600">Compteur : {{ $subscriptions->first()->waterMeter->serial_number }}</p>
                <label class="mb-2 mt-5 block text-sm font-medium" for="current_index">Index actuel (m³)</label>
                <input class="w-full border border-slate-300 px-3 py-3 text-sm" id="current_index" name="current_index" type="number" min="0" step="1" value="{{ old('current_index') }}" required>
                @error('current_index')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                <label class="mb-2 mt-4 block text-sm font-medium" for="read_at">Date du relevé</label>
                <input class="w-full border border-slate-300 px-3 py-3 text-sm" id="read_at" name="read_at" type="date" max="{{ now()->toDateString() }}" value="{{ old('read_at', now()->toDateString()) }}" required>
                @error('read_at')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                <label class="mb-2 mt-4 block text-sm font-medium" for="notes">Note (facultatif)</label>
                <textarea class="w-full border border-slate-300 px-3 py-3 text-sm" id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
                <button class="mt-5 bg-hydro-teal px-4 py-3 text-sm font-semibold text-white hover:bg-hydro-ink" type="submit">Envoyer pour vérification</button>
            @else
                <p class="mt-4 text-sm leading-6 text-slate-600">Un abonnement actif est nécessaire pour transmettre un relevé.</p>
                <a class="mt-5 inline-block text-sm font-semibold text-hydro-teal underline underline-offset-4" href="{{ route('client.requests.index') }}">Voir mes demandes de raccordement</a>
            @endif
        </form>

        <div>
            <h2 class="mb-4 text-lg font-semibold text-hydro-ink">Historique des relevés</h2>
            @forelse ($subscriptions as $subscription)
                <div class="mb-6 border border-hydro-ink/10 bg-white">
                    <div class="border-b border-hydro-ink/10 px-5 py-4"><p class="font-semibold">{{ $subscription->reference }}</p><p class="mt-1 text-sm text-slate-600">{{ $subscription->service_address }}</p></div>
                    <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-hydro-mist text-xs uppercase text-slate-600"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Compteur</th><th class="px-4 py-3">Index précédent</th><th class="px-4 py-3">Index transmis</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3">Facture</th></tr></thead><tbody>
                        @forelse ($subscription->meterReadings->sortByDesc('read_at') as $reading)
                            <tr class="border-t border-hydro-ink/10"><td class="px-4 py-3">{{ $reading->read_at->format('d/m/Y') }}</td><td class="px-4 py-3">{{ $reading->waterMeter?->serial_number ?? 'Compteur historique' }}</td><td class="px-4 py-3">{{ $reading->previous_index }} m³</td><td class="px-4 py-3">{{ $reading->current_index }} m³</td><td class="px-4 py-3">{{ $reading->status === 'verified' ? 'Vérifié' : 'À vérifier' }}</td><td class="px-4 py-3">
                                @if ($reading->invoice)
                                    <a class="font-semibold text-hydro-teal underline underline-offset-4" href="{{ route('client.invoices.download', $reading->invoice) }}">Télécharger PDF</a>
                                @elseif ($reading->status === 'verified')
                                    <form method="POST" action="{{ route('client.readings.invoice', $reading) }}">@csrf<button class="text-left font-semibold text-hydro-teal underline underline-offset-4" type="submit">Générer et envoyer</button></form>
                                @else
                                    <span class="text-xs text-slate-500">Après vérification</span>
                                @endif
                            </td></tr>
                        @empty
                            <tr><td class="px-4 py-4 text-slate-600" colspan="6">Aucun relevé enregistré.</td></tr>
                        @endforelse
                    </tbody></table></div>
                </div>
            @empty
                <div class="border-y border-hydro-ink/10 bg-white px-5 py-6 text-sm text-slate-600">Aucun compteur actif.</div>
            @endforelse
        </div>
    </section>
@endsection