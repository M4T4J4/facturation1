<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['amount', 'channel', 'reference', 'status', 'confirmed_at'];

    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
