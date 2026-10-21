<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldMission extends Model
{
    protected $fillable = [
        'title',
        'water_zone_id',
        'assigned_to_user_id',
        'status',
        'scheduled_for',
        'notes',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(WaterZone::class, 'water_zone_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }
}
