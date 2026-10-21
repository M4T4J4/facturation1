<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\Subscription;
use App\Models\Tariff;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceBillingService
{
    public function createFromReading(MeterReading $meterReading): ?Invoice
    {
        return DB::transaction(function () use ($meterReading): ?Invoice {
            $reading = MeterReading::query()->lockForUpdate()->findOrFail($meterReading->id);

            if ($reading->status !== 'verified') {
                return null;
            }

            $subscription = Subscription::query()
                ->with('connectionRequest')
                ->lockForUpdate()
                ->findOrFail($reading->subscription_id);

            if ($subscription->status !== 'active') {
                return null;
            }

            $period = $reading->read_at->copy()->startOfMonth();

            if ($subscription->invoices()->whereDate('period', $period)->exists()) {
                return null;
            }

            $consumption = $reading->current_index - $reading->previous_index;
            $tariff = $this->tariffFor($subscription, $period);
            $billing = $tariff === null
                ? $this->legacyBilling($subscription, $consumption)
                : $this->tariffBilling($tariff, $consumption);
            $subtotal = $billing['water_amount'] + $billing['monthly_fee'];
            $taxAmount = intdiv(($subtotal * $billing['tax_percent']) + 50, 100);

            $invoice = $subscription->invoices()->create([
                'meter_reading_id' => $reading->id,
                'billed_to_user_id' => $subscription->user_id,
                'water_zone_id' => $subscription->water_zone_id,
                'tariff_id' => $tariff?->id,
                'number' => 'FAC-'.now()->format('ym').'-'.Str::upper(Str::random(6)),
                'period' => $period,
                'consumption_m3' => $consumption,
                'price_per_m3' => $billing['price_per_m3'],
                'monthly_fee' => $billing['monthly_fee'],
                'water_amount' => $billing['water_amount'],
                'tax_amount' => $taxAmount,
                'tariff_breakdown' => $billing['breakdown'],
                'amount' => $subtotal + $taxAmount,
                'amount_paid' => 0,
                'due_at' => $period->copy()->endOfMonth()->addDays(14),
                'status' => 'unpaid',
            ]);

            $reading->update(['status' => 'verified']);

            return $invoice;
        });
    }

    private function tariffFor(Subscription $subscription, Carbon $period): ?Tariff
    {
        $usageType = $subscription->connectionRequest?->usage_type;

        if ($usageType === null) {
            return null;
        }

        return Tariff::query()
            ->with('tiers')
            ->where('usage_type', $usageType)
            ->whereDate('effective_from', '<=', $period)
            ->where(fn ($query) => $query
                ->whereNull('effective_until')
                ->orWhereDate('effective_until', '>=', $period))
            ->orderByDesc('effective_from')
            ->first();
    }

    /**
     * @return array{
     *     water_amount: int,
     *     monthly_fee: int,
     *     price_per_m3: int,
     *     tax_percent: int,
     *     breakdown: array<int, array{from: int, to: int|null, unit_price: int, quantity: int, amount: int}>
     * }
     */
    private function tariffBilling(Tariff $tariff, int $consumption): array
    {
        $waterAmount = 0;
        $breakdown = [];

        foreach ($tariff->tiers as $tier) {
            if ($consumption < $tier->consumption_from) {
                break;
            }

            $capacity = $tier->consumption_to === null
                ? $consumption - $tier->consumption_from + 1
                : $tier->consumption_to - $tier->consumption_from + 1;
            $quantity = min($consumption - $tier->consumption_from + 1, $capacity);
            $amount = $quantity * $tier->unit_price;
            $waterAmount += $amount;

            if ($quantity > 0) {
                $breakdown[] = [
                    'from' => $tier->consumption_from,
                    'to' => $tier->consumption_to,
                    'unit_price' => $tier->unit_price,
                    'quantity' => $quantity,
                    'amount' => $amount,
                ];
            }
        }

        return [
            'water_amount' => $waterAmount,
            'monthly_fee' => $tariff->fixed_fee,
            'price_per_m3' => $tariff->tiers->first()?->unit_price ?? 0,
            'tax_percent' => $tariff->tax_percent,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * @return array{
     *     water_amount: int,
     *     monthly_fee: int,
     *     price_per_m3: int,
     *     tax_percent: int,
     *     breakdown: array<int, array{from: int, to: int|null, unit_price: int, quantity: int, amount: int}>
     * }
     */
    private function legacyBilling(Subscription $subscription, int $consumption): array
    {
        $waterAmount = $consumption * $subscription->price_per_m3;

        return [
            'water_amount' => $waterAmount,
            'monthly_fee' => $subscription->monthly_fee,
            'price_per_m3' => $subscription->price_per_m3,
            'tax_percent' => 0,
            'breakdown' => [[
                'from' => 1,
                'to' => null,
                'unit_price' => $subscription->price_per_m3,
                'quantity' => $consumption,
                'amount' => $waterAmount,
            ]],
        ];
    }
}
