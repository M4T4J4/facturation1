@extends('layouts.app')

@section('title', 'Espace agent')

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-xs font-bold uppercase text-hydro-coral">Espace agent de terrain</p><h1 class="mt-2 text-3xl font-semibold text-hydro-ink">Bonjour, {{ auth()->user()->name }}</h1><p class="mt-2 text-sm text-slate-600">Saisissez les index des compteurs actifs.</p></div>
        <a class="bg-hydro-teal px-4 py-3 text-sm font-semibold text-white hover:bg-hydro-ink" href="{{ route('agent.readings.index') }}">Saisir un relevé</a>
    </div>
    <section class="grid gap-4 sm:grid-cols-2">
        <div class="border border-hydro-ink/10 bg-white p-6"><p class="text-sm text-slate-600">Compteurs actifs</p><p class="mt-3 text-4xl font-semibold tabular-nums">{{ $activeSubscriptionCount }}</p></div>
        <div class="bg-hydro-ink p-6 text-white"><p class="text-xs font-semibold uppercase text-hydro-lime">Session</p><p class="mt-3 text-sm">{{ auth()->user()->email }}</p><a class="mt-5 inline-block text-sm font-semibold text-hydro-lime underline underline-offset-4" href="{{ route('agent.readings.index') }}">Ouvrir la saisie de relevé</a></div>
    </section>
@endsection
