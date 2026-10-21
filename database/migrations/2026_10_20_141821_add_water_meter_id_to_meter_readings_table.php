<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('meter_readings', function (Blueprint $table) {
            $table->foreignId('water_meter_id')->nullable()->after('subscription_id')->constrained()->nullOnDelete();
        });

        DB::table('meter_readings')
            ->whereNull('water_meter_id')
            ->orderBy('id')
            ->chunkById(500, function ($readings): void {
                $meterIds = DB::table('water_meters')
                    ->whereIn('subscription_id', $readings->pluck('subscription_id')->unique())
                    ->pluck('id', 'subscription_id');

                foreach ($readings as $reading) {
                    $meterId = $meterIds->get($reading->subscription_id);

                    if ($meterId !== null) {
                        DB::table('meter_readings')->where('id', $reading->id)->update([
                            'water_meter_id' => $meterId,
                        ]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meter_readings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('water_meter_id');
        });
    }
};
