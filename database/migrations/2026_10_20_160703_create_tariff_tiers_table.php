<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tariff_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tariff_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->unsignedInteger('consumption_from');
            $table->unsignedInteger('consumption_to')->nullable();
            $table->unsignedInteger('unit_price');
            $table->timestamps();

            $table->unique(['tariff_id', 'position']);
            $table->unique(['tariff_id', 'consumption_from']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tariff_tiers');
    }
};
