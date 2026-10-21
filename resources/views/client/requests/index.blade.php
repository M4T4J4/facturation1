@extends('layouts.app')

@section('title', 'Mes demandes')

@section('content')
    <div class="mb-8">
        <p class="text-xs font-bold uppercase text-hydro-coral">Espace abonné</p>
        <h1 class="mt-2 text-3xl font-semibold text-hydro-ink">Raccordement</h1>
        <p class="mt-2 text-sm text-slate-600">Déposez une demande et suivez les réponses de l’entreprise.</p>
    </div>

    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)]">
        <section class="h-fit border border-hydro-ink/10 bg-white p-6 sm:p-8">
            <h2 class="text-lg font-semibold text-hydro-ink">Nouvelle demande</h2>
            <form class="mt-6 grid gap-5" method="POST" action="{{ route('client.requests.store') }}">
                @csrf
                <div>
                    <label class="mb-2 block text-sm font-medium" for="service_address">Adresse à raccorder</label>
                    <textarea class="w-full border border-slate-300 px-3 py-3 text-sm focus:border-hydro-teal focus:outline-none" id="service_address" name="service_address" rows="3" required>{{ old('service_address') }}</textarea>
                    @error('service_address')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium" for="usage_type">Type d’usage</label>
                    <select class="w-full border border-slate-300 bg-white px-3 py-3 text-sm focus:border-hydro-teal focus:outline-none" id="usage_type" name="usage_type" required>
                        <option value="residential" @selected(old('usage_type') === 'residential')>Habitation</option>
                        <option value="business" @selected(old('usage_type') === 'business')>Activité professionnelle</option>
                    </select>
                    @error('usage_type')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div class="border-l-4 border-hydro-lime bg-hydro-mist px-4 py-3 text-sm leading-6">
                    Frais indicatifs de raccordement : {{ number_format($settings->connection_fee, 0, ',', ' ') }} {{ $settings->currency }}. Le montant définitif est confirmé par l’équipe.
                </div>
                <button class="w-fit bg-hydro-teal px-4 py-3 text-sm font-semibold text-white hover:bg-hydro-ink" type="submit">Envoyer la demande</button>
            </form>
        </section>

        <section>
            <div class="mb-4 flex items-end justify-between gap-3">
                <div><p class="text-xs font-bold uppercase text-hydro-coral">Suivi</p><h2 class="mt-1 text-lg font-semibold text-hydro-ink">Mes demandes</h2></div>
                <span class="text-sm text-slate-500">{{ $connectionRequests->total() }} au total</span>
            </div>
            <div class="divide-y divide-hydro-ink/10 border-y border-hydro-ink/10 bg-white">
                @forelse ($connectionRequests as $connectionRequest)
                    <article class="p-5 sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div><p class="font-semibold text-hydro-ink">{{ $connectionRequest->usage_type === 'business' ? 'Activité professionnelle' : 'Habitation' }}</p><p class="mt-1 text-sm text-slate-600">{{ $connectionRequest->service_address }}</p></div>
                            <span class="bg-hydro-mist px-2.5 py-1 text-xs font-semibold text-hydro-ink">{{ match($connectionRequest->status) {'received' => 'Reçue', 'under_review' => 'À l’étude', 'quoted' => 'Devis envoyé', 'approved' => 'Devis accepté', 'connected' => 'Raccordée', 'rejected' => 'Refusée', default => $connectionRequest->status} }}</span>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">Déposée le {{ $connectionRequest->created_at->format('d/m/Y') }}</p>
                        @if ($connectionRequest->quote_amount)
                            <p class="mt-4 text-sm">Devis : <strong>{{ number_format($connectionRequest->quote_amount, 0, ',', ' ') }} {{ $settings->currency }}</strong></p>
                        @endif
                        @if ($connectionRequest->admin_note)
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $connectionRequest->admin_note }}</p>
                        @endif
                        @if ($connectionRequest->status === 'quoted')
                            <form class="mt-4" method="POST" action="{{ route('client.requests.accept', $connectionRequest) }}">
                                @csrf
                                <button class="border border-hydro-teal px-3 py-2 text-sm font-semibold text-hydro-teal hover:bg-hydro-mist" type="submit">Accepter le devis</button>
                            </form>
                        @endif
                    </article>
                @empty
                    <p class="p-6 text-sm text-slate-600">Aucune demande pour l’instant.</p>
                @endforelse
            </div>
            <div class="mt-4">{{ $connectionRequests->links() }}</div>
        </section>
    </div>
@endsection