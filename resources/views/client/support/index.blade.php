@extends('layouts.app')

@section('title', 'Assistance')

@section('content')
    <div class="mb-8">
        <p class="text-xs font-bold uppercase text-hydro-coral">Espace abonné</p>
        <h1 class="mt-2 text-3xl font-semibold text-hydro-ink">Assistance</h1>
        <p class="mt-2 text-sm text-slate-600">Signalez une fuite, une panne ou une question sur votre service.</p>
    </div>

    <div class="grid gap-8 lg:grid-cols-[minmax(280px,0.75fr)_minmax(0,1.25fr)]">
        <form class="h-fit border border-hydro-ink/10 bg-white p-6 sm:p-8" method="POST" action="{{ route('client.support.store') }}">
            @csrf
            <h2 class="text-lg font-semibold text-hydro-ink">Nouveau message</h2>
            <label class="mb-2 mt-5 block text-sm font-medium" for="subject">Objet</label>
            <select class="w-full border border-slate-300 bg-white px-3 py-3 text-sm" id="subject" name="subject" required>
                <option value="Fuite d’eau">Fuite d’eau</option><option value="Compteur">Compteur</option><option value="Facture ou paiement">Facture ou paiement</option><option value="Qualité ou pression">Qualité ou pression</option><option value="Autre demande">Autre demande</option>
            </select>
            <label class="mb-2 mt-4 block text-sm font-medium" for="description">Description et adresse concernée</label>
            <textarea class="w-full border border-slate-300 px-3 py-3 text-sm" id="description" name="description" rows="5" maxlength="3000" required>{{ old('description') }}</textarea>
            @error('description')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            <button class="mt-5 bg-hydro-teal px-4 py-3 text-sm font-semibold text-white hover:bg-hydro-ink" type="submit">Envoyer à l’équipe</button>
        </form>

        <section>
            <h2 class="mb-4 text-lg font-semibold text-hydro-ink">Mes demandes</h2>
            <div class="divide-y divide-hydro-ink/10 border-y border-hydro-ink/10 bg-white">
                @forelse ($tickets as $ticket)
                    <article class="p-5"><div class="flex flex-wrap justify-between gap-2"><h3 class="font-semibold">{{ $ticket->subject }}</h3><span class="text-xs text-slate-500">{{ $ticket->status === 'open' ? 'Ouverte' : ucfirst($ticket->status) }}</span></div><p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $ticket->description }}</p><p class="mt-3 text-xs text-slate-500">Envoyée le {{ $ticket->created_at->format('d/m/Y') }}</p></article>
                @empty
                    <p class="p-6 text-sm text-slate-600">Aucune demande d’assistance.</p>
                @endforelse
            </div>
            <div class="mt-4">{{ $tickets->links() }}</div>
        </section>
    </div>
@endsection