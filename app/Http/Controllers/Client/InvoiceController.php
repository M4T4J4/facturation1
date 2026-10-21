<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\WaterSetting;
use App\Services\InvoiceIssuanceService;
use App\Services\InvoicePdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()->id;
        $invoices = Invoice::query()
            ->with('subscription')
            ->where(function ($query) use ($userId) {
                $query->where('billed_to_user_id', $userId)
                    ->orWhereHas('subscription', fn ($subscriptionQuery) => $subscriptionQuery->where('user_id', $userId));
            })
            ->latest('period')
            ->paginate(10);

        return view('client.invoices.index', ['invoices' => $invoices, 'settings' => WaterSetting::current()]);
    }

    public function print(Invoice $invoice, Request $request): View
    {
        $invoice->loadMissing(['billedTo', 'meterReading', 'subscription.user', 'subscription.waterZone']);
        $ownerUserId = $invoice->billed_to_user_id ?? $invoice->subscription?->user_id;
        abort_unless($ownerUserId === $request->user()->id, 403);

        return view('client.invoices.print', [
            'invoice' => $invoice,
            'settings' => WaterSetting::current(),
        ]);
    }

    public function download(Invoice $invoice, Request $request, InvoicePdfService $pdfs): Response
    {
        $this->authorizeInvoice($invoice, $request);

        return response($pdfs->render($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$invoice->number.'.pdf"',
        ]);
    }

    public function createFromReading(
        MeterReading $meterReading,
        Request $request,
        InvoiceIssuanceService $issuance,
    ): RedirectResponse {
        abort_unless($meterReading->subscription->user_id === $request->user()->id, 403);

        $result = $issuance->issueFromReading($meterReading);

        if ($result === null) {
            return back()->withErrors(['invoice' => 'Le relevé doit être vérifié et ne pas avoir déjà été facturé.']);
        }

        $status = $result['email_sent'] ? 'invoice-created' : 'invoice-created-email-failed';

        return redirect()->route('client.invoices.index')->with('status', $status);
    }

    public function send(Invoice $invoice, Request $request, InvoiceIssuanceService $issuance): RedirectResponse
    {
        $this->authorizeInvoice($invoice, $request);

        return back()->with('status', $issuance->send($invoice) ? 'invoice-email-sent' : 'invoice-email-failed');
    }

    public function storePayment(Invoice $invoice, Request $request): RedirectResponse
    {
        $this->authorizeInvoice($invoice, $request);
        $validated = $request->validate([
            'channel' => ['required', 'in:mtn_momo,orange_money,wave,cash'],
            'reference' => ['nullable', 'string', 'max:120'],
            'amount' => ['nullable', 'integer', 'min:1'],
        ]);

        $paymentWasSubmitted = DB::transaction(function () use ($invoice, $validated): bool {
            $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            abort_unless(in_array($lockedInvoice->status, ['unpaid', 'partial'], true), 422);

            if ($lockedInvoice->payments()->where('status', 'pending')->exists()) {
                return false;
            }

            $remainingBalance = $lockedInvoice->amount - $lockedInvoice->amount_paid;
            $paymentAmount = $validated['amount'] ?? $remainingBalance;

            if ($paymentAmount > $remainingBalance) {
                throw ValidationException::withMessages([
                    'amount' => 'Le montant dépasse le solde restant de la facture.',
                ]);
            }

            $lockedInvoice->payments()->create([
                'channel' => $validated['channel'],
                'reference' => $validated['reference'] ?? null,
                'amount' => $paymentAmount,
            ]);

            return true;
        });

        if (! $paymentWasSubmitted) {
            return back()->withErrors(['payment' => 'Un paiement est déjà en attente de vérification.']);
        }

        return back()->with('status', 'payment-submitted');
    }

    private function authorizeInvoice(Invoice $invoice, Request $request): void
    {
        $ownerUserId = $invoice->billed_to_user_id ?? $invoice->subscription?->user_id;
        abort_unless($ownerUserId === $request->user()->id, 403);
    }
}
