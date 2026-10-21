<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tariff extends Model
{
    protected $fillable = [
        'name',
        'usage_type',
        'effective_from',
        'effective_until',
        'fixed_fee',
        'tax_percent',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_until' => 'date',
        ];
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(TariffTier::class)->orderBy('position');
    }
}
