<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\WaterSetting;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePdfService
{
    public function render(Invoice $invoice): string
    {
        $invoice->loadMissing(['billedTo', 'meterReading', 'subscription.user', 'subscription.waterZone']);

        return Pdf::loadView('client.invoices.pdf', [
            'invoice' => $invoice,
            'settings' => WaterSetting::current(),
        ])->setPaper('a4', 'portrait')->output();
    }
}
