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
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('water_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->index(['water_zone_id', 'period']);
        });

        DB::table('invoices')
            ->whereNull('water_zone_id')
            ->whereExists(fn ($query) => $query->selectRaw('1')
                ->from('subscriptions')
                ->whereColumn('subscriptions.id', 'invoices.subscription_id')
                ->whereNotNull('subscriptions.water_zone_id'))
            ->update([
                'water_zone_id' => DB::raw('(select water_zone_id from subscriptions where subscriptions.id = invoices.subscription_id)'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['water_zone_id', 'period']);
            $table->dropConstrainedForeignId('water_zone_id');
        });
    }
};
