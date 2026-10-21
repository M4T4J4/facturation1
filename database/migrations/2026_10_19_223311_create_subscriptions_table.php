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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('connection_request_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('reference')->unique();
            $table->text('service_address');
            $table->string('meter_number')->nullable()->unique();
            $table->string('status')->default('active')->index();
            $table->unsignedInteger('monthly_fee')->default(1000);
            $table->unsignedInteger('price_per_m3')->default(400);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
