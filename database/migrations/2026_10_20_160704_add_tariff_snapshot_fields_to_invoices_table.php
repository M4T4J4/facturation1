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
            $table->foreignId('tariff_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('water_amount')->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('amount_paid')->default(0);
            $table->json('tariff_breakdown')->nullable();
        });

        DB::table('invoices')
            ->where('status', 'paid')
            ->update(['amount_paid' => DB::raw('amount')]);

        DB::table('invoices')
            ->update([
                'water_amount' => DB::raw('consumption_m3 * price_per_m3'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tariff_id');
            $table->dropColumn(['water_amount', 'tax_amount', 'amount_paid', 'tariff_breakdown']);
        });
    }
};
