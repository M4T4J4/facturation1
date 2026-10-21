<p>Bonjour {{ $invoice->billedTo?->name ?? $invoice->subscription->user->name }},</p>

<p>Votre facture d’eau {{ $invoice->number }} pour la période {{ $invoice->period->format('m/Y') }} est jointe à ce message au format PDF.</p>

<p>Montant à payer : <strong>{{ number_format($invoice->amount, 0, ',', ' ') }} {{ \App\Models\WaterSetting::current()->currency }}</strong>.</p>

<p>Cordialement,<br>{{ \App\Models\WaterSetting::current()->company_name }}</p>
