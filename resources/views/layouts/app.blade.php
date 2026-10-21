<div>
    <!-- An unexamined life is not worth living. - Socrates -->
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Espace de travail') | {{ config('app.name', 'HydroGestion') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f3f6f2] font-sans text-hydro-ink antialiased">
        <header class="border-b border-hydro-ink/10 bg-white">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-4 sm:px-8">
                <a class="flex items-center gap-3 font-semibold text-hydro-ink" href="{{ route('dashboard') }}">
                    <span class="grid size-10 place-items-center rounded-sm bg-hydro-teal text-lg font-bold text-white">H</span>
                    <span>{{ config('app.name', 'HydroGestion') }}</span>
                </a>
                <nav aria-label="Navigation principale" class="flex flex-wrap items-center gap-2 text-sm">
                    <a class="rounded-sm px-3 py-2 text-slate-600 hover:bg-hydro-mist hover:text-hydro-ink" href="{{ route('guide') }}">Guide</a>
                    @if (auth()->user()->hasRole(\App\UserRole::Administrator))
                        <a class="rounded-sm px-3 py-2 text-slate-600 hover:bg-hydro-mist hover:text-hydro-ink" href="{{ route('admin.guide') }}">Guide gestion</a>
                        <a class="rounded-sm px-3 py-2 text-slate-600 hover:bg-hydro-mist hover:text-hydro-ink" href="{{ route('admin.water.dashboard') }}">Gestion eau</a>
                        <a class="rounded-sm px-3 py-2 text-slate-600 hover:bg-hydro-mist hover:text-hydro-ink" href="{{ route('admin.meters.index') }}">Compteurs</a>
                        <a class="rounded-sm px-3 py-2 text-slate-600 hover:bg-hydro-mist hover:text-hydro-ink" href="{{ route('admin.subscriptions.index') }}">Abonnements</a>
                        <a class="rounded-sm px-3 py-2 text-slate-600 hover:bg-hydro-mist hover:text-hydro-ink" href="{{ route('admin.zones.index') }}">Zones</a>
                        <a class="rounded-sm px-3 py-2 text-slate-600 hover:bg-hydro-mist hover:text-hydro-ink" href="{{ route('admin.network.index') }}">Réseau</a>
                        <a class="rounded-sm px-3 py-2 text-slate-600 hover:bg-hydro-mist hover:text-hydro-ink" href="{{ route('admin.tariffs.index') }}">Tarifs</a>
                        <a class="rounded-sm px-3 py-2 text-slate-600 hover:bg-hydro-mist hover:text-hydro-ink" href="{{ route('admin.users.index') }}">Utilisateurs</a>
                    @elseif (auth()->user()->hasRole(\App\UserRole::Customer))
                        <a class="rounded-sm px-3 py-2 text-slate-600 hover:bg-hydro-mist hover:text-hydro-ink" href="{{ route('client.requests.index') }}">Raccordement</a>
                        <a class="rounded-sm px-3 py-2 text-slate-600 hover:bg-hydro-mist hover:text-hydro-ink" href="{{ route('client.invoices.index') }}">Factures</a>
                        <a class="rounded-sm px-3 py-2 text-slate-600 hover:bg-hydro-mist hover:text-hydro-ink" href="{{ route('client.readings.index') }}">Relevés</a>
                        <a class="rounded-sm px-3 py-2 text-slate-600 hover:bg-hydro-mist hover:text-hydro-ink" href="{{ route('client.support.index') }}">Assistance</a>
                    @else
                        <a class="rounded-sm px-3 py-2 text-slate-600 hover:bg-hydro-mist hover:text-hydro-ink" href="{{ route('agent.readings.index') }}">Relevés terrain</a>
                    @endif
                    <a class="rounded-sm px-3 py-2 text-slate-600 hover:bg-hydro-mist hover:text-hydro-ink" href="{{ route('profile.edit') }}">Mon profil</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-sm border border-hydro-ink/15 px-3 py-2 font-medium text-hydro-ink hover:border-hydro-teal hover:text-hydro-teal" type="submit">Déconnexion</button>
                    </form>
                </nav>
            </div>
        </header>
        <main class="mx-auto max-w-7xl px-5 py-8 sm:px-8 sm:py-10">
            @if (session('status'))
                <div class="mb-6 border-l-4 border-hydro-teal bg-white px-4 py-3 text-sm text-hydro-ink" role="status">
                    @switch(session('status'))
                        @case('profile-updated') Profil mis à jour. @break
                        @case('password-updated') Mot de passe modifié. @break
                        @case('role-updated') Rôle mis à jour. @break
                        @case('request-submitted') Demande transmise à l’équipe. @break
                        @case('quote-accepted') Devis accepté. L’équipe peut activer votre abonnement. @break
                        @case('request-updated') Demande mise à jour. @break
                        @case('subscription-activated') Abonnement activé. @break
                        @case('subscription-status-updated') Statut du contrat mis à jour. @break
                        @case('subscription-holder-transferred') Titulaire du contrat mis à jour. @break
                        @case('subscription-occupant-updated') Occupant du point de livraison mis à jour. @break
                        @case('water-zone-created') Zone de distribution créée. @break
                        @case('water-zone-updated') Zone de distribution mise à jour. @break
                        @case('subscription-zone-assigned') Abonnement affecté à la zone. @break
                        @case('water-zone-agents-updated') Affectations des agents enregistrées. @break
                        @case('network-asset-created') Équipement réseau enregistré. @break
                        @case('network-asset-updated') Équipement réseau mis à jour. @break
                        @case('incident-created') Incident réseau déclaré. @break
                        @case('incident-updated') Incident réseau mis à jour. @break
                        @case('field-mission-created') Mission terrain planifiée. @break
                        @case('field-mission-updated') Mission terrain mise à jour. @break
                        @case('notification-sent') Notification système enregistrée. @break
                        @case('invoice-created') Facture créée et envoyée au transport email configuré avec son PDF joint. @break
                        @case('invoice-created-email-failed') Facture créée, mais email non envoyé. Vérifiez la configuration mail; le PDF reste disponible dans les factures. @break
                        @case('invoice-email-sent') PDF de la facture envoyé au transport email configuré. @break
                        @case('invoice-email-failed') Email non envoyé. Vérifiez la configuration mail; le PDF reste téléchargeable. @break
                        @case('tariff-created') Tarif progressif enregistré. @break
                        @case('reading-verified') Relevé validé ; vous pouvez générer sa facture maintenant ou la laisser à la génération mensuelle. @break
                        @case('payment-submitted') Paiement déclaré, en attente de vérification. @break
                        @case('payment-reviewed') Paiement traité. @break
                        @case('reading-submitted') Relevé transmis pour vérification. @break
                        @case('ticket-submitted') Demande d’assistance transmise. @break
                        @case('ticket-updated') Ticket mis à jour. @break
                        @case('settings-updated') Tarifs et paramètres enregistrés. @break
                        @case('meter-created') Compteur ajouté à l’inventaire. @break
                        @case('meter-updated') Compteur mis à jour. @break
                        @case('meter-assigned') Affectation du compteur enregistrée. @break
                        @case('meter-event-recorded') Intervention enregistrée dans l’historique. @break
                        @default {{ session('status') }}
                    @endswitch
                </div>
            @endif
            @yield('content')
        </main>
    </body>
</html>
