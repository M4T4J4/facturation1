<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'connection_request_id',
        'reference',
        'service_address',
        'meter_number',
        'status',
        'monthly_fee',
        'price_per_m3',
        'occupant_name',
        'occupant_phone',
        'water_zone_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function connectionRequest(): BelongsTo
    {
        return $this->belongsTo(ConnectionRequest::class);
    }

    public function waterZone(): BelongsTo
    {
        return $this->belongsTo(WaterZone::class);
    }

    public function meterReadings(): HasMany
    {
        return $this->hasMany(MeterReading::class);
    }

    public function waterMeter(): HasOne
    {
        return $this->hasOne(WaterMeter::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(SubscriptionEvent::class);
    }
}
