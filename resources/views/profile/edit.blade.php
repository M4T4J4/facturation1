<div>
    <!-- Be present above all else. - Naval Ravikant -->
@extends('layouts.app')

@section('title', 'Mon profil')

@section('content')
    <div class="mb-8">
        <p class="text-xs font-bold uppercase tracking-normal text-hydro-coral">Compte utilisateur</p>
        <h1 class="mt-2 text-3xl font-semibold text-hydro-ink">Mon profil</h1>
        <p class="mt-2 text-sm text-slate-600">Gérez vos coordonnées et votre mot de passe.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-sm border border-hydro-ink/10 bg-white p-6 sm:p-8">
            <h2 class="text-lg font-semibold text-hydro-ink">Informations personnelles</h2>
            <form class="mt-6 space-y-5" method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PUT')
                <div>
                    <label class="mb-2 block text-sm font-medium" for="name">Nom complet</label>
                    <input class="w-full rounded-sm border border-slate-300 px-3 py-3 text-sm outline-none focus:border-hydro-teal focus:ring-2 focus:ring-hydro-teal/20" id="name" name="name" type="text" value="{{ old('name', $user->name) }}" autocomplete="name" required>
                    @error('name') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium" for="email">Adresse email</label>
                    <input class="w-full rounded-sm border border-slate-300 px-3 py-3 text-sm outline-none focus:border-hydro-teal focus:ring-2 focus:ring-hydro-teal/20" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" autocomplete="email" required>
                    @error('email') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium" for="phone">Téléphone</label>
                    <input class="w-full rounded-sm border border-slate-300 px-3 py-3 text-sm outline-none focus:border-hydro-teal focus:ring-2 focus:ring-hydro-teal/20" id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" autocomplete="tel">
                    @error('phone') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <button class="rounded-sm bg-hydro-teal px-4 py-3 text-sm font-semibold text-white hover:bg-hydro-ink" type="submit">Enregistrer</button>
            </form>
        </section>

        <section class="rounded-sm border border-hydro-ink/10 bg-white p-6 sm:p-8">
            <h2 class="text-lg font-semibold text-hydro-ink">Changer le mot de passe</h2>
            <form class="mt-6 space-y-5" method="POST" action="{{ route('profile.password.update') }}">
                @csrf
                @method('PUT')
                <div>
                    <label class="mb-2 block text-sm font-medium" for="current_password">Mot de passe actuel</label>
                    <input class="w-full rounded-sm border border-slate-300 px-3 py-3 text-sm outline-none focus:border-hydro-teal focus:ring-2 focus:ring-hydro-teal/20" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                    @error('current_password') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium" for="new_password">Nouveau mot de passe</label>
                    <input class="w-full rounded-sm border border-slate-300 px-3 py-3 text-sm outline-none focus:border-hydro-teal focus:ring-2 focus:ring-hydro-teal/20" id="new_password" name="password" type="password" autocomplete="new-password" required>
                    @error('password') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium" for="password_confirmation">Confirmer le nouveau mot de passe</label>
                    <input class="w-full rounded-sm border border-slate-300 px-3 py-3 text-sm outline-none focus:border-hydro-teal focus:ring-2 focus:ring-hydro-teal/20" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                </div>
                <button class="rounded-sm border border-hydro-ink/15 px-4 py-3 text-sm font-semibold text-hydro-ink hover:border-hydro-teal hover:text-hydro-teal" type="submit">Mettre à jour le mot de passe</button>
            </form>
        </section>
    </div>
@endsection
