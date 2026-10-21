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
        Schema::create('water_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->default('EauVive SARL');
            $table->string('currency')->default('FCFA');
            $table->unsignedInteger('price_per_m3')->default(400);
            $table->unsignedInteger('monthly_fee')->default(1000);
            $table->unsignedInteger('connection_fee')->default(25000);
            $table->unsignedInteger('meter_deposit')->default(5000);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('water_settings');
    }
};
