<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Guide d’utilisation | {{ config('app.name', 'HydroGestion') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f3f6f2] font-sans text-hydro-ink antialiased">
    <header class="border-b border-hydro-ink/10 bg-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-5 py-4 sm:px-8">
            <a class="flex items-center gap-3 font-semibold text-hydro-ink" href="{{ route('home') }}">
                <span class="grid size-10 place-items-center rounded-sm bg-hydro-teal text-lg font-bold text-white">H</span>
                <span>{{ config('app.name', 'HydroGestion') }}</span>
            </a>
            <nav class="flex items-center gap-4 text-sm font-semibold">
                <a class="text-slate-600 underline underline-offset-4 hover:text-hydro-teal" href="{{ route('login') }}">Connexion</a>
                <a class="bg-hydro-teal px-4 py-2.5 text-white hover:bg-hydro-ink" href="{{ route('register') }}">Créer un compte</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-5 py-10 sm:px-8 sm:py-14">
        <section class="border-b border-hydro-ink/15 pb-8 sm:pb-10">
            <p class="text-xs font-bold uppercase text-hydro-coral">Guide abonné · 6 étapes</p>
            <h1 class="mt-3 max-w-3xl text-3xl font-semibold leading-tight sm:text-4xl">De l’inscription à votre facture, étape par étape.</h1>
            <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">Suivez le parcours de raccordement, transmettez votre relevé, puis imprimez votre facture ou enregistrez-la en PDF.</p>
        </section>

        <ol class="grid gap-x-12 sm:grid-cols-2">
            <li class="flex gap-4 border-b border-hydro-ink/10 py-6">
                <span class="grid size-9 shrink-0 place-items-center bg-hydro-ink text-sm font-bold text-white">01</span>
                <div>
                    <h2 class="font-semibold">Créez votre compte abonné</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Renseignez votre nom, votre adresse email et un mot de passe. Le téléphone est facultatif. Connectez-vous ensuite à votre espace.</p>
                    <a class="mt-3 inline-block text-sm font-semibold text-hydro-teal underline underline-offset-4" href="{{ route('register') }}">Créer mon compte</a>
                </div>
            </li>
            <li class="flex gap-4 border-b border-hydro-ink/10 py-6">
                <span class="grid size-9 shrink-0 place-items-center bg-hydro-ink text-sm font-bold text-white">02</span>
                <div>
                    <h2 class="font-semibold">Demandez le raccordement</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Dans « Raccordement », indiquez l’adresse à raccorder et choisissez l’usage habitation ou professionnel. Suivez le statut de la demande sur cette page.</p>
                </div>
            </li>
            <li class="flex gap-4 border-b border-hydro-ink/10 py-6">
                <span class="grid size-9 shrink-0 place-items-center bg-hydro-ink text-sm font-bold text-white">03</span>
                <div>
                    <h2 class="font-semibold">Acceptez le devis</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Quand le devis apparaît dans « Raccordement », vérifiez le montant puis cliquez sur « Accepter le devis ». L’équipe procédera ensuite à l’activation.</p>
                </div>
            </li>
            <li class="flex gap-4 border-b border-hydro-ink/10 py-6">
                <span class="grid size-9 shrink-0 place-items-center bg-hydro-ink text-sm font-bold text-white">04</span>
                <div>
                    <h2 class="font-semibold">Attendez l’activation du compteur</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Une fois l’abonnement activé et le compteur installé, ses références sont visibles dans votre tableau de bord.</p>
                </div>
            </li>
            <li class="flex gap-4 border-b border-hydro-ink/10 py-6">
                <span class="grid size-9 shrink-0 place-items-center bg-hydro-ink text-sm font-bold text-white">05</span>
                <div>
                    <h2 class="font-semibold">Envoyez l’index du compteur</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Dans « Relevés », saisissez l’index actuel affiché en m³ et la date du relevé. L’équipe doit vérifier ce relevé avant qu’une facture puisse être créée.</p>
                </div>
            </li>
            <li class="flex gap-4 border-b border-hydro-ink/10 py-6">
                <span class="grid size-9 shrink-0 place-items-center bg-hydro-coral text-sm font-bold text-white">06</span>
                <div>
                    <h2 class="font-semibold">Ouvrez et enregistrez la facture</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Dès que le relevé est vérifié, vous pouvez cliquer sur « Générer et envoyer » depuis « Relevés », sans attendre la génération mensuelle. Le PDF est envoyé à l’adresse email du compte si le service mail est configuré. Sinon, l’équipe peut créer la facture depuis « Gestion eau ».</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Dans « Factures », choisissez « Télécharger PDF » ou « Voir / imprimer ». Le document comporte deux exemplaires et détaille les index, la consommation, le prix, l’entretien du compteur, les taxes et le solde.</p>
                    @auth
                        <a class="mt-3 inline-block text-sm font-semibold text-hydro-teal underline underline-offset-4" href="{{ route('client.invoices.index') }}">Accéder à mes factures</a>
                    @else
                        <a class="mt-3 inline-block text-sm font-semibold text-hydro-teal underline underline-offset-4" href="{{ route('login') }}">Me connecter pour voir mes factures</a>
                    @endauth
                </div>
            </li>
        </ol>

        <footer class="mt-8 flex flex-wrap items-start justify-between gap-4 border-l-4 border-hydro-lime bg-white px-5 py-4">
            <p class="max-w-2xl text-sm leading-6 text-slate-600">Le paiement déclaré dans le portail reste en attente de vérification par l’entreprise. Aucun débit automatique n’est effectué depuis le site.</p>
            <a class="text-sm font-semibold text-hydro-teal underline underline-offset-4" href="{{ route('login') }}">Retour à la connexion</a>
        </footer>
    </main>
</body>
</html>