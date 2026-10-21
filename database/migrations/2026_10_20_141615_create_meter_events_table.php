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
        Schema::create('meter_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('water_meter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('performed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type')->index();
            $table->text('description')->nullable();
            $table->date('event_at')->index();
            $table->timestamps();
        });

        DB::table('subscriptions')
            ->whereNotNull('meter_number')
            ->where('meter_number', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($subscriptions): void {
                foreach ($subscriptions as $subscription) {
                    $meterId = DB::table('water_meters')->insertGetId([
                        'serial_number' => $subscription->meter_number,
                        'status' => 'active',
                        'subscription_id' => $subscription->id,
                        'installed_at' => $subscription->created_at ? substr($subscription->created_at, 0, 10) : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('meter_events')->insert([
                        'water_meter_id' => $meterId,
                        'subscription_id' => $subscription->id,
                        'event_type' => 'installation',
                        'description' => 'Compteur repris depuis l’abonnement existant.',
                        'event_at' => $subscription->created_at ? substr($subscription->created_at, 0, 10) : now()->toDateString(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meter_events');
    }
};
