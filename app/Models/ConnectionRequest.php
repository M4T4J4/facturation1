<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ConnectionRequest extends Model
{
    use HasFactory;

    protected $fillable = ['service_address', 'usage_type', 'status', 'quote_amount', 'admin_note'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }
}
