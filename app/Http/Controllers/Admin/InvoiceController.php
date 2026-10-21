<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceIssuanceService;
use App\Services\InvoicePdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    public function download(Invoice $invoice, InvoicePdfService $pdfs): Response
    {
        return response($pdfs->render($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$invoice->number.'.pdf"',
        ]);
    }

    public function send(Invoice $invoice, InvoiceIssuanceService $issuance): RedirectResponse
    {
        return back()->with('status', $issuance->send($invoice) ? 'invoice-email-sent' : 'invoice-email-failed');
    }
}
