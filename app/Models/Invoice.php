<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'meter_reading_id',
        'billed_to_user_id',
        'water_zone_id',
        'tariff_id',
        'number',
        'period',
        'consumption_m3',
        'price_per_m3',
        'monthly_fee',
        'water_amount',
        'tax_amount',
        'tariff_breakdown',
        'amount',
        'amount_paid',
        'due_at',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'period' => 'date',
            'due_at' => 'date',
            'paid_at' => 'datetime',
            'tariff_breakdown' => 'array',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function billedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'billed_to_user_id');
    }

    public function meterReading(): BelongsTo
    {
        return $this->belongsTo(MeterReading::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function tariff(): BelongsTo
    {
        return $this->belongsTo(Tariff::class);
    }
}
