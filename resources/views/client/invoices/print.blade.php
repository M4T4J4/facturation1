<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Facture {{ $invoice->number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #e9ecea; color: #172523; font-family: Arial, Helvetica, sans-serif; }
        .toolbar { display: flex; justify-content: center; gap: 12px; padding: 16px; }
        .toolbar a, .toolbar button { border: 1px solid #173d37; background: #fff; color: #173d37; cursor: pointer; font: inherit; font-size: 14px; font-weight: 700; padding: 10px 14px; text-decoration: none; }
        .toolbar button { background: #173d37; color: #fff; }
        .sheet { width: 210mm; min-height: 297mm; margin: 0 auto 24px; padding: 5mm; background: #fff; box-shadow: 0 8px 30px #17252320; }
        .copy { height: 143mm; padding: 5mm 4mm; overflow: hidden; }
        .copy + .copy { border-top: 1px dashed #78827f; }
        .heading { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; border-bottom: 2px solid #173d37; padding-bottom: 9px; }
        .company { font-size: 17px; font-weight: 800; text-transform: uppercase; }
        .subtitle { margin-top: 4px; color: #52605d; font-size: 10px; }
        .title { text-align: right; font-size: 18px; font-weight: 800; }
        .copy-label { margin-top: 5px; color: #52605d; font-size: 9px; font-weight: 700; text-transform: uppercase; }
        .invoice-line { display: flex; justify-content: space-between; gap: 16px; margin: 9px 0; font-size: 10px; }
        .invoice-line strong { font-size: 11px; }
        .details { display: grid; grid-template-columns: 1.2fr 1fr 1fr; gap: 6px 12px; border: 1px solid #aab3b0; padding: 8px; font-size: 10px; }
        .details span { display: block; margin-bottom: 2px; color: #52605d; font-size: 8px; font-weight: 700; text-transform: uppercase; }
        table { width: 100%; margin-top: 10px; border-collapse: collapse; table-layout: fixed; font-size: 9px; }
        th, td { border: 1px solid #7d8985; padding: 6px 4px; text-align: center; overflow-wrap: anywhere; }
        th { background: #f1f4f2; font-size: 8px; font-weight: 700; }
        .totals { display: grid; grid-template-columns: 1fr auto; gap: 5px 12px; margin: 9px 0 0 auto; width: 55%; font-size: 10px; }
        .grand-total { border-top: 1px solid #173d37; padding-top: 6px; font-size: 14px; font-weight: 800; }
        .signatures { display: flex; justify-content: space-between; gap: 24px; margin-top: 16px; font-size: 9px; }
        .signature { width: 42%; border-top: 1px solid #7d8985; padding-top: 5px; text-align: center; }
        .note { margin-top: 8px; color: #52605d; font-size: 8px; }
        @page { size: A4 portrait; margin: 5mm; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
            .copy { height: 143mm; }
        }
        @media screen and (max-width: 720px) {
            .sheet { width: 100%; min-height: 0; padding: 8px; }
            .copy { height: auto; min-height: 430px; padding: 16px 8px; }
            .details { grid-template-columns: 1fr 1fr; }
            table { font-size: 8px; }
            th, td { padding: 4px 2px; }
        }
    </style>
</head>
<body>
    @php
        $customer = $invoice->billedTo ?? $invoice->subscription->user;
        $meterReading = $invoice->meterReading;
        $averageUnitPrice = $invoice->consumption_m3 > 0
            ? $invoice->water_amount / $invoice->consumption_m3
            : $invoice->price_per_m3;
        $remainingBalance = max(0, $invoice->amount - $invoice->amount_paid);
    @endphp

    <nav class="toolbar" aria-label="Actions de la facture">
        <a href="{{ route('client.invoices.index') }}">Retour aux factures</a>
        <a href="{{ route('client.invoices.download', $invoice) }}">Télécharger le PDF</a>
        <button type="button" onclick="window.print()">Imprimer / enregistrer en PDF</button>
    </nav>

    <main class="sheet">
        @foreach (['Exemplaire abonné', 'Exemplaire comité'] as $copyLabel)
            <section class="copy">
                <header class="heading">
                    <div>
                        <div class="company">{{ $settings->company_name }}</div>
                        <div class="subtitle">Facturation et gestion du service d’eau</div>
                    </div>
                    <div>
                        <div class="title">FACTURE D’EAU</div>
                        <div class="copy-label">{{ $copyLabel }}</div>
                    </div>
                </header>

                <div class="invoice-line">
                    <div>N° <strong>{{ $invoice->number }}</strong></div>
                    <div>Mois : <strong>{{ $invoice->period->format('m/Y') }}</strong></div>
                    <div>Émise le : <strong>{{ $invoice->created_at->format('d/m/Y') }}</strong></div>
                </div>

                <div class="details">
                    <div><span>Destinataire</span>{{ $invoice->subscription->occupant_name ?: $customer->name }}</div>
                    <div><span>Téléphone</span>{{ $invoice->subscription->occupant_phone ?: $customer->phone ?: '—' }}</div>
                    <div><span>Référence abonné</span>{{ $invoice->subscription->reference }}</div>
                    <div><span>Point de livraison</span>{{ $invoice->subscription->service_address }}</div>
                    <div><span>Compteur</span>{{ $invoice->subscription->meter_number ?: $invoice->subscription->reference }}</div>
                    <div><span>Zone</span>{{ $invoice->subscription->waterZone?->name ?: '—' }}</div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th rowspan="2">Compteur</th>
                            <th colspan="2">Index</th>
                            <th rowspan="2">Consommation<br>m³</th>
                            <th rowspan="2">Prix unitaire<br>moyen</th>
                            <th rowspan="2">Montant<br>consommation</th>
                            <th rowspan="2">Entretien<br>compteur</th>
                            <th rowspan="2">Montant à payer</th>
                        </tr>
                        <tr><th>Nouveau</th><th>Ancien</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $invoice->subscription->meter_number ?: $invoice->subscription->reference }}</td>
                            <td>{{ $meterReading?->current_index ?? '—' }}</td>
                            <td>{{ $meterReading?->previous_index ?? '—' }}</td>
                            <td>{{ $invoice->consumption_m3 }}</td>
                            <td>{{ number_format($averageUnitPrice, 2, ',', ' ') }} {{ $settings->currency }}</td>
                            <td>{{ number_format($invoice->water_amount, 0, ',', ' ') }} {{ $settings->currency }}</td>
                            <td>{{ number_format($invoice->monthly_fee, 0, ',', ' ') }} {{ $settings->currency }}</td>
                            <td>{{ number_format($invoice->amount, 0, ',', ' ') }} {{ $settings->currency }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="totals">
                    <span>Montant consommation</span><span>{{ number_format($invoice->water_amount, 0, ',', ' ') }} {{ $settings->currency }}</span>
                    <span>Entretien compteur</span><span>{{ number_format($invoice->monthly_fee, 0, ',', ' ') }} {{ $settings->currency }}</span>
                    <span>Taxes</span><span>{{ number_format($invoice->tax_amount, 0, ',', ' ') }} {{ $settings->currency }}</span>
                    <span class="grand-total">TOTAL À PAYER</span><span class="grand-total">{{ number_format($invoice->amount, 0, ',', ' ') }} {{ $settings->currency }}</span>
                    <span>Déjà réglé</span><span>{{ number_format($invoice->amount_paid, 0, ',', ' ') }} {{ $settings->currency }}</span>
                    <span>Solde restant</span><span>{{ number_format($remainingBalance, 0, ',', ' ') }} {{ $settings->currency }}</span>
                </div>

                <div class="signatures">
                    <div class="signature">Signature et cachet du comité</div>
                    <div class="signature">Signature de l’abonné</div>
                </div>
                <div class="note">Date limite de paiement : {{ $invoice->due_at?->format('d/m/Y') ?: '—' }} · Statut : {{ match ($invoice->status) {'paid' => 'Payée', 'partial' => 'Partiellement payée', default => 'À régler'} }}</div>
            </section>
        @endforeach
    </main>
</body>
</html>