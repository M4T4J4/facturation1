<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionEvent extends Model
{
    protected $fillable = [
        'subscription_id',
        'performed_by_user_id',
        'event_type',
        'old_status',
        'new_status',
        'previous_user_id',
        'new_user_id',
        'old_user_name',
        'new_user_name',
        'old_user_email',
        'new_user_email',
        'old_occupant_name',
        'new_occupant_name',
        'old_occupant_phone',
        'new_occupant_phone',
        'description',
        'event_at',
    ];

    protected function casts(): array
    {
        return ['event_at' => 'datetime'];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }
}
