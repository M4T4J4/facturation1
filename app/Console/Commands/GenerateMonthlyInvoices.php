<?php

namespace App\Console\Commands;

use App\Models\MeterReading;
use App\Services\InvoiceIssuanceService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('water:generate-invoices')]
#[Description('Génère les factures des relevés validés des périodes précédentes')]
class GenerateMonthlyInvoices extends Command
{
    public function handle(InvoiceIssuanceService $issuance): int
    {
        $readings = MeterReading::query()
            ->where('status', 'verified')
            ->whereDate('read_at', '<', now()->startOfMonth())
            ->whereDoesntHave('invoice')
            ->whereHas('subscription', fn ($query) => $query->where('status', 'active'))
            ->with('subscription')
            ->orderBy('read_at')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (MeterReading $reading): string => $reading->subscription_id.'-'.$reading->read_at->format('Y-m'))
            ->map(fn ($periodReadings) => $periodReadings->last());
        $createdCount = 0;
        $emailFailureCount = 0;

        foreach ($readings as $reading) {
            $result = $issuance->issueFromReading($reading);

            if ($result !== null) {
                $createdCount++;

                if (! $result['email_sent']) {
                    $emailFailureCount++;
                }
            }
        }

        $this->info("Factures générées : $createdCount");

        if ($emailFailureCount > 0) {
            $this->warn("Emails non envoyés : $emailFailureCount");
        }

        return self::SUCCESS;
    }
}
