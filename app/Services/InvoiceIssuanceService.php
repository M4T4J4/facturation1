<?php

namespace App\Services;

use App\Mail\InvoiceIssued;
use App\Models\Invoice;
use App\Models\MeterReading;
use Illuminate\Support\Facades\Mail;
use Throwable;

class InvoiceIssuanceService
{
    public function __construct(
        private InvoiceBillingService $billing,
        private InvoicePdfService $pdfs,
    ) {}

    /**
     * @return array{invoice: Invoice, email_sent: bool}|null
     */
    public function issueFromReading(MeterReading $reading): ?array
    {
        $invoice = $this->billing->createFromReading($reading);

        if ($invoice === null) {
            return null;
        }

        return [
            'invoice' => $invoice,
            'email_sent' => $this->send($invoice),
        ];
    }

    public function send(Invoice $invoice): bool
    {
        try {
            $invoice->loadMissing(['billedTo', 'subscription.user']);
            $recipient = $invoice->billedTo ?? $invoice->subscription->user;
            $pdfContents = $this->pdfs->render($invoice);

            Mail::to($recipient)->send(new InvoiceIssued($invoice, $pdfContents));

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }
}
