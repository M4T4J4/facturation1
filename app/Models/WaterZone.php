<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaterZone extends Model
{
    protected $fillable = ['name', 'code', 'district', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_water_zone')
            ->withTimestamps();
    }
}
