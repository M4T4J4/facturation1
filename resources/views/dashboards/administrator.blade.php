@extends('layouts.app')

@section('title', 'Administration')

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-normal text-hydro-coral">Espace gestionnaire</p>
            <h1 class="mt-2 text-3xl font-semibold text-hydro-ink">Bonjour, {{ auth()->user()->name }}</h1>
            <p class="mt-2 text-sm text-slate-600">Demandes, abonnements, relevés et paiements.</p>
        </div>
        <a class="bg-hydro-teal px-4 py-3 text-sm font-semibold text-white hover:bg-hydro-ink" href="{{ route('admin.water.dashboard') }}">Ouvrir la gestion d’eau</a>
    </div>

    <section aria-label="Comptes par rôle" class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-sm border border-hydro-ink/10 bg-white p-6">
            <p class="text-sm text-slate-600">Abonnés</p>
            <p class="mt-3 text-4xl font-semibold tabular-nums text-hydro-ink">{{ number_format($customerCount, 0, ',', ' ') }}</p>
        </div>
        <div class="rounded-sm border border-hydro-ink/10 bg-white p-6">
            <p class="text-sm text-slate-600">Agents de terrain</p>
            <p class="mt-3 text-4xl font-semibold tabular-nums text-hydro-ink">{{ number_format($fieldAgentCount, 0, ',', ' ') }}</p>
        </div>
    </section>

    <section class="mt-8 grid gap-4 sm:grid-cols-2">
        <a class="border border-hydro-ink/10 bg-white p-6 hover:border-hydro-teal" href="{{ route('admin.water.dashboard') }}"><h2 class="font-semibold text-hydro-ink">Opérations eau</h2><p class="mt-2 text-sm text-slate-600">Traiter les demandes, activer les compteurs, vérifier les relevés et paiements.</p></a>
        <a class="border border-hydro-ink/10 bg-white p-6 hover:border-hydro-teal" href="{{ route('admin.users.index') }}"><h2 class="font-semibold text-hydro-ink">Comptes et rôles</h2><p class="mt-2 text-sm text-slate-600">Gérer les abonnés, agents de terrain et administrateurs.</p></a>
    </section>

    <section class="mt-8 border-l-4 border-hydro-lime bg-white px-5 py-4">
        <h2 class="font-semibold text-hydro-ink">Compte gestionnaire</h2>
        <p class="mt-1 text-sm text-slate-600">{{ auth()->user()->email }}</p>
    </section>
@endsection
