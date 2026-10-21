@extends('layouts.app')

@section('title', 'Guide de facturation')

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase text-hydro-coral">Guide administrateur · 7 étapes</p>
            <h1 class="mt-2 text-3xl font-semibold text-hydro-ink">Du dossier à la facture de l’abonné</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Traitez la demande, activez le compteur et validez un relevé pour déclencher la facturation mensuelle.</p>
        </div>
        <a class="border border-hydro-ink/20 px-4 py-2.5 text-sm font-semibold text-hydro-ink hover:bg-white" href="{{ route('admin.water.dashboard') }}">Ouvrir Gestion eau</a>
    </div>

    <ol class="grid gap-x-10 sm:grid-cols-2">
        <li class="flex gap-4 border-t border-hydro-ink/15 py-6">
            <span class="grid size-9 shrink-0 place-items-center bg-hydro-ink text-sm font-bold text-white">01</span>
            <div>
                <h2 class="font-semibold text-hydro-ink">Ouvrir les demandes récentes</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Dans « Gestion eau », repérez la demande de l’abonné. Vérifiez son nom, son adresse et le type d’usage. Si le dossier doit être étudié, choisissez « À l’étude », puis enregistrez le suivi.</p>
                <a class="mt-3 inline-block text-sm font-semibold text-hydro-teal underline underline-offset-4" href="{{ route('admin.water.dashboard') }}">Voir les demandes</a>
            </div>
        </li>
        <li class="flex gap-4 border-t border-hydro-ink/15 py-6">
            <span class="grid size-9 shrink-0 place-items-center bg-hydro-ink text-sm font-bold text-white">02</span>
            <div>
                <h2 class="font-semibold text-hydro-ink">Saisir et envoyer le devis</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Choisissez « Devis envoyé », renseignez le montant du devis et ajoutez une note si nécessaire, puis cliquez sur « Enregistrer le suivi ». Le dossier attend ensuite l’acceptation de l’abonné dans son espace.</p>
            </div>
        </li>
        <li class="flex gap-4 border-t border-hydro-ink/15 py-6">
            <span class="grid size-9 shrink-0 place-items-center bg-hydro-ink text-sm font-bold text-white">03</span>
            <div>
                <h2 class="font-semibold text-hydro-ink">Activer la demande acceptée</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Après acceptation du devis par le client, le statut devient « approved ». Cliquez alors sur « Créer l’abonnement et le compteur » dans « Gestion eau ».</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">L’abonnement et le compteur sont créés et le client peut transmettre ses relevés.</p>
            </div>
        </li>
        <li class="flex gap-4 border-t border-hydro-ink/15 py-6">
            <span class="grid size-9 shrink-0 place-items-center bg-hydro-ink text-sm font-bold text-white">04</span>
            <div>
                <h2 class="font-semibold text-hydro-ink">Vérifier le tarif applicable</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Avant la première facture, contrôlez le tarif correspondant à l’usage de l’abonné et à la période facturée. Un tarif défini avec ses tranches et dates d’effet est appliqué automatiquement.</p>
                <a class="mt-3 inline-block text-sm font-semibold text-hydro-teal underline underline-offset-4" href="{{ route('admin.tariffs.index') }}">Gérer les tarifs</a>
            </div>
        </li>
        <li class="flex gap-4 border-t border-hydro-ink/15 py-6">
            <span class="grid size-9 shrink-0 place-items-center bg-hydro-ink text-sm font-bold text-white">05</span>
            <div>
                <h2 class="font-semibold text-hydro-ink">Contrôler le relevé reçu</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Le client ou un agent transmet l’index et la date de lecture. Dans « Relevés à vérifier », comparez l’ancien et le nouvel index; contactez l’équipe terrain si la lecture semble incorrecte.</p>
            </div>
        </li>
        <li class="flex gap-4 border-t border-hydro-ink/15 py-6">
            <span class="grid size-9 shrink-0 place-items-center bg-hydro-coral text-sm font-bold text-white">06</span>
            <div>
                <h2 class="font-semibold text-hydro-ink">Valider le relevé</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Après contrôle, cliquez sur « Valider le relevé ». Seuls les relevés vérifiés peuvent être facturés. La validation ne crée pas immédiatement une facture pour le mois en cours.</p>
            </div>
        </li>
        <li class="flex gap-4 border-t border-hydro-ink/15 py-6 sm:col-span-2">
            <span class="grid size-9 shrink-0 place-items-center bg-hydro-coral text-sm font-bold text-white">07</span>
            <div>
                <h2 class="font-semibold text-hydro-ink">Laisser la génération mensuelle créer la facture</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Pour une facture immédiate, utilisez « Relevés vérifiés sans facture », puis cliquez sur « Générer et envoyer la facture ». Le PDF est envoyé à l’adresse email de l’abonné et reste accessible au téléchargement dans « Factures récentes ».</p>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Sans émission manuelle, la tâche planifiée s’exécute chaque jour à 02:00 et traite les relevés vérifiés des périodes précédentes pour les abonnements actifs. Elle crée une facture par abonné et par mois, à partir du dernier relevé validé de la période. Le client peut ensuite imprimer le PDF depuis son espace.</p>
                <a class="mt-3 inline-block text-sm font-semibold text-hydro-teal underline underline-offset-4" href="{{ route('admin.water.dashboard') }}">Retourner à Gestion eau</a>
            </div>
        </li>
    </ol>

    <p class="mt-6 border-l-4 border-hydro-lime bg-white px-5 py-4 text-sm leading-6 text-slate-600">Le paiement déclaré par l’abonné reste en attente jusqu’à votre vérification dans « Paiements à vérifier ». Le portail ne déclenche pas de débit automatique.</p>
@endsection