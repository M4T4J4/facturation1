<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaterNetworkAsset extends Model
{
    protected $fillable = [
        'name',
        'type',
        'location',
        'status',
        'capacity',
        'last_inspection_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'last_inspection_at' => 'date',
        ];
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(MaintenanceIncident::class);
    }

    public function latestIncident(): HasMany
    {
        return $this->hasMany(MaintenanceIncident::class)->latest('reported_at')->latest('id');
    }
}
