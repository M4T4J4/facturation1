<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MeterReading extends Model
{
    protected $fillable = ['water_meter_id', 'recorded_by_user_id', 'previous_index', 'current_index', 'read_at', 'status', 'notes'];

    protected function casts(): array
    {
        return ['read_at' => 'date'];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function waterMeter(): BelongsTo
    {
        return $this->belongsTo(WaterMeter::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }
}
