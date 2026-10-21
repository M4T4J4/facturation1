<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaterSetting extends Model
{
    protected $fillable = ['company_name', 'currency', 'price_per_m3', 'monthly_fee', 'connection_fee', 'meter_deposit'];

    public static function current(): self
    {
        return self::query()->firstOrCreate(['id' => 1], [
            'company_name' => 'EauVive SARL',
            'currency' => 'FCFA',
            'price_per_m3' => 400,
            'monthly_fee' => 1000,
            'connection_fee' => 25000,
            'meter_deposit' => 5000,
        ]);
    }
}
