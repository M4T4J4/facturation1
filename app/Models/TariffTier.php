<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TariffTier extends Model
{
    protected $fillable = [
        'position',
        'consumption_from',
        'consumption_to',
        'unit_price',
    ];

    public function tariff(): BelongsTo
    {
        return $this->belongsTo(Tariff::class);
    }
}
