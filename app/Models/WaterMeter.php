<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaterMeter extends Model
{
    protected $fillable = ['serial_number', 'status', 'subscription_id', 'installed_at', 'notes'];

    protected function casts(): array
    {
        return ['installed_at' => 'date'];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(MeterEvent::class);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(MeterReading::class);
    }
}
