<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeterEvent extends Model
{
    protected $fillable = ['subscription_id', 'performed_by_user_id', 'event_type', 'description', 'event_at'];

    protected function casts(): array
    {
        return ['event_at' => 'date'];
    }

    public function waterMeter(): BelongsTo
    {
        return $this->belongsTo(WaterMeter::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }
}
