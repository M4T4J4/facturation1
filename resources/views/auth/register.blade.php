<div>
    <!-- Act only according to that maxim whereby you can, at the same time, will that it should become a universal law. - Immanuel Kant -->
@extends('layouts.guest')

@section('title', 'Créer un compte')

@section('content')
    <div class="mb-7">
        <p class="text-xs font-bold uppercase tracking-normal text-hydro-coral">Espace abonné</p>
        <h2 class="mt-2 text-2xl font-semibold text-hydro-ink">Créer un compte</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">Renseignez vos coordonnées pour accéder à votre espace.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div>
            <label class="mb-2 block text-sm font-medium text-hydro-ink" for="name">Nom complet</label>
            <input class="w-full rounded-sm border border-slate-300 px-3 py-3 text-sm outline-none focus:border-hydro-teal focus:ring-2 focus:ring-hydro-teal/20" id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required autofocus>
            @error('name') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-2 block text-sm font-medium text-hydro-ink" for="email">Adresse email</label>
            <input class="w-full rounded-sm border border-slate-300 px-3 py-3 text-sm outline-none focus:border-hydro-teal focus:ring-2 focus:ring-hydro-teal/20" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
            @error('email') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-2 block text-sm font-medium text-hydro-ink" for="phone">Téléphone <span class="font-normal text-slate-500">(facultatif)</span></label>
            <input class="w-full rounded-sm border border-slate-300 px-3 py-3 text-sm outline-none focus:border-hydro-teal focus:ring-2 focus:ring-hydro-teal/20" id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" placeholder="+237 6 00 00 00 00">
            @error('phone') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-2 block text-sm font-medium text-hydro-ink" for="password">Mot de passe</label>
                <input class="w-full rounded-sm border border-slate-300 px-3 py-3 text-sm outline-none focus:border-hydro-teal focus:ring-2 focus:ring-hydro-teal/20" id="password" name="password" type="password" autocomplete="new-password" required>
                @error('password') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-hydro-ink" for="password_confirmation">Confirmer</label>
                <input class="w-full rounded-sm border border-slate-300 px-3 py-3 text-sm outline-none focus:border-hydro-teal focus:ring-2 focus:ring-hydro-teal/20" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
            </div>
        </div>
        <button class="mt-2 w-full rounded-sm bg-hydro-teal px-4 py-3 text-sm font-semibold text-white transition hover:bg-hydro-ink focus:outline-none focus:ring-2 focus:ring-hydro-teal focus:ring-offset-2" type="submit">Créer mon compte</button>
    </form>

    <p class="mt-6 border-t border-slate-200 pt-5 text-sm text-slate-600">
        Déjà inscrit ? <a class="font-semibold text-hydro-teal underline underline-offset-4 hover:text-hydro-ink" href="{{ route('login') }}">Se connecter</a>
    </p>
@endsection
