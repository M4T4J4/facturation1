@extends('layouts.app')

@section('title', 'Saisie des relevés')

@section('content')
    <div class="mb-8">
        <p class="text-xs font-bold uppercase text-hydro-coral">Espace agent</p>
        <h1 class="mt-2 text-3xl font-semibold text-hydro-ink">Saisie des relevés</h1>
        <p class="mt-2 text-sm text-slate-600">Enregistrez l’index observé sur un compteur actif.</p>
    </div>

    <section class="grid gap-8 lg:grid-cols-[minmax(280px,0.7fr)_minmax(0,1.3fr)]">
        <form class="h-fit border border-hydro-ink/10 bg-white p-6 sm:p-8" method="POST" action="{{ route('agent.readings.store') }}">
            @csrf
            <h2 class="text-lg font-semibold text-hydro-ink">Nouveau relevé</h2>
            <label class="mb-2 mt-5 block text-sm font-medium" for="reference">Abonnement</label>
            <select class="w-full border border-slate-300 bg-white px-3 py-3 text-sm" id="reference" name="reference" required>
                @foreach ($subscriptions as $subscription)<option value="{{ $subscription->reference }}">{{ $subscription->reference }} · {{ $subscription->waterMeter->serial_number }} · {{ $subscription->user->name }}</option>@endforeach
            </select>
            @error('reference')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            <label class="mb-2 mt-4 block text-sm font-medium" for="current_index">Index actuel (m³)</label>
            <input class="w-full border border-slate-300 px-3 py-3 text-sm" id="current_index" name="current_index" type="number" min="0" required>
            @error('current_index')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            <label class="mb-2 mt-4 block text-sm font-medium" for="read_at">Date du relevé</label>
            <input class="w-full border border-slate-300 px-3 py-3 text-sm" id="read_at" name="read_at" type="date" max="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" required>
            <label class="mb-2 mt-4 block text-sm font-medium" for="notes">Note (facultatif)</label>
            <textarea class="w-full border border-slate-300 px-3 py-3 text-sm" id="notes" name="notes" rows="2"></textarea>
            <button class="mt-5 bg-hydro-teal px-4 py-3 text-sm font-semibold text-white hover:bg-hydro-ink" type="submit" @disabled($subscriptions->isEmpty())>Enregistrer le relevé</button>
        </form>

        <div>
            <h2 class="mb-4 text-lg font-semibold text-hydro-ink">Compteurs actifs</h2>
            <div class="overflow-x-auto border-y border-hydro-ink/10 bg-white"><table class="w-full text-left text-sm"><thead class="bg-hydro-mist text-xs uppercase text-slate-600"><tr><th class="px-4 py-3">Compteur</th><th class="px-4 py-3">Référence</th><th class="px-4 py-3">Abonné</th><th class="px-4 py-3">Adresse</th><th class="px-4 py-3">Dernier index</th></tr></thead><tbody>
                @forelse ($subscriptions as $subscription)
                    <tr class="border-t border-hydro-ink/10"><td class="px-4 py-3 font-medium">{{ $subscription->waterMeter->serial_number }}</td><td class="px-4 py-3">{{ $subscription->reference }}</td><td class="px-4 py-3">{{ $subscription->user->name }}</td><td class="px-4 py-3">{{ $subscription->service_address }}</td><td class="px-4 py-3">{{ $subscription->waterMeter->readings->first()?->current_index ?? '—' }}</td></tr>
                @empty
                    <tr><td class="px-4 py-4 text-slate-600" colspan="5">Aucun compteur actif.</td></tr>
                @endforelse
            </tbody></table></div>
            <div class="mt-4">{{ $subscriptions->links() }}</div>
        </div>
    </section>
@endsection