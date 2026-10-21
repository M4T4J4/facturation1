@extends('layouts.guest')

@section('title', 'Connexion')

@section('content')
    <div class="mb-8">
        <p class="text-xs font-bold uppercase tracking-normal text-hydro-coral">Accès sécurisé</p>
        <h2 class="mt-2 text-2xl font-semibold text-hydro-ink">Connexion</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">Utilisez votre adresse email ou votre numéro de téléphone.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf
        <div>
            <label class="mb-2 block text-sm font-medium text-hydro-ink" for="identifier">Email ou téléphone</label>
            <input class="w-full rounded-sm border border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 outline-none transition focus:border-hydro-teal focus:ring-2 focus:ring-hydro-teal/20" id="identifier" name="identifier" type="text" value="{{ old('identifier') }}" autocomplete="username" required autofocus aria-invalid="{{ $errors->has('identifier') ? 'true' : 'false' }}">
            @error('identifier')
                <p class="mt-2 text-sm text-red-700" id="identifier-error">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="mb-2 block text-sm font-medium text-hydro-ink" for="password">Mot de passe</label>
            <input class="w-full rounded-sm border border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 outline-none transition focus:border-hydro-teal focus:ring-2 focus:ring-hydro-teal/20" id="password" name="password" type="password" autocomplete="current-password" required>
            @error('password')
                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>
        <label class="flex items-center gap-3 text-sm text-slate-600">
            <input class="size-4 rounded-sm border-slate-300 accent-hydro-teal focus:ring-hydro-teal" name="remember" type="checkbox" value="1" @checked(old('remember'))>
            Rester connecté
        </label>
        <button class="w-full rounded-sm bg-hydro-teal px-4 py-3 text-sm font-semibold text-white transition hover:bg-hydro-ink focus:outline-none focus:ring-2 focus:ring-hydro-teal focus:ring-offset-2" type="submit">Se connecter</button>
    </form>

    <p class="mt-7 border-t border-slate-200 pt-5 text-sm text-slate-600">
        Pas encore de compte ? <a class="font-semibold text-hydro-teal underline underline-offset-4 hover:text-hydro-ink" href="{{ route('register') }}">Créer un compte abonné</a>
    </p>
@endsection
