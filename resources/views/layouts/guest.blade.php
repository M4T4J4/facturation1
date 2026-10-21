<div>
    <!-- Well begun is half done. - Aristotle -->
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title') | {{ config('app.name', 'HydroGestion') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f2f6f2] font-sans text-hydro-ink antialiased">
        <div class="min-h-screen">
            <header class="border-b border-hydro-ink/10 bg-white/90">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-8">
                    <a class="flex items-center gap-3 font-semibold tracking-normal text-hydro-ink" href="{{ route('home') }}">
                        <span class="grid size-10 place-items-center rounded-sm bg-hydro-teal text-lg font-bold text-white">H</span>
                        <span>{{ config('app.name', 'HydroGestion') }}</span>
                    </a>
                    <a class="text-sm font-semibold text-hydro-teal underline underline-offset-4" href="{{ route('guide') }}">Guide d’utilisation</a>
                </div>
            </header>
            <main class="mx-auto grid min-h-[calc(100vh-73px)] max-w-7xl items-center gap-10 px-5 py-10 sm:px-8 lg:grid-cols-[minmax(0,1fr)_minmax(360px,440px)] lg:gap-20">
                <section class="max-w-xl">
                    <p class="mb-5 flex items-center gap-3 text-xs font-bold uppercase tracking-normal text-hydro-teal">
                        <span class="h-px w-8 bg-hydro-coral"></span>
                        Service public · Cameroun
                    </p>
                    <h1 class="max-w-lg text-4xl font-semibold leading-tight text-hydro-ink sm:text-5xl">L’eau, au cœur de nos services.</h1>
                    <p class="mt-5 max-w-md text-base leading-7 text-slate-600">Accédez à votre espace de gestion.</p>
                    <div class="mt-10 flex items-center gap-4 border-l-2 border-hydro-lime pl-4 text-sm text-slate-600">
                        <span class="font-semibold text-hydro-ink">XAF</span>
                        <span>Franc CFA d’Afrique centrale</span>
                    </div>
                </section>
                <section class="w-full rounded-sm border border-hydro-ink/10 bg-white p-6 shadow-[0_18px_50px_-36px_rgba(23,59,58,0.45)] sm:p-9">
                    @yield('content')
                </section>
            </main>
        </div>
    </body>
</html>
