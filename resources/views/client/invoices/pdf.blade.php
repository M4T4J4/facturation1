<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 portrait; margin: 7mm; }
        body { color: #172523; font-family: DejaVu Sans, sans-serif; font-size: 8pt; margin: 0; }
        .copy { height: 137mm; padding: 3mm 2mm; page-break-inside: avoid; }
        .copy + .copy { border-top: 1px dashed #77817e; }
        table { border-collapse: collapse; width: 100%; }
        .heading td { border-bottom: 2px solid #173d37; padding: 0 0 7px; vertical-align: top; }
        .company { color: #173d37; font-size: 15pt; font-weight: bold; text-transform: uppercase; }
        .muted { color: #52605d; }
        .title { font-size: 15pt; font-weight: bold; text-align: right; }
        .copy-label { color: #52605d; font-size: 7pt; font-weight: bold; text-align: right; text-transform: uppercase; }
        .invoice-line { margin: 8px 0; }
        .invoice-line td { padding: 2px 3px; }
        .details td { border: 1px solid #aab3b0; padding: 5px; vertical-align: top; width: 33.33%; }
        .label { color: #52605d; display: block; font-size: 6pt; font-weight: bold; margin-bottom: 2px; text-transform: uppercase; }
        .items { margin-top: 8px; table-layout: fixed; }
        .items th, .items td { border: 1px solid #7d8985; padding: 5px 3px; text-align: center; }
        .items th { background: #f1f4f2; font-size: 6.5pt; }
        .totals { margin: 7px 0 0 auto; width: 54%; }
        .totals td { padding: 2px 3px; }
        .amount { text-align: right; }
        .grand-total td { border-top: 1px solid #173d37; font-size: 10pt; font-weight: bold; padding-top: 5px; }
        .signatures { margin-top: 12px; }
        .signatures td { border-top: 1px solid #7d8985; padding-top: 4px; text-align: center; width: 50%; }
        .note { color: #52605d; font-size: 6.5pt; margin-top: 7px; }
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

    @foreach (['Exemplaire abonné', 'Exemplaire comité'] as $copyLabel)
        <section class="copy">
            <table class="heading">
                <tr>
                    <td><div class="company">{{ $settings->company_name }}</div><div class="muted">Gestion et distribution d’eau</div></td>
                    <td><div class="title">FACTURE D’EAU</div><div class="copy-label">{{ $copyLabel }}</div></td>
                </tr>
            </table>
            <table class="invoice-line">
                <tr>
                    <td>N° <strong>{{ $invoice->number }}</strong></td>
                    <td>Mois : <strong>{{ $invoice->period->format('m/Y') }}</strong></td>
                    <td>Émise le : <strong>{{ $invoice->created_at->format('d/m/Y') }}</strong></td>
                </tr>
            </table>
            <table class="details">
                <tr>
                    <td><span class="label">Destinataire</span>{{ $invoice->subscription->occupant_name ?: $customer->name }}</td>
                    <td><span class="label">Téléphone</span>{{ $invoice->subscription->occupant_phone ?: $customer->phone ?: '—' }}</td>
                    <td><span class="label">Référence abonné</span>{{ $invoice->subscription->reference }}</td>
                </tr>
                <tr>
                    <td><span class="label">Point de livraison</span>{{ $invoice->subscription->service_address }}</td>
                    <td><span class="label">Compteur</span>{{ $invoice->subscription->meter_number ?: $invoice->subscription->reference }}</td>
                    <td><span class="label">Zone</span>{{ $invoice->subscription->waterZone?->name ?: '—' }}</td>
                </tr>
            </table>
            <table class="items">
                <thead>
                    <tr>
                        <th rowspan="2">Compteur</th><th colspan="2">Index</th><th rowspan="2">Consommation<br>m³</th>
                        <th rowspan="2">Prix unitaire<br>moyen</th><th rowspan="2">Montant<br>consommation</th>
                        <th rowspan="2">Entretien<br>compteur</th><th rowspan="2">Montant à payer</th>
                    </tr>
                    <tr><th>Nouveau</th><th>Ancien</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $invoice->subscription->meter_number ?: $invoice->subscription->reference }}</td>
                        <td>{{ $meterReading?->current_index ?? '—' }}</td><td>{{ $meterReading?->previous_index ?? '—' }}</td>
                        <td>{{ $invoice->consumption_m3 }}</td><td>{{ number_format($averageUnitPrice, 2, ',', ' ') }} {{ $settings->currency }}</td>
                        <td>{{ number_format($invoice->water_amount, 0, ',', ' ') }} {{ $settings->currency }}</td>
                        <td>{{ number_format($invoice->monthly_fee, 0, ',', ' ') }} {{ $settings->currency }}</td>
                        <td>{{ number_format($invoice->amount, 0, ',', ' ') }} {{ $settings->currency }}</td>
                    </tr>
                </tbody>
            </table>
            <table class="totals">
                <tr><td>Montant consommation</td><td class="amount">{{ number_format($invoice->water_amount, 0, ',', ' ') }} {{ $settings->currency }}</td></tr>
                <tr><td>Entretien compteur</td><td class="amount">{{ number_format($invoice->monthly_fee, 0, ',', ' ') }} {{ $settings->currency }}</td></tr>
                <tr><td>Taxes</td><td class="amount">{{ number_format($invoice->tax_amount, 0, ',', ' ') }} {{ $settings->currency }}</td></tr>
                <tr class="grand-total"><td>TOTAL À PAYER</td><td class="amount">{{ number_format($invoice->amount, 0, ',', ' ') }} {{ $settings->currency }}</td></tr>
                <tr><td>Déjà réglé</td><td class="amount">{{ number_format($invoice->amount_paid, 0, ',', ' ') }} {{ $settings->currency }}</td></tr>
                <tr><td>Solde restant</td><td class="amount">{{ number_format($remainingBalance, 0, ',', ' ') }} {{ $settings->currency }}</td></tr>
            </table>
            <table class="signatures"><tr><td>Signature et cachet du comité</td><td>Signature de l’abonné</td></tr></table>
            <p class="note">Date limite de paiement : {{ $invoice->due_at?->format('d/m/Y') ?: '—' }} · Statut : {{ match ($invoice->status) {'paid' => 'Payée', 'partial' => 'Partiellement payée', default => 'À régler'} }}</p>
        </section>
    @endforeach
</body>
</html>