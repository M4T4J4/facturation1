<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Services\InvoiceIssuanceService;
use Illuminate\Http\RedirectResponse;

class MeterReadingController extends Controller
{
    public function createInvoice(MeterReading $meterReading, InvoiceIssuanceService $issuance): RedirectResponse
    {
        abort_if($meterReading->invoice()->exists(), 409);
        abort_unless($meterReading->status === 'verified', 422);
        abort_unless($meterReading->subscription->status === 'active', 422);

        if (Invoice::whereBelongsTo($meterReading->subscription)->whereDate('period', $meterReading->read_at->copy()->startOfMonth())->exists()) {
            return back()->withErrors(['invoice' => 'Une facture existe déjà pour cet abonnement et cette période.']);
        }

        $result = $issuance->issueFromReading($meterReading);

        if ($result === null) {
            return back()->withErrors(['invoice' => 'Une facture existe déjà pour cet abonnement et cette période.']);
        }

        $status = $result['email_sent'] ? 'invoice-created' : 'invoice-created-email-failed';

        return back()->with('status', $status);
    }

    public function verify(MeterReading $meterReading): RedirectResponse
    {
        abort_unless($meterReading->status === 'submitted', 422);

        $meterReading->update(['status' => 'verified']);

        return back()->with('status', 'reading-verified');
    }
}
