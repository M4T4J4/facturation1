@extends('layouts.app')

@section('title', 'Mes factures')

@section('content')
    <div class="mb-8">
        <p class="text-xs font-bold uppercase text-hydro-coral">Espace abonné</p>
        <h1 class="mt-2 text-3xl font-semibold text-hydro-ink">Mes factures</h1>
        <p class="mt-2 text-sm text-slate-600">Consultez vos factures et déclarez un règlement pour vérification.</p>
    </div>

    <div class="divide-y divide-hydro-ink/10 border-y border-hydro-ink/10 bg-white">
        @forelse ($invoices as $invoice)
            @php($remainingBalance = max(0, $invoice->amount - $invoice->amount_paid))
            <article class="grid gap-5 p-5 sm:p-6 lg:grid-cols-[minmax(0,1fr)_minmax(260px,0.8fr)]">
                <div>
                    <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="font-semibold text-hydro-ink">{{ $invoice->number }}</h2><span class="bg-hydro-mist px-2.5 py-1 text-xs font-semibold">{{ match ($invoice->status) {'paid' => 'Payée', 'partial' => 'Partiellement payée', default => 'À régler'} }}</span></div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a class="inline-flex border border-hydro-ink/20 px-3 py-2 text-sm font-semibold text-hydro-ink hover:bg-hydro-mist" href="{{ route('client.invoices.print', $invoice) }}" target="_blank" rel="noopener">Voir / imprimer</a>
                        <a class="inline-flex border border-hydro-teal px-3 py-2 text-sm font-semibold text-hydro-teal hover:bg-hydro-mist" href="{{ route('client.invoices.download', $invoice) }}">Télécharger PDF</a>
                        <form method="POST" action="{{ route('client.invoices.send', $invoice) }}">@csrf<button class="border border-hydro-ink/20 px-3 py-2 text-sm font-semibold text-hydro-ink hover:bg-hydro-mist" type="submit">Envoyer le PDF par email</button></form>
                    </div>
                    <p class="mt-2 text-sm text-slate-600">Période du {{ $invoice->period->format('m/Y') }} · Compteur {{ $invoice->subscription->meter_number ?: $invoice->subscription->reference }}</p>
                    <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 text-sm sm:grid-cols-3">
                        <div><dt class="text-slate-500">Consommation</dt><dd class="mt-1 font-medium">{{ $invoice->consumption_m3 }} m³</dd></div>
                        <div><dt class="text-slate-500">Consommation facturée</dt><dd class="mt-1 font-medium">{{ number_format($invoice->water_amount, 0, ',', ' ') }} {{ $settings->currency }}</dd></div>
                        <div><dt class="text-slate-500">Forfait mensuel</dt><dd class="mt-1 font-medium">{{ number_format($invoice->monthly_fee, 0, ',', ' ') }} {{ $settings->currency }}</dd></div>
                        <div><dt class="text-slate-500">Taxes</dt><dd class="mt-1 font-medium">{{ number_format($invoice->tax_amount, 0, ',', ' ') }} {{ $settings->currency }}</dd></div>
                    </dl>
                    @if ($invoice->tariff_breakdown)
                        <ul class="mt-3 space-y-1 text-xs text-slate-600">
                            @foreach ($invoice->tariff_breakdown as $tier)
                                <li>{{ $tier['quantity'] }} m³ × {{ number_format($tier['unit_price'], 0, ',', ' ') }} {{ $settings->currency }}/m³ = {{ number_format($tier['amount'], 0, ',', ' ') }} {{ $settings->currency }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <p class="mt-4 text-xl font-semibold tabular-nums">{{ number_format($invoice->amount, 0, ',', ' ') }} <span class="text-sm font-normal">{{ $settings->currency }}</span></p>
                    <p class="mt-1 text-sm text-slate-600">Déjà réglé : {{ number_format($invoice->amount_paid, 0, ',', ' ') }} {{ $settings->currency }} · Solde : {{ number_format($remainingBalance, 0, ',', ' ') }} {{ $settings->currency }}</p>
                    @if ($invoice->due_at)<p class="mt-1 text-xs text-slate-500">Échéance : {{ $invoice->due_at->format('d/m/Y') }}</p>@endif
                </div>

                @if (in_array($invoice->status, ['unpaid', 'partial'], true))
                    <form class="border-t border-hydro-ink/10 pt-4 lg:border-t-0 lg:border-l lg:pt-0 lg:pl-5" method="POST" action="{{ route('client.invoices.payments.store', $invoice) }}">
                        @csrf
                        <h3 class="text-sm font-semibold text-hydro-ink">Déclarer un paiement</h3>
                        <label class="mt-3 block text-xs font-medium text-slate-600" for="channel-{{ $invoice->id }}">Moyen de paiement</label>
                        <select class="mt-1 w-full border border-slate-300 bg-white px-3 py-2 text-sm" id="channel-{{ $invoice->id }}" name="channel" required>
                            <option value="mtn_momo">MTN Mobile Money</option><option value="orange_money">Orange Money</option><option value="wave">Wave</option><option value="cash">Espèces au bureau</option>
                        </select>
                        <label class="mt-3 block text-xs font-medium text-slate-600" for="amount-{{ $invoice->id }}">Montant à régler (facultatif)</label>
                        <input class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm" id="amount-{{ $invoice->id }}" name="amount" type="number" min="1" max="{{ $remainingBalance }}" placeholder="{{ $remainingBalance }}">
                        <label class="mt-3 block text-xs font-medium text-slate-600" for="reference-{{ $invoice->id }}">Référence de transaction (facultatif)</label>
                        <input class="mt-1 w-full border border-slate-300 px-3 py-2 text-sm" id="reference-{{ $invoice->id }}" name="reference" maxlength="120">
                        @error('payment')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                        <button class="mt-4 w-full bg-hydro-teal px-3 py-2.5 text-sm font-semibold text-white hover:bg-hydro-ink" type="submit">Transmettre le paiement</button>
                        <p class="mt-2 text-xs leading-5 text-slate-500">Aucun débit n’est effectué ici. Le paiement est marqué comme réglé après vérification par l’entreprise.</p>
                    </form>
                @else
                    <div class="flex items-center border-t border-hydro-ink/10 pt-4 text-sm text-emerald-800 lg:border-t-0 lg:border-l lg:pt-0 lg:pl-5">Règlement intégral confirmé le {{ $invoice->paid_at?->format('d/m/Y') }}</div>
                @endif
            </article>
        @empty
            <p class="p-6 text-sm text-slate-600">Aucune facture disponible.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $invoices->links() }}</div>
@endsection