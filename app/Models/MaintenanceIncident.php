<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceIncident extends Model
{
    protected $fillable = [
        'water_network_asset_id',
        'title',
        'kind',
        'severity',
        'status',
        'reported_at',
        'resolved_at',
        'assigned_to_user_id',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(WaterNetworkAsset::class, 'water_network_asset_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }
}
