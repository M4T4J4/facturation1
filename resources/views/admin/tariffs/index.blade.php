@extends('layouts.app')

@section('title', 'Tarifs')

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase text-hydro-coral">Facturation</p>
            <h1 class="mt-2 text-3xl font-semibold text-hydro-ink">Tarification par tranches</h1>
            <p class="mt-2 text-sm text-slate-600">Créez des tarifs datés. Une fois enregistrés, ils restent dans l’historique et ne sont pas modifiables.</p>
        </div>
        <a class="text-sm font-semibold text-hydro-teal underline underline-offset-4" href="{{ route('admin.water.dashboard') }}">Retour à la gestion de l’eau</a>
    </div>

    @if ($errors->any())
        <div class="mb-6 border-l-4 border-red-700 bg-white px-4 py-3 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="mb-10 border border-hydro-ink/10 bg-white p-5 sm:p-7">
        <h2 class="text-xl font-semibold text-hydro-ink">Créer un tarif</h2>
        <form class="mt-5 grid gap-4 sm:grid-cols-2" method="POST" action="{{ route('admin.tariffs.store') }}">
            @csrf
            <label class="text-sm font-medium">Nom du tarif
                <input class="mt-1 w-full border border-slate-300 px-3 py-2.5" name="name" value="{{ old('name') }}" maxlength="120" required>
            </label>
            <label class="text-sm font-medium">Type de client
                <select class="mt-1 w-full border border-slate-300 bg-white px-3 py-2.5" name="usage_type" required>
                    <option value="residential" @selected(old('usage_type') === 'residential')>Particulier / habitation</option>
                    <option value="business" @selected(old('usage_type') === 'business')>Entreprise / activité professionnelle</option>
                </select>
            </label>
            <label class="text-sm font-medium">Applicable à partir du
                <input class="mt-1 w-full border border-slate-300 px-3 py-2.5" name="effective_from" type="date" value="{{ old('effective_from') }}" required>
            </label>
            <label class="text-sm font-medium">Applicable jusqu’au (facultatif)
                <input class="mt-1 w-full border border-slate-300 px-3 py-2.5" name="effective_until" type="date" value="{{ old('effective_until') }}">
            </label>
            <label class="text-sm font-medium">Forfait fixe mensuel ({{ $currency }})
                <input class="mt-1 w-full border border-slate-300 px-3 py-2.5" name="fixed_fee" type="number" min="0" value="{{ old('fixed_fee') }}" required>
            </label>
            <label class="text-sm font-medium">Taxe (%)
                <input class="mt-1 w-full border border-slate-300 px-3 py-2.5" name="tax_percent" type="number" min="0" max="100" value="{{ old('tax_percent') }}" required>
            </label>

            <div class="sm:col-span-2">
                <h3 class="font-semibold text-hydro-ink">Tranches de consommation (m³)</h3>
                <p class="mt-1 text-xs text-slate-500">Les bornes sont inclusives. Configurez les deux premières tranches et laissez la dernière sans limite.</p>
                <div class="mt-3 grid gap-3">
                    @foreach ([
                        ['from' => 1, 'to' => 10],
                        ['from' => 11, 'to' => 30],
                        ['from' => 31, 'to' => null],
                    ] as $index => $defaults)
                        <div class="grid gap-3 sm:grid-cols-3">
                            <label class="text-xs font-medium text-slate-600">De
                                <input class="mt-1 w-full border border-slate-300 px-3 py-2.5" name="tiers[{{ $index }}][consumption_from]" type="number" min="1" value="{{ old("tiers.$index.consumption_from", $defaults['from']) }}" required>
                            </label>
                            <label class="text-xs font-medium text-slate-600">À (vide = sans limite)
                                <input class="mt-1 w-full border border-slate-300 px-3 py-2.5" name="tiers[{{ $index }}][consumption_to]" type="number" min="1" value="{{ old("tiers.$index.consumption_to", $defaults['to']) }}">
                            </label>
                            <label class="text-xs font-medium text-slate-600">Prix par m³ ({{ $currency }})
                                <input class="mt-1 w-full border border-slate-300 px-3 py-2.5" name="tiers[{{ $index }}][unit_price]" type="number" min="0" value="{{ old("tiers.$index.unit_price") }}" required>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
            <button class="w-fit bg-hydro-teal px-4 py-3 text-sm font-semibold text-white hover:bg-hydro-ink sm:col-span-2" type="submit">Enregistrer ce tarif</button>
        </form>
    </section>

    <section>
        <h2 class="mb-4 text-xl font-semibold text-hydro-ink">Historique des tarifs</h2>
        <div class="divide-y divide-hydro-ink/10 border-y border-hydro-ink/10 bg-white">
            @forelse ($tariffs as $tariff)
                <article class="p-5 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-hydro-ink">{{ $tariff->name }}</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ $tariff->usage_type === 'business' ? 'Entreprise' : 'Particulier' }} · Du {{ $tariff->effective_from->format('d/m/Y') }} @if ($tariff->effective_until) au {{ $tariff->effective_until->format('d/m/Y') }} @else · En cours @endif</p>
                        </div>
                            <p class="text-sm text-slate-600">Forfait {{ number_format($tariff->fixed_fee, 0, ',', ' ') }} {{ $currency }} · Taxe {{ $tariff->tax_percent }} %</p>
                    </div>
                    <ul class="mt-3 grid gap-2 text-sm text-slate-700 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($tariff->tiers as $tier)
                            <li class="border border-hydro-ink/10 px-3 py-2">{{ $tier->consumption_from }}–{{ $tier->consumption_to ?? '∞' }} m³ : {{ number_format($tier->unit_price, 0, ',', ' ') }} {{ $currency }}/m³</li>
                        @endforeach
                    </ul>
                </article>
            @empty
                <p class="p-6 text-sm text-slate-600">Aucun tarif progressif n’est défini. Les abonnements continuent d’utiliser leur tarif fixe actuel jusqu’à la création d’un tarif applicable.</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $tariffs->links() }}</div>
    </section>
@endsection
